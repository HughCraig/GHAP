<?php

namespace TLCMap\Console\Commands;

use Illuminate\Console\Command;
use TLCMap\ROCrate\ROCrateGenerator;

class ExportGhapRocrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:rocrate:ghap
                            {--dir= : The path of the target directory to hold the RO-Crate archive. Default to the current directory}
                            {--pgdump=pg_dump : The path of "pg_dump" utility. Default to "pg_dump" as it is in the system path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the whole GHAP database as RO-Crate';

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
        if (empty($this->option('dir'))) {
            $directory = getcwd();
        } else {
            $directory = $this->option('dir');
            // Check and remove tailing slash.
            $lastChar = substr($directory, strlen($directory) - 1);
            if ($lastChar === '\\' || $lastChar === '/') {
                $directory = substr($directory, 0, strlen($directory) - 1);
            }
        }
        $timestamp = date("YmdHis");
        $filePath = $directory . DIRECTORY_SEPARATOR . 'ghap-ro-crate-' . $timestamp . '.zip';
        try {
            $crate = ROCrateGenerator::generateGHAPCrate($filePath, $this->option('pgdump'));
            $this->info("RO-Crate has been created at {$crate}");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
