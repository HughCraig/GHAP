<?php

namespace TLCMap\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database
                            {--dir= : The path of the target directory to hold the backup files. Default to the current directory}
                            {--pgdump=pg_dump : The path of "pg_dump" utility. Default to "pg_dump" as it is in the system path}
                            {--keep-days= : The number of days of backups to keep. USE THIS WITH CAUTION. It will remove any backup files older than the specified days.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the whole GHAP database with RO-Crate';

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
        // Set the directory path.
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
        // Get the database name.
        $dbname = config('database.connections.pgsql.database');
        // Clean up expired backup files if the keep-days is set.
        if (!empty($this->option('keep-days')) && (int) $this->option('keep-days') > 0) {
            $this->cleanup($directory, $dbname, (int) $this->option('keep-days'));
        }
        // Set the backup file name.
        $filename = $this->createFileName($dbname);
        // Run the RO-Crate export artisan command.
        Artisan::call('export:rocrate:ghap', [
            '--dir' => $directory,
            '--name' => $filename,
            '--pgdump' => $this->option('pgdump')
        ]);
        $this->info('Backup is successful');
    }

    /**
     * Clean up the expired backup files.
     *
     * Note that the time of the backup file is determined from the timestamp in the file name instead of the real file
     * creation time.
     *
     * @param string $directory
     *   The backup directory.
     * @param string $dbname
     *   The database name. This is useful as it will clean up those backup files based on the current database.
     * @param string $days
     *   The number of days. Any backup file with the timestamp (in file name) earlier than the number of days ago from
     *   the current time will be deleted.
     * @return void
     */
    private function cleanup($directory, $dbname, $days)
    {
        $dirIterator = new \DirectoryIterator($directory);
        /**
         * @var \DirectoryIterator $file
         */
        foreach ($dirIterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                $fileTimestamp = $this->getTimeStampFromFileName($filename, $dbname);
                if (isset($fileTimestamp)) {
                    $comparisonTimestamp = strtotime("-{$days} days");
                    if ($fileTimestamp < $comparisonTimestamp) {
                        unlink($file->getPathname());
                        $this->line("Backup file {$file->getPathname()} has been deleted");
                    }
                }
            }
        }
    }

    /**
     * Generate the backup file name.
     *
     * @param $dbname
     * @return string
     */
    private function createFileName($dbname)
    {
        $timestamp = date("YmdHis");
        return 'backup_ro_crate_' . $dbname . '_' . $timestamp . '.zip';
    }

    /**
     * Get the timestamp from a backup file name.
     *
     * @param string $filename
     *   The backup file name.
     * @param string $dbname
     *   The database name.
     * @return int|null
     *   The UNIX timestamp, or null if there's no timestamp found from the file name.
     */
    private function getTimeStampFromFileName($filename, $dbname)
    {
        if (preg_match('/^backup_ro_crate_' . preg_quote($dbname, '/') . '_(\d{14})\.zip$/', $filename, $matches)) {
            return \DateTime::createFromFormat('YmdHis', $matches[1])->getTimestamp();
        }
        return null;
    }
}
