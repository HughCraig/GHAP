<?php

namespace TLCMap\Console\Commands;

use Illuminate\Console\Command;
use TLCMap\Http\Helpers\CSVReader;
use TLCMap\Http\Helpers\UID;
use TLCMap\Models\Dataitem;
use TLCMap\Models\Datasource;
use TLCMap\Models\RecordType;

class ImportNcgData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:ncg
                            {datasource : The ID of the datasource}
                            {csv : The path of the source CSV file}
                            {--update : Update the existing records if found by NCG ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import NCG data to GHAP. The source data should be in CSV format with all common fields.
                              Additional columns "DATESTART" and "ALTNAME" are consolidated from authority fields.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->warn('USE WITH CAUTION.');
        $this->warn('This task may modify the existing data and cause possible data corruption and inconsistency.');
        if ($this->confirm('Do you wish to continue?')) {
            $datasource = Datasource::find($this->argument('datasource'));
            if ($datasource) {
                $rows = CSVReader::read($this->argument('csv'));
                $count = count($rows);
                $this->line('Importing data...');
                $bar = $this->output->createProgressBar($count);
                // Get record type.
                $recordType = RecordType::where('type', 'Placename')->first();
                foreach ($rows as $row) {
                    $dataitem = null;
                    if ($this->option('update')) {
                        $dataitem = Dataitem::where('original_id', $row['ID'])->first();
                    }
                    if (!isset($dataitem)) {
                        $dataitem = new Dataitem();
                    }

                    $extendedData = [];
                    $dataitem->datasource_id = $datasource->id;
                    $dataitem->title = $row['NAME'];
                    $dataitem->placename = $row['NAME'];
                    $dataitem->original_id = $row['ID'];
                    $dataitem->recordtype_id = $recordType->id;
                    $dataitem->latitude = $row['LATITUDE'];
                    $dataitem->longitude = $row['LONGITUDE'];
                    $dataitem->feature_term = ($row['FEATURE'] === null ? null : strtolower($row['FEATURE']));
                    if (!empty($row['CATEGORY'])) {
                        $extendedData['category'] = strtolower($row['CATEGORY']);
                    }
                    if (!empty($row['GROUP'])) {
                        $extendedData['group'] = strtolower($row['GROUP']);
                    }
                    if (!empty($row['AUTHORITY'])) {
                        if ($row['AUTHORITY'] === 'AHO') {
                            $dataitem->state = null;
                        } else {
                            $dataitem->state = $row['AUTHORITY'];
                        }
                        $extendedData['authority'] = $row['AUTHORITY'];
                    } else {
                        $dataitem->state = null;
                    }
                    if (!empty($row['SUPPLY_DATE'])) {
                        $extendedData['supply_date'] = $this->normaliseDate($row['SUPPLY_DATE']);
                    }
                    $dataitem->datestart = ($row['DATESTART'] === null ? null : $this->normaliseDate($row['DATESTART']));
                    if (!empty($row['ALTNAME'])) {
                        $extendedData['altname'] = $row['ALTNAME'];
                    }
                    if (!empty($extendedData)) {
                        $dataitem->setExtendedData($extendedData);
                    }

                    $dataitem->save();
                    // Generate UID.
                    if (empty($dataitem->uid) && !empty($dataitem->id)) {
                        $dataitem->uid = UID::create($dataitem->id, 'n');
                        $dataitem->save();
                    }
                    $bar->advance();
                }
                $this->info("\nSuccessfully imported {$count} records");
            } else {
                $this->error('Couldn\'t find datasource ' . $this->argument('datasource'));
            }
        }
    }

    /**
     * Normalise a date string.
     *
     * @param string $dateString
     *   The original date string.
     * @return string
     *   The normalised date in 'yyyy-mm-dd' format.
     *
     */
    private function normaliseDate($dateString)
    {
        if (preg_match('/(\d{4})(\d{2})(\d{2})/', $dateString, $matches)) {
            return "$matches[1]-$matches[2]-$matches[3]";
        } elseif (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $dateString, $matches)) {
            return "$matches[3]-$matches[2]-$matches[1]";
        } elseif (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $dateString, $matches)) {
            return "$matches[1]-$matches[2]-$matches[3]";
        }
        return null;
    }
}
