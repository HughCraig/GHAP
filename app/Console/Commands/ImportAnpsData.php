<?php

namespace TLCMap\Console\Commands;

use Illuminate\Console\Command;
use TLCMap\Http\Helpers\UID;
use TLCMap\Models\Dataitem;
use TLCMap\Models\RecordType;

class ImportAnpsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:anps
                            {datasource : The ID of the datasource}
                            {dbname : The name of the ANPS database}
                            {--host=127.0.0.1 : The host of the ANPS database}
                            {--port=5432 : The port of the ANPS database}
                            {--username= : The username of the ANPS database. Default to the username used for GHAP database}
                            {--password= : The password of the ANPS database. Default to the password used for GHAP database}
                            {--charset=UTF8 : The charset of the ANPS database}
                            {--update : Update the existing records if found by ANPS ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import ANPS data to GHAP';

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
            $username = $this->option('username');
            if (empty($username)) {
                $username = config('database.connections.pgsql.username');
            }
            $password = $this->option('password');
            if (empty($password)) {
                $password = config('database.connections.pgsql.password');
            }
            $dsn = "pgsql:host={$this->option('host')};port={$this->option('port')};dbname={$this->argument('dbname')};options='--client_encoding={$this->option('charset')}'";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);

            // Get source lookup.
            $sourceLookup = $this->getSourceLookup($pdo);

            // Get record type.
            $recordType = RecordType::where('type', 'Placename')->first();

            // Get and import all ANPS records.
            $query = 'select * from gazetteer.register order by gazetteer.register.anps_id';
            $statement = $pdo->query($query);
            $count = $statement->rowCount();

            $this->line('Importing data...');
            $bar = $this->output->createProgressBar($count);
            foreach ($statement as $row) {
                $anpsID = $row['anps_id'];
                if ($this->option('update')) {
                    $dataitem = Dataitem::where('original_id', $anpsID)->first();
                    if (!$dataitem) {
                        $dataitem = new Dataitem();
                    }
                } else {
                    $dataitem = new Dataitem();
                }
                $dataitem->original_id = $anpsID;
                $dataitem->uid = UID::create($anpsID, 'a');
                $dataitem->datasource_id = $this->argument('datasource');
                $dataitem->recordtype_id = $recordType->id;

                if (!empty($row['placename'])) {
                    $dataitem->title = $row['placename'];
                    $dataitem->placename = $row['placename'];
                }
                if (!empty($row['feature_term'])) {
                    $dataitem->feature_term = $row['feature_term'];
                }
                if (!empty($row['description'])) {
                    $dataitem->description = $row['description'];
                }
                if (!empty($row['lga_name'])) {
                    $dataitem->lga = $row['lga_name'];
                }
                if (!empty($row['state_code'])) {
                    $dataitem->state = $row['state_code'];
                }
                if (!empty($row['parish'])) {
                    $dataitem->parish = $row['parish'];
                }
                if (!empty($row['parish'])) {
                    $dataitem->parish = $row['parish'];
                }
                if (isset($row['tlcm_latitude'])) {
                    $dataitem->latitude = $row['tlcm_latitude'];
                }
                if (isset($row['tlcm_longitude'])) {
                    $dataitem->longitude = $row['tlcm_longitude'];
                }
                if (!empty($row['tlcm_start'])) {
                    $dataitem->datestart = $row['tlcm_start'];
                }
                if (!empty($row['tlcm_end'])) {
                    $dataitem->dateend = $row['tlcm_end'];
                }
                if (!empty($row['flag'])) {
                    $dataitem->flag = $row['flag'];
                }
                $source = '';
                if (!empty($row['original_data_source'])) {
                    $source .= $row['original_data_source'];
                }
                if (isset($sourceLookup[$anpsID])) {
                    if (!empty($source)) {
                        $source .= "\n";
                    }
                    $source .= $sourceLookup[$anpsID];
                }
                if (!empty($source)) {
                    $dataitem->source = $source;
                }
                $dataitem->save();
                $bar->advance();
            }
            $this->info("\nSuccessfully imported {$count} records");
        }
    }

    /**
     * Get the ANPS ID to source text lookup.
     *
     * @param \PDO $pdo
     *   The database connection to the ANPS source database.
     * @return array
     *   An array keyed by ANPS ID while each element is the concatenated source text.
     */
    private function getSourceLookup($pdo)
    {
        $query = 'select distinct gazetteer.documentation.anps_id, gazetteer."source".*
                from gazetteer.documentation
                join gazetteer."source" on gazetteer.documentation.doc_source_id = gazetteer."source".source_id
                order by gazetteer.documentation.anps_id';
        $statement = $pdo->query($query);
        $sourceLookup = [];
        // Array keyed by ANPS ID while each element is an array of concatenated source text.
        $anpsIDToSources = [];
        foreach ($statement as $result) {
            $anpsID = $result['anps_id'];
            if (!isset($anpsIDToSources[$anpsID])) {
                $anpsIDToSources[$anpsID] = [];
            }
            $anpsIDToSources[$anpsID][] = $this->generateSourceText($result);
        }
        // Concatenate multiple sources to one single string.
        foreach ($anpsIDToSources as $anpsID => $sourceTexts) {
            foreach ($sourceTexts as $index => $sourceText) {
                $sourceTexts[$index] = ($index + 1) . '. ' . $sourceText;
            }
            $sourceLookup[$anpsID] = implode("\n", $sourceTexts);
        }
        return $sourceLookup;
    }

    /**
     * Generate the concatenated text from a source record.
     *
     * @param array $row
     *   A source record from the database.
     * @return string
     *   The generated source text.
     */
    private function generateSourceText($row)
    {
        $sourceText = $row['title'] . ';';
        if (!empty($row['source_type'])) {
            $sourceText .= " Type: {$row['source_type']};";
        }
        if (!empty($row['author'])) {
            $sourceText .= " Author: {$row['author']};";
        }
        if (!empty($row['isbn'])) {
            $sourceText .= " ISBN: {$row['isbn']};";
        }
        if (!empty($row['publisher'])) {
            $sourceText .= " Publisher: {$row['publisher']};";
        }
        if (!empty($row['source_place'])) {
            $sourceText .= " Place: {$row['source_place']};";
        }
        if (!empty($row['anps_library'])) {
            $sourceText .= " ANPS Library: {$row['anps_library']};";
        }
        if (!empty($row['source_status'])) {
            $sourceText .= " Status: {$row['source_status']};";
        }
        if (!empty($row['source_notes'])) {
            $sourceText .= " Notes: {$row['source_notes']};";
        }
        $sourceText .= " ANPS Source ID: {$row['source_id']};";
        return $sourceText;
    }
}
