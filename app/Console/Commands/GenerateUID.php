<?php

namespace TLCMap\Console\Commands;

use Illuminate\Console\Command;
use TLCMap\Http\Helpers\UID;
use TLCMap\Models\Dataitem;
use TLCMap\Models\Datasource;

class GenerateUID extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:uid
                            {datasource : The ID of the datasource}
                            {--overwrite : Whether to overwrite for existing UID}
                            {--prefix=t : The prefix letter used for UID}
                            {--column=id : The ID column used for UID generation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate UID for data item records';

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
                /**
                 * @var Dataitem $dataitem
                 */
                foreach($datasource->dataitems as $dataitem) {
                    if (empty($dataitem->uid) || $this->option('overwrite')) {
                        $column = $this->option('column');
                        $baseID = $dataitem->getAttribute($column);
                        $prefix = $this->option('prefix');
                        if (isset($baseID)) {
                            $dataitem->uid = UID::create($baseID, $prefix);
                            $dataitem->save();
                        } else {
                            $this->error("Base ID value of dataitem {$dataitem->id} is not available.");
                        }
                    }
                }
                $this->info('UID generated successfully.');
            } else {
                $this->error('Couldn\'t find datasource ' . $this->argument('datasource'));
            }
        }
    }
}
