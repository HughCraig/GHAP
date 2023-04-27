<?php

namespace TLCMap\ROCrate;

use Illuminate\Support\Facades\Auth;
use TLCMap\Http\Helpers\FileFormatter;
use TLCMap\Models\Collection;
use TLCMap\Models\Dataset;

class ROCrateGenerator
{
    /**
     * Create the RO-Crate zip archive for a dataset.
     *
     * @param Dataset $dataset
     *   The dataset object.
     * @param string|null $path
     *   The path of the zip archive. If set to empty, it will create a temporary file.
     * @return string|null
     *   The file path of the zip archive, or null on fail.
     * @throws \Throwable
     */
    public static function generateDatasetCrate(Dataset $dataset, $path = null)
    {
        $zip = new \ZipArchive();
        if (empty($path)) {
            // Create a temporary file for the archive
            $zipFile = tempnam(sys_get_temp_dir(), 'GHAP');
        } else {
            $zipFile = $path;
        }
        if ($zipFile && $zip->open($zipFile, \ZipArchive::CREATE) === TRUE) {
            $metadata = self::generateDatasetMetadata($dataset);
            $zip->addFromString('ro-crate-metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
            $zip->addFromString('ro-crate-preview.html', self::generateDatasetHtml($metadata));
            $zip->addFromString(self::getDatasetExportFileName($dataset, 'csv'), $dataset->csv());
            $zip->addFromString(self::getDatasetExportFileName($dataset, 'kml'), $dataset->kml());
            $zip->addFromString(self::getDatasetExportFileName($dataset, 'json'), $dataset->json());
            $zip->close();
            return $zipFile;
        }
        return null;
    }

    /**
     * Generate the metadata of the dataset.
     *
     * @param Dataset $dataset
     * @return array
     *   The content of the metadata.
     */
    public static function generateDatasetMetadata(Dataset $dataset)
    {
        $metadata = new Metadata();
        $rootEntity = self::createDatasetDataEntity($dataset);
        $metadata->addDataEntity($rootEntity);

        return $metadata->getData();
    }

    /**
     * Create the DataEntity for a dataset.
     *
     * @param Dataset $dataset
     *   The dataset object.
     * @param string $directory
     *   The directory represents the dataset. Default is an empty string which points to the root directory.
     * @return DataEntity
     *   The DataEntity object.
     * @throws \Exception
     */
    public static function createDatasetDataEntity(Dataset $dataset, $directory = '')
    {
        if (!empty($directory)) {
            // Add tailing slash.
            if (substr($directory, strlen($directory) - 1) !== '/') {
                $directory .= '/';
            }
        }

        $dataEntity = new DataEntity('Dataset', empty($directory) ? './' : $directory);
        $dataEntity->set('datePublished', date("Y-m-d"));
        $dataEntity->set('name', $dataset->name);
        $dataEntity->set('description', $dataset->description);
        if (!empty($dataset->creator)) {
            $dataEntity->set('creator', $dataset->creator);
        }
        if (!empty($dataset->publisher)) {
            $dataEntity->set('publisher', $dataset->publisher);
        }
        if (!empty($dataset->citation)) {
            $dataEntity->set('citation', $dataset->citation);
        }
        if (!empty($dataset->doi)) {
            $dataEntity->set('identifier', $dataset->doi);
        }
        if ($dataset->public) {
            $dataEntity->set('url', url("publicdatasets/{$dataset->id}"));
        }
        if (!empty($dataset->source_url)) {
            $dataEntity->append('url', $dataset->source_url);
        }
        if (!empty($dataset->linkback)) {
            $dataEntity->append('url', $dataset->linkback);
        }
        $temporalCoverage = '';
        if (!empty($dataset->temporal_from)) {
            $temporalCoverage .= $dataset->temporal_from;
        }
        if (!empty($dataset->temporal_to)) {
            $temporalCoverage .= '/' . $dataset->temporal_to;
        }
        if (!empty($temporalCoverage)) {
            $dataEntity->set('temporalCoverage', $temporalCoverage);
        }
        if (!empty($dataset->created)) {
            $dataEntity->set('dateCreated', $dataset->created);
        }
        if (!empty($dataset->language)) {
            $dataEntity->set('inLanguage', $dataset->language);
        }
        if (!empty($dataset->license)) {
            $dataEntity->set('license', $dataset->license);
        }
        if (!empty($dataset->rights)) {
            $dataEntity->set('copyrightNotice', $dataset->rights);
        }
        if (!empty($dataset->recordtype)) {
            $dataEntity->set('keywords', $dataset->recordtype->type);
        }
        if (!empty($dataset->warning)) {
            $dataEntity->set('comment', $dataset->warning);
        }
        if (
            !empty($dataset->latitude_from) &&
            !empty($dataset->latitude_to) &&
            !empty($dataset->longitude_from) &&
            !empty($dataset->longitude_to)
        ) {
            $spatialCoverage = new DataEntity('Place');
            $coverageShape = new DataEntity('GeoShape');
            $coverageShape->set('box', "{$dataset->latitude_from},{$dataset->longitude_from} {$dataset->latitude_to},{$dataset->longitude_to}");
            $spatialCoverage->set('geo', $coverageShape);
            $dataEntity->set('spatialCoverage', $spatialCoverage);
        }

        // Add files.
        $csvEntity = new DataEntity('File', $directory . self::getDatasetExportFileName($dataset, 'csv'));
        $csvEntity->set('name', "CSV export of {$dataset->name}");
        $csvEntity->set('description', "CSV export of the layer data");
        $csvEntity->set('encodingFormat', 'text/csv');
        $dataEntity->addPart($csvEntity);

        $kmlEntity = new DataEntity('File', $directory . self::getDatasetExportFileName($dataset, 'kml'));
        $kmlEntity->set('name', "KML export of {$dataset->name}");
        $kmlEntity->set('description', "KML export of the layer data");
        $kmlEntity->set('encodingFormat', 'application/vnd.google-earth.kml+xml');
        $dataEntity->addPart($kmlEntity);

        $jsonEntity = new DataEntity('File', $directory . self::getDatasetExportFileName($dataset, 'json'));
        $jsonEntity->set('name', "GeoJSON export of {$dataset->name}");
        $jsonEntity->set('description', "GeoJSON export of the layer data");
        $jsonEntity->set('encodingFormat', 'application/geo+json');
        $dataEntity->addPart($jsonEntity);

        return $dataEntity;
    }

    /**
     * Generate the HTML of the dataset crate.
     *
     * @param array $metadata
     *   The RO-Crate metadata.
     * @return string
     *   The HTML content.
     * @throws \Throwable
     */
    public static function generateDatasetHtml($metadata)
    {
        return view('rocrate.dataset', ['metadata' => $metadata])->render();
    }

    /**
     * Create the RO-Crate zip archive for a collection.
     *
     * @param Collection $collection
     *   The collection object.
     * @param $path
     *   The path of the zip archive. If set to empty, it will create a temporary file.
     * @return string|null
     *   The file path of the zip archive, or null on fail.
     */
    public static function generateCollectionCrate(Collection $collection, $path = null)
    {
        if ($collection->datasets->count() > 0) {
            $zip = new \ZipArchive();
            if (empty($path)) {
                // Create a temporary file for the archive
                $zipFile = tempnam(sys_get_temp_dir(), 'GHAP');
            } else {
                $zipFile = $path;
            }
            if ($zipFile && $zip->open($zipFile, \ZipArchive::CREATE) === TRUE) {
                $metadata = self::generateCollectionMetadata($collection);
                $zip->addFromString('ro-crate-metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
                $zip->addFromString('ro-crate-preview.html', self::generateCollectionHtml($metadata));

                foreach ($collection->datasets as $dataset) {
                    $directory = self::getDatasetDirectoryName($dataset);
                    $zip->addEmptyDir($directory);
                    $zip->addFromString($directory . '/' . self::getDatasetExportFileName($dataset, 'csv'), $dataset->csv());
                    $zip->addFromString($directory . '/' . self::getDatasetExportFileName($dataset, 'kml'), $dataset->kml());
                    $zip->addFromString($directory . '/' . self::getDatasetExportFileName($dataset, 'json'), $dataset->json());
                }
                $zip->close();
                return $zipFile;
            }
        }
        return null;
    }

    /**
     * Generate the metadata of the collection.
     *
     * @param Collection $collection
     * @return array
     *   The metadata content.
     * @throws \Exception
     */
    public static function generateCollectionMetadata(Collection $collection)
    {
        $metadata = new Metadata();
        $rootEntity = new DataEntity('Dataset', './');
        $rootEntity->set('datePublished', date("Y-m-d"));
        $rootEntity->set('name', $collection->name);
        if (!empty($collection->description)) {
            $rootEntity->set('description', $collection->description);
        }
        if (!empty($collection->creator)) {
            $rootEntity->set('creator', $collection->creator);
        }
        if (!empty($collection->publisher)) {
            $rootEntity->set('publisher', $collection->publisher);
        }
        if (!empty($collection->citation)) {
            $rootEntity->set('citation', $collection->citation);
        }
        if (!empty($collection->doi)) {
            $rootEntity->set('identifier', $collection->doi);
        }
        if ($collection->public) {
            $rootEntity->set('url', url("publiccollections/{$collection->id}"));
        }
        if (!empty($collection->source_url)) {
            $rootEntity->append('url', $collection->source_url);
        }
        if (!empty($collection->linkback)) {
            $rootEntity->append('url', $collection->linkback);
        }
        $temporalCoverage = '';
        if (!empty($collection->temporal_from)) {
            $temporalCoverage .= $collection->temporal_from;
        }
        if (!empty($collection->temporal_to)) {
            $temporalCoverage .= '/' . $collection->temporal_to;
        }
        if (!empty($temporalCoverage)) {
            $rootEntity->set('temporalCoverage', $temporalCoverage);
        }
        if (!empty($collection->created)) {
            $rootEntity->set('dateCreated', $collection->created);
        }
        if (!empty($collection->language)) {
            $rootEntity->set('inLanguage', $collection->language);
        }
        if (!empty($collection->license)) {
            $rootEntity->set('license', $collection->license);
        }
        if (!empty($collection->rights)) {
            $rootEntity->set('copyrightNotice', $collection->rights);
        }
        if (!empty($collection->warning)) {
            $rootEntity->set('comment', $collection->warning);
        }
        if (
            !empty($collection->latitude_from) &&
            !empty($collection->latitude_to) &&
            !empty($collection->longitude_from) &&
            !empty($collection->longitude_to)
        ) {
            $spatialCoverage = new DataEntity('Place');
            $coverageShape = new DataEntity('GeoShape');
            $coverageShape->set('box', "{$collection->latitude_from},{$collection->longitude_from} {$collection->latitude_to},{$collection->longitude_to}");
            $spatialCoverage->set('geo', $coverageShape);
            $rootEntity->set('spatialCoverage', $spatialCoverage);
        }

        // Add dataset data entities.
        foreach ($collection->datasets as $dataset) {
            $directory = self::getDatasetDirectoryName($dataset);
            $datasetDataEntity = self::createDatasetDataEntity($dataset, $directory);
            $rootEntity->addPart($datasetDataEntity);
        }

        $metadata->addDataEntity($rootEntity);

        return $metadata->getData();
    }

    /**
     * Generate the HTML of the collection crate.
     *
     * @param array $metadata
     *   The RO-Crate metadata.
     * @return string
     *   The HTML content.
     * @throws \Throwable
     */
    public static function generateCollectionHtml($metadata)
    {
        return view('rocrate.collection', ['metadata' => $metadata])->render();
    }

    /**
     * Generate the RO-Crate archive for search results.
     *
     * @param array $results
     *   The search results.
     * @param array $parameters
     *   The search parameters.
     * @param $path
     *   The path of the zip archive. If set to empty, it will create a temporary file.
     * @return string|null
     *   The file path of the zip archive, or null on fail.
     * @throws \Throwable
     */
    public static function generateSearchCrate($results, $parameters, $path = null)
    {
        $zip = new \ZipArchive();
        if (empty($path)) {
            // Create a temporary file for the archive
            $zipFile = tempnam(sys_get_temp_dir(), 'GHAP');
        } else {
            $zipFile = $path;
        }
        if ($zipFile && $zip->open($zipFile, \ZipArchive::CREATE) === TRUE) {
            $metadata = self::generateSearchMetadata($parameters);
            $zip->addFromString('ro-crate-metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
            $zip->addFromString('ro-crate-preview.html', self::generateSearchHtml($metadata));
            $zip->addFromString(self::getSearchExportFileName('csv'), FileFormatter::toCSVContent($results));
            $zip->addFromString(self::getSearchExportFileName('kml'), FileFormatter::toKML2($results, $parameters));
            $zip->addFromString(self::getSearchExportFileName('json'), FileFormatter::toGeoJSON($results));
            $zip->close();
            return $zipFile;
        }
        return null;
    }

    /**
     * Generate the metadata of the search results.
     *
     * @param array $parameters
     *   The search parameters.
     * @return array
     *   The metadata content.
     * @throws \Exception
     */
    public static function generateSearchMetadata($parameters)
    {
        $metadata = new Metadata();
        $rootEntity = new DataEntity('Dataset', './');
        $rootEntity->set('datePublished', date("Y-m-d"));
        $rootEntity->set('name', 'GHAP search results');
        $rootEntity->set('description', 'Export of search results data from GHAP');
        // Check the current logged in user and set the name as creator.
        $user = Auth::user();
        if ($user) {
            $rootEntity->set('creator', $user->name);
        }
        // Reconstruct the search URL.
        $queryParameters = [];
        foreach ($parameters as $name => $value) {
            if (isset($value) && $name !== 'format' && $name !== 'download') {
                $queryParameters[] = "{$name}={$value}";
            }
        }
        $rootEntity->set('url', url('search?' . implode('&', $queryParameters)));

        // Add files.
        $csvEntity = new DataEntity('File', self::getSearchExportFileName('csv'));
        $csvEntity->set('name', "CSV export of search results");
        $csvEntity->set('description', "CSV export of the search results");
        $csvEntity->set('encodingFormat', 'text/csv');
        $rootEntity->addPart($csvEntity);

        $kmlEntity = new DataEntity('File', self::getSearchExportFileName('kml'));
        $kmlEntity->set('name', "KML export of search results");
        $kmlEntity->set('description', "KML export of the search results");
        $kmlEntity->set('encodingFormat', 'application/vnd.google-earth.kml+xml');
        $rootEntity->addPart($kmlEntity);

        $jsonEntity = new DataEntity('File', self::getSearchExportFileName('json'));
        $jsonEntity->set('name', "GeoJSON export of search results");
        $jsonEntity->set('description', "GeoJSON export of the search results");
        $jsonEntity->set('encodingFormat', 'application/geo+json');
        $rootEntity->addPart($jsonEntity);

        $metadata->addDataEntity($rootEntity);

        return $metadata->getData();
    }

    /**
     * Generate the HTML of the search results crate.
     *
     * @param array $metadata
     *   The RO-Crate metadata.
     * @return string
     *   The HTML content.
     * @throws \Throwable
     */
    public static function generateSearchHtml($metadata)
    {
        return view('rocrate.search', ['metadata' => $metadata])->render();
    }

    /**
     * Generate the RO-Crate archive for GHAP database.
     *
     * @param null $path
     *   The path of the zip archive. If set to empty, it will create a temporary file.
     * @param string $pgdump
     *   The path of 'pg_dump' utility. Default to 'pg_dump' as it's in the system path.
     * @return string|null
     *   The file path of the zip archive, or null on fail.
     * @throws \Exception
     */
    public static function generateGHAPCrate($path = null, $pgdump = 'pg_dump')
    {
        $zip = new \ZipArchive();
        if (empty($path)) {
            // Create a temporary file for the archive
            $zipFile = tempnam(sys_get_temp_dir(), 'GHAP');
        } else {
            $zipFile = $path;
        }
        if ($zipFile && $zip->open($zipFile, \ZipArchive::CREATE) === TRUE) {
            $timestamp = date("YmdHis");
            $metadata = self::generateGHAPMetadata($timestamp);
            $zip->addFromString('ro-crate-metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
            // Add the database dump.
            $dumpFile = self::createGHAPDatabaseDump(null, $pgdump);
            if ($dumpFile) {
                $zip->addFile($dumpFile, self::getGHAPExportFileName($timestamp, 'sql'));
            }
            $zip->close();
            // Delete the temporary dump file.
            unlink($dumpFile);
            return $zipFile;
        }
        return null;
    }

    /**
     * Generate the metadata of the GHAP RO-Crate.
     *
     * @param string $timestamp
     *   The timestamp used in the file name.
     * @return array
     *   The metadata content.
     * @throws \Exception
     */
    public static function generateGHAPMetadata($timestamp)
    {
        $metadata = new Metadata();
        $rootEntity = new DataEntity('Dataset', './');
        $rootEntity->set('datePublished', date("Y-m-d"));
        $rootEntity->set('name', 'GHAP database snapshot');
        $rootEntity->set('description', 'The snapshot of the whole GHAP database');

        // Add files.
        $fileEntity = new DataEntity('File', self::getGHAPExportFileName($timestamp, 'sql'));
        $fileEntity->set('name', "GHAP database dump");
        $fileEntity->set('description', "The SQL dump file of the GHAP database");
        $fileEntity->set('encodingFormat', 'text/plain');
        $rootEntity->addPart($fileEntity);

        $metadata->addDataEntity($rootEntity);

        return $metadata->getData();
    }

    /**
     * Create a database dump of the GHAP database.
     *
     * @param string $directory
     *   The path of the directory where to create the dump file. If not specified, it will use the system temporary
     *   file directory.
     * @param string $pgdump
     *   The path of 'pg_dump' utility. Default to 'pg_dump' as it's in the system path.
     * @return string
     *   The path of the dump file.
     * @throws \Exception
     */
    public static function createGHAPDatabaseDump($directory = null, $pgdump = 'pg_dump')
    {
        if (empty($directory)) {
            $directory = sys_get_temp_dir();
        }
        $file = tempnam($directory, 'GHAP');
        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port');
        $dbname = config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');

        // Set the password in environment variables.
        putenv("PGPASSWORD={$password}");

        $cmd = "{$pgdump} -h {$host} -p {$port} -U {$username} -F p -f \"{$file}\" -O -x {$dbname}";

        system($cmd, $retval);
        // Check if the command executed successfully
        if ($retval !== 0) {
            throw new \Exception('Failed to create the database dump');
        }
        return $file;
    }

    /**
     * Get the directory name of a dataset.
     *
     * @param Dataset $dataset
     * @return string
     */
    public static function getDatasetDirectoryName(Dataset $dataset)
    {
        return "export-layer-{$dataset->id}";
    }

    /**
     * Get the export file name of a dataset.
     *
     * @param Dataset $dataset
     * @param string $extension
     *   The file extension.
     * @return string
     */
    public static function getDatasetExportFileName(Dataset $dataset, $extension)
    {
        return "TLCMLayer_{$dataset->id}.{$extension}";
    }

    /**
     * Get the export file name of the search results.
     *
     * @param string $extension
     *   The file extension.
     * @return string
     */
    public static function getSearchExportFileName($extension)
    {
        return "tlcmap_output.{$extension}";
    }

    /**
     * Get the export file name of the GHAP database.
     *
     * @param string $timestamp
     *   The time stamp append to the file name.
     * @param string $extension
     *   The file extension.
     * @return string
     */
    public static function getGHAPExportFileName($timestamp, $extension)
    {
        return "ghap_db_dump_{$timestamp}.{$extension}";
    }
}
