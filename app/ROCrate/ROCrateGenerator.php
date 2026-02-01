<?php

namespace TLCMap\ROCrate;

use Illuminate\Support\Facades\Auth;
use TLCMap\Http\Helpers\FileFormatter;
use TLCMap\Models\Collection;
use TLCMap\Models\Dataset;
use TLCMap\Models\SavedSearch;
use Illuminate\Http\Request;
use TLCMap\Http\Controllers\GazetteerController;
use Illuminate\Support\HtmlString;
use TLCMap\Models\TextContext;

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
            $zip->addFromString('ro-crate-metadata.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $zip->addFromString('ro-crate-preview.html', self::generateDatasetHtml($metadata));
            $zip->addFromString(self::getDatasetExportFileName($dataset, 'csv'), $dataset->csv());
            $zip->addFromString(self::getDatasetExportFileName($dataset, 'kml'), $dataset->kml());

            //Remove escape slashes from GeoJSON
            $formattedGeoJSON = json_encode( json_decode($dataset->json()) , JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);  
            $zip->addFromString(self::getSearchExportFileName('json'), $formattedGeoJSON);

            if ($dataset->recordtype->type == 'Text' && $dataset->text) {
                $text = $dataset->text;

                $textContexts = [];
                $dateitems = $dataset->dataitems()->where('recordtype_id', "4")->get();
                foreach ($dateitems as $dataitem) {
                    $textContext = TextContext::getContentByDataitemUid($dataitem->uid);
                    if ($textContext->count() > 0) {
                        $textContexts[] = $textContext->first();
                    }
                }

                $markedHtml = self::generateMarkedTextHtml(
                    $text->content,
                    $textContexts
                );

                $zip->addFromString(
                    'text.html',
                    view('rocrate.text', [
                        'markedText' => $markedHtml,
                        'metadata'   => $metadata,
                    ])->render()
                );
            }

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

        // Get all file IDs.
        $files = $rootEntity->getParts();
        $fileIDs = [];
        foreach ($files as $file) {
            $fileIDs[] = ['@id' => $file->id()];
        }
        $actionEntity = self::createApplicationDataEntity($fileIDs);

        $metadata->addDataEntity($actionEntity);

        return $metadata->getData();
    }

    /**
     * Create the application data entity used to describe the application used to generate the files.
     *
     * @param array $fileIDs
     *   The array of file IDs where each element in the format of ['@id' => 'file_id'];
     * @return DataEntity
     *   The 'CreateAction' data entity describes the used application and the files it generates.
     * @throws \Exception
     */
    public static function createApplicationDataEntity($fileIDs)
    {
        $appEntity = new DataEntity('SoftwareApplication', config('app.url'));
        $appEntity->set('url', config('app.url'));
        $appEntity->set('name', config('app.name'));
        $appEntity->set('version', config('app.version'));

        $actionEntity = new DataEntity('CreateAction');
        $actionEntity->set('instrument', $appEntity);

        $actionEntity->set('result', $fileIDs);
        return $actionEntity;
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
        $dataEntity->set('datePublished', $dataset->created_at->toDateString());
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
            $dataEntity->set('language', $dataset->language);
        }
        if (!empty($dataset->license)) {
            $license = new DataEntity('CreativeWork');
            $license->set('name', $dataset->license);
            $dataEntity->set('license', $license);
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
     * Create the DataEntity for a saved search.
     *
     * @param SavedSearch $savedSearch
     *   The dataset object.
     * @param string $directory
     *   The directory represents the dataset. Default is an empty string which points to the root directory.
     * @return DataEntity
     *   The DataEntity object.
     * @throws \Exception
     */
    public static function createSavedSearchDataEntity(SavedSearch $savedSearch, $directory = '')
    {
        if (!empty($directory)) {
            // Add tailing slash.
            if (substr($directory, strlen($directory) - 1) !== '/') {
                $directory .= '/';
            }
        }
    
        $dataEntity = new DataEntity('Dataset', empty($directory) ? './' : $directory);
        $dataEntity->set('name', 'GHAP search results: ' . $savedSearch->name);
        $dataEntity->set('description' , "Export of search results data from GHAP");
        $dataEntity->set('url', url($savedSearch->query));
        $dataEntity->set('creator', $savedSearch->getOwnerName());
        
        if (!empty($savedSearch->created_at)) {
            $dataEntity->set('datePublished', $savedSearch->created_at->toDateString());
        }
        if (!empty($savedSearch->updated_at)) {
            $dataEntity->set('dateModified', $savedSearch->updated_at->toDateString());
        }

        //Add files
        $csvEntity = new DataEntity('File', $directory . self::getSavedSearchExportFileName($savedSearch, 'csv'));
        $csvEntity->set('name', "CSV export of search result {$savedSearch->name}");
        $csvEntity->set('description', "CSV export of the search results");
        $csvEntity->set('encodingFormat', 'text/csv'); 
        $dataEntity->addPart($csvEntity);

        $kmlEntity = new DataEntity('File', $directory . self::getSavedSearchExportFileName($savedSearch, 'kml'));
        $kmlEntity->set('name', "KML export of search result {$savedSearch->name}");
        $kmlEntity->set('description', "KML export of the search results");
        $kmlEntity->set('encodingFormat', 'application/vnd.google-earth.kml+xml');
        $dataEntity->addPart($kmlEntity);

        $jsonEntity = new DataEntity('File', $directory . self::getSavedSearchExportFileName($savedSearch, 'json'));
        $jsonEntity->set('name', "GeoJSON export of search result {$savedSearch->name}");
        $jsonEntity->set('description', "GeoJSON export of the search results");
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


    private static function generateMarkedTextHtml(string $textContent, $textContexts)
    {
        if (empty($textContexts)) {
            return new HtmlString($textContent);
        }

        $markedText = '';
        $lastIndex = 0;

        usort($textContexts, function ($a, $b) {
            return $a->start_index <=> $b->start_index;
        });

        foreach ($textContexts as $context) {
            $startIndex = $context->start_index;
            $endIndex   = $context->end_index;

            // Text before highlight
            $markedText .= e(mb_substr(
                $textContent,
                $lastIndex,
                $startIndex - $lastIndex
            ));

            // Highlighted text
            $markedText .= '<span style="background-color: orange; padding:2px;">'
                . e(mb_substr($textContent, $startIndex, $endIndex - $startIndex))
                . '</span>';

            $lastIndex = $endIndex;
        }

        $markedText .= e(mb_substr($textContent, $lastIndex));

        return new HtmlString(nl2br($markedText));
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
        if ($collection->datasets->count() > 0 || $collection->savedSearches->count() > 0) {
            $zip = new \ZipArchive();
            if (empty($path)) {
                // Create a temporary file for the archive
                $zipFile = tempnam(sys_get_temp_dir(), 'GHAP');
            } else {
                $zipFile = $path;
            }      
            if ($zipFile && $zip->open($zipFile, \ZipArchive::CREATE) === TRUE) {
                $metadata = self::generateCollectionMetadata($collection);
                $zip->addFromString('ro-crate-metadata.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $zip->addFromString('ro-crate-preview.html', self::generateCollectionHtml($metadata));

                foreach ($collection->datasets as $dataset) {
                    $directory = self::getDatasetDirectoryName($dataset);
                    $zip->addEmptyDir($directory);
                    $zip->addFromString($directory . '/' . self::getDatasetExportFileName($dataset, 'csv'), $dataset->csv());
                    $zip->addFromString($directory . '/' . self::getDatasetExportFileName($dataset, 'kml'), $dataset->kml());

                    //Remove escape slashes from GeoJSON
                    $formattedGeoJSON = json_encode( json_decode($dataset->json()) , JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);  
                    $zip->addFromString($directory . '/' . self::getDatasetExportFileName($dataset, 'json'), $formattedGeoJSON );
                }

                //Add saved search files to zip
                // Creating fake request to simulate an internal request to GazetteerController's search function
                foreach ($collection->savedSearches as $savedSearch) {

                    $directory = self::getSavedSearchDirectoryName($savedSearch);
                    $zip->addEmptyDir($directory);

                    // TO BE REFACTORED
                    // The current searching functions are tightly coupled with the search controller. To reuse the search
                    // functions, it has to create the dummy requests to the controller.
                    // Ideally, the search functions should be refactored into independent modules which can be resued easily.
                                     
                    //Csv
                    // &format=csvContent parameters returns the content of the csv as string by stream_get_contents()
                    $url = url($savedSearch->query . '&format=csvContent');
                    parse_str(parse_url($url, PHP_URL_QUERY), $queryParameters);
                    $fakeRequest = Request::create('/dummy-path', 'GET', $queryParameters);
                    $res = (new GazetteerController())->search($fakeRequest);
                    // Check if $res is a response object and extract content
                    if($res instanceof \Illuminate\Http\Response) {
                        $content = $res->getContent();
                    } else {
                        $content = $res;
                    }
                    $zip->addFromString($directory . '/' . self::getSavedSearchExportFileName($savedSearch, 'csv'), $content);

                    //kml
                    $url = url($savedSearch->query . '&format=kml');
                    parse_str(parse_url($url, PHP_URL_QUERY), $queryParameters);
                    $fakeRequest = Request::create('/dummy-path', 'GET', $queryParameters);
                    $res = (new GazetteerController())->search($fakeRequest);
                    // Check if $res is a response object and extract content
                    if($res instanceof \Illuminate\Http\Response) {
                        $content = $res->getContent();
                    } else {
                        $content = $res;
                    }
                    $zip->addFromString($directory . '/' . self::getSavedSearchExportFileName($savedSearch, 'kml'), $content);

                    //json
                    $url = url($savedSearch->query . '&format=json');
                    parse_str(parse_url($url, PHP_URL_QUERY), $queryParameters);
                    $fakeRequest = Request::create('/dummy-path', 'GET', $queryParameters);
                    $res = (new GazetteerController())->search($fakeRequest);
                    // Check if $res is a response object and extract content
                    if($res instanceof \Illuminate\Http\Response) {
                        $content = json_encode(json_decode($res->getContent()),JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ;
                    } else {
                        $content = $res;
                    }
                    $zip->addFromString($directory . '/' . self::getSavedSearchExportFileName($savedSearch, 'json'), $content);
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
        $rootEntity->set('datePublished', $collection->created_at->toDateString());
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
            $rootEntity->set('language', $collection->language);
        }
        if (!empty($collection->license)) {
            $license = new DataEntity('CreativeWork');
            $license->set('name', $collection->license);
            $rootEntity->set('license', $license);
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
        $fileIDs = [];
        foreach ($collection->datasets as $dataset) {
            $directory = self::getDatasetDirectoryName($dataset);
            $datasetDataEntity = self::createDatasetDataEntity($dataset, $directory);
            $rootEntity->addPart($datasetDataEntity);
            // Add file IDs to list.
            $files = $datasetDataEntity->getParts();
            foreach ($files as $file) {
                $fileIDs[] = ['@id' => $file->id()];
            }
        }

        // Handling Saved Searches
        foreach ($collection->savedSearches as $savedSearch) {
            $directory = self::getSavedSearchDirectoryName($savedSearch);
            $savedSearchDataEntity = self::createSavedSearchDataEntity($savedSearch, $directory);
            $rootEntity->addPart($savedSearchDataEntity);
            // Add file IDs to list.
            $files = $savedSearchDataEntity->getParts();
            foreach ($files as $file) {
                $fileIDs[] = ['@id' => $file->id()];
            }
        }

        $metadata->addDataEntity($rootEntity);

        // Add application data entities.
        $actionEntity = self::createApplicationDataEntity($fileIDs);
        $metadata->addDataEntity($actionEntity);

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
            $zip->addFromString('ro-crate-metadata.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $zip->addFromString('ro-crate-preview.html', self::generateSearchHtml($metadata));
            $zip->addFromString(self::getSearchExportFileName('csv'), FileFormatter::toCSVContent($results));
            $zip->addFromString(self::getSearchExportFileName('kml'), FileFormatter::toKML2($results, $parameters));

            //Remove escape slashes from GeoJSON
            $formattedGeoJSON = json_encode( json_decode(FileFormatter::toGeoJSON($results)) , JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);  
            $zip->addFromString(self::getSearchExportFileName('json'), $formattedGeoJSON);
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
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
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

        // Add application data entity.
        $fileIDs = [
            ['@id' => $csvEntity->id()],
            ['@id' => $kmlEntity->id()],
            ['@id' => $jsonEntity->id()],
        ];
        $actionEntity = self::createApplicationDataEntity($fileIDs);
        $metadata->addDataEntity($actionEntity);

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
            $zip->addFromString('ro-crate-metadata.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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
     * Get the directory name of a saved search.
     *
     * @param SavedSearch $savedSearch
     * @return string
     */
    public static function getSavedSearchDirectoryName(SavedSearch $savedSearch)
    {
        return "export-saved-search-{$savedSearch->id}";
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
     * Get the export file name of a saved search.
     *
     * @param SavedSearch $savedSearch
     * @param string $extension
     *   The file extension.
     * @return string
     */
    public static function getSavedSearchExportFileName(SavedSearch $savedSearch, $extension)
    {
        return "GHAPSearchResult_{$savedSearch->id}.{$extension}";
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
