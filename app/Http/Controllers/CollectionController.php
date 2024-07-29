<?php

namespace TLCMap\Http\Controllers;

use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use TLCMap\Http\Controllers\GazetteerController;
use TLCMap\Http\Helpers\GeneralFunctions;
use TLCMap\Http\Helpers\HtmlFilter;
use TLCMap\Models\Collection;
use TLCMap\Models\SavedSearch;
use TLCMap\Models\Dataset;
use TLCMap\Models\SubjectKeyword;
use TLCMap\ROCrate\ROCrateGenerator;
use TLCMap\ViewConfig\CollectionConfig;
use TLCMap\ViewConfig\DatasetConfig;
use TLCMap\ViewConfig\GhapConfig;
use Illuminate\Support\Facades\Storage;
use Response;

class CollectionController extends Controller
{
    /**
     * Public collection list view.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function viewPublicCollections(Request $request)
    {
        $collections = Collection::where('public', 1)->get();
        $collectionsHasMobInfo = [];

        foreach ($collections as $collection) { // Only show public datasets.

            [
                'datasetsHasMobInfo' => $dsHasMobInfo,
                'savedSearchesHasMobInfo' => $ssHasMobInfo,
                'collectionHasMobInfo' => $collHasMobInfo
            ] = self::getPublicCollectionMobilityInfo($request, $collection);

            array_push($collectionsHasMobInfo, $collHasMobInfo);
        }

        return view('ws.ghap.publiccollections', [
            'collections' => $collections,
            'collectionsHasMobInfo' => $collectionsHasMobInfo
        ]);
    }

    /**
     * Public view of a single collection.
     *
     * @param Request $request
     * @param $collectionID
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function viewPublicCollection(Request $request, $collectionID)
    {
        $collection = Collection::where([
            'public' => 1,
            'id' => $collectionID,
        ])->first();
        if (!$collection) {
            abort(404);
        }
        // Only show public datasets.
        $datasets = $collection->datasets()->where('public', 1)->get();

        [
            'datasetsHasMobInfo' => $dsHasMobInfo,
            'savedSearchesHasMobInfo' => $ssHasMobInfo,
            'collectionHasMobInfo' => $collHasMobInfo
        ] = self::getPublicCollectionMobilityInfo($request, $collection);

        return view('ws.ghap.publiccollection', [
            'collection' => $collection,
            'datasets' => $datasets,
            'collectionHasMobInfo' => $collHasMobInfo,
            'datasetsHasMobInfo' => $dsHasMobInfo,
            'savedSearchesHasMobInfo' => $ssHasMobInfo,
        ]);
    }

    /**
     * JSON view of the public collection.
     *
     * @param Request $request
     * @param int $collectionID
     *   The collection ID.
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewPublicJson(Request $request, $collectionID)
    {
        /**
         * @var Collection $collection
         */
        $collection = Collection::where([
            'id' => $collectionID,
            'public' => true,
        ])->first();
        if (!$collection) {
            return Response::make(Collection::getRestrictedCollectionGeoJSON(), '200', array('Content-Type' => 'application/json'));
        }
        $result = $collection->toArray();
        $result['url'] = url("publiccollections/{$collection->id}");
        $data = [
            'metadata' => $result
        ];

        // Set collection config.
        $collectionConfig = new CollectionConfig();
        $collectionConfig->setInfoTitle($result['name'], $result['url']);
        $collectionConfig->setInfoContent(GhapConfig::createCollectionInfoBlockContent($collection));

        $data['display'] = $collectionConfig->toArray();

        // Get query string.
        $queryString = '';
        if (!empty($request->input('line'))) {
            $queryString = '?line=' . $request->input('line');
        } elseif (!empty($request->input('sort'))) {
            $queryString = '?sort=' . $request->input('sort');
        }

        if (!empty($request->input('mobility'))) {
            $displayMode = $request->input('mobility');
            $queryString = '?mobility=' . $displayMode;
            // check whether each layer/saved search is qualified for mobility/mobility time start/mobility time end JSON request
            [
                'datasetsHasMobInfo' => $dsHasMobInfo,
                'savedSearchesHasMobInfo' => $ssHasMobInfo,
                'collectionHasMobInfo' => $collHasMobInfo
            ] = self::getPublicCollectionMobilityInfo($request, $collection);

            $data['datasets'] = [];
            $datasets = $collection->datasets()->where('public', true)->get();
            if (!empty($datasets) && count($datasets) > 0) {
                foreach ($datasets as $index => $dataset) {
                    // Set dataset config.
                    $datasetConfig = new DatasetConfig();
                    $datasetConfig->enableListPaneColor();
                    $datasetConfig->setListPaneContent(GhapConfig::createDatasetListPaneContent($dataset));
                    $dsQueryString = "";
                    if (isset($dsHasMobInfo[$index])) {
                        switch ($displayMode) {
                            case 'route':
                                if ($dsHasMobInfo[$index]['default']) {
                                    $dsQueryString = $queryString;
                                }
                                break;
                            case 'timestart':
                                if ($dsHasMobInfo[$index]['hasrouteiddatestart']) {
                                    $dsQueryString = $queryString;
                                }
                                break;
                            case 'timeend':
                                if ($dsHasMobInfo[$index]['hasrouteiddateend']) {
                                    $dsQueryString = $queryString;
                                }
                                break;
                        }
                    }

                    $data['datasets'][] = [
                        'name' => $dataset->name,
                        'jsonURL' => url("layers/{$dataset->id}/json{$dsQueryString}"),
                        'display' => $datasetConfig->toArray(),
                    ];
                }
            }
            $savedSearches = $collection->savedSearches;

            if ($savedSearches && count($savedSearches) > 0) {
                foreach ($savedSearches as $index => $savedSearch) {
                    // Set saved search config.
                    $savedSearchConfig = new DatasetConfig();
                    $savedSearchConfig->enableListPaneColor();
                    $savedSearchConfig->setListPaneContent(GhapConfig::createSavedSearchListPaneContent($savedSearch));
                    $ssQueryString = "";
                    if (isset($ssHasMobInfo[$index])) {
                        switch ($displayMode) {
                            case 'route':
                                if ($ssHasMobInfo[$index]['default']) {
                                    $ssQueryString = substr($queryString, 1);
                                }
                                break;
                            case 'timestart':
                                if ($ssHasMobInfo[$index]['hasrouteiddatestart']) {
                                    $ssQueryString = substr($queryString, 1);
                                }
                                break;
                            case 'timeend':
                                if ($ssHasMobInfo[$index]['hasrouteiddateend']) {
                                    $ssQueryString = substr($queryString, 1);
                                }
                                break;
                        }
                    }
                    $data['datasets'][] = [
                        'name' => $savedSearch->name,
                        'jsonURL' => url("/places" . $savedSearch->query . '&format=json&mapping=true' . '&' . $ssQueryString . "&requestFrom=multilayers"),
                        'display' => $savedSearchConfig->toArray(),
                    ];
                }
            }
        } else {
            $data['datasets'] = [];
            $datasets = $collection->datasets()->where('public', true)->get();
            if (!empty($datasets) && count($datasets) > 0) {
                foreach ($datasets as $dataset) {
                    // Set dataset config.
                    $datasetConfig = new DatasetConfig();
                    $datasetConfig->enableListPaneColor();
                    $datasetConfig->setListPaneContent(GhapConfig::createDatasetListPaneContent($dataset));

                    $data['datasets'][] = [
                        'name' => $dataset->name,
                        'jsonURL' => url("layers/{$dataset->id}/json{$queryString}"),
                        'display' => $datasetConfig->toArray(),
                    ];
                }
            }
            $savedSearches = $collection->savedSearches;
            if ($savedSearches && count($savedSearches) > 0) {
                foreach ($savedSearches as $savedSearch) {
                    $data['datasets'][] = [
                        'name' => $savedSearch->name,
                        'jsonURL' => url("/places" . $savedSearch->query . '&format=json' . '&' . substr($queryString, 1)),
                    ];
                }
            }
        }

        if (count($data['datasets']) == 0) {
            $data['metadata']['warning'] .=  "<p>0 results found</p>";
            $data['display']['info']['content'] .= "<div class=\"warning-message\"><p>0 results found</p></div>";
        }

        return response()->json($data);
    }

    /**
     * Get mobility information for the collection.
     *
     * This function retrieves and processes mobility information for a given collection,
     * including its public datasets and saved searches. It uses session storage to cache
     * the results for improved performance.
     *
     * @param Request $request The current HTTP request
     * @param Collection $collection The collection to process
     *
     * @return array An associative array containing:
     *               - 'datasetsHasMobInfo': Array of mobility info for public datasets
     *               - 'savedSearchesHasMobInfo': Array of mobility info for saved searches
     *               - 'collectionHasMobInfo': Array of aggregated mobility info for the collection
     */
    private static function getPublicCollectionMobilityInfo(Request $request, $collection)
    {
        $collectionID = $collection->id;
        $sessionKey = "collection_{$collectionID}_mob_info";
        if (
            isset($sessionData['datasetsHasMobInfo']) &&
            isset($sessionData['savedSearchesHasMobInfo']) &&
            isset($sessionData['collectionHasMobInfo'])
        ) {
            $dsHasMobInfo = $sessionData['datasetsHasMobInfo'];
            $ssHasMobInfo = $sessionData['savedSearchesHasMobInfo'];
            $collHasMobInfo = $sessionData['collectionHasMobInfo'];
        } else {
            // Clear cache
            $request->session()->forget($sessionKey);
            // Get mobility existence of datasets
            $datasets = $collection->datasets()->where('public', 1)->get();
            $dsHasMobInfo = [];
            foreach ($datasets as $ds) {
                array_push($dsHasMobInfo, $ds->getMappingMobilityInfo());
            }

            // Get mobility existence of saved searches
            $savedSearches = $collection->savedSearches;
            $ssHasMobInfo = [];
            foreach ($savedSearches as $ss) {
                $queryParameters = [];
                parse_str(parse_url($ss->query, PHP_URL_QUERY), $queryParameters);
                $queryParameters['savedSearch'] = true;
                $ssResults = (new GazetteerController())->search(new Request($queryParameters));
                array_push($ssHasMobInfo, $ssResults['hasmobinfo']);
            }

            // Get mobility existence of collections
            $collHasMobInfo = [
                'default' => false,
                'hasrouteiddatestart' => false,
                'hasrouteiddateend' => false
            ];
            foreach ($collHasMobInfo as $key => &$value) {
                foreach (array_merge($dsHasMobInfo, $ssHasMobInfo) as $mobInfo) {
                    if (isset($mobInfo[$key]) && $mobInfo[$key] === true) {
                        $value = true;
                        break;
                    }
                }
                if ($value === true) {
                    continue;
                }
            }

            $request->session()->put($sessionKey, [
                'datasetsHasMobInfo' => $dsHasMobInfo,
                'savedSearchesHasMobInfo' => $ssHasMobInfo,
                'collectionHasMobInfo' => $collHasMobInfo
            ]);
        }


        return [
            'datasetsHasMobInfo' => $dsHasMobInfo,
            'savedSearchesHasMobInfo' => $ssHasMobInfo,
            'collectionHasMobInfo' => $collHasMobInfo
        ];
    }

    /**
     * Download the RO-Crate for a public collection.
     *
     * @param Request $request
     * @param int $collectionID
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|null
     */
    public function downloadPublicROCrate(Request $request, $collectionID)
    {
        $collection = Collection::where([
            'id' => $collectionID,
            'public' => true,
        ])->first();
        if (!$collection) {
            abort(404);
        }
        $crate = ROCrateGenerator::generateCollectionCrate($collection);
        if ($crate) {
            $timestamp = date("YmdHis");
            return response()->download($crate, "ghap-ro-crate-multilayer-{$collection->id}-{$timestamp}.zip")->deleteFileAfterSend();
        }
        return null;
    }

    /**
     * Download the RO-Crate as the owner of a collection.
     *
     * @param Request $request
     * @param int $collectionID
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|null
     */
    public function downloadPrivateROCrate(Request $request, $collectionID)
    {
        $user = Auth::user();
        $collection = $user->collections()->find($collectionID);
        if (!$collection) {
            abort(404);
        }
        $crate = ROCrateGenerator::generateCollectionCrate($collection);
        if ($crate) {
            $timestamp = date("YmdHis");
            return response()->download($crate, "ghap-ro-crate-multilayer-{$collection->id}-{$timestamp}.zip")->deleteFileAfterSend();
        }
        return null;
    }

    /**
     * View all collections of the current user.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function viewMyCollections(Request $request)
    {
        $user = Auth::user();
        $collections = Collection::where('owner', $user->id)->get();
        return view('user.usercollections', [
            'collections' => $collections,
            'user' => $user,
        ]);
    }

    /**
     * Page of creating new collection.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function newCollection(Request $request)
    {
        return view('user.usernewcollection');
    }

    /**
     * Create a new collection.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function createNewCollection(Request $request)
    {
        $user = auth()->user();

        //ensure the required fields are present
        $collectionName = $request->name;
        $description = $request->description;

        $isValid = true;
        if (!$collectionName || !$description) {
            $isValid = false;
        }

        //Check temporalfrom and temporalto is valid, continue if it is or reject if it is not
        $temporalfrom = $request->temporalfrom;
        if (isset($temporalfrom)) {
            $temporalfrom = GeneralFunctions::dateMatchesRegexAndConvertString($temporalfrom);
            if (!$temporalfrom) {
                $isValid = false;
            }
        }

        $temporalto = $request->temporalto;
        if (isset($temporalto)) {
            $temporalto = GeneralFunctions::dateMatchesRegexAndConvertString($temporalto);
            if (!$temporalto) {
                $isValid = false;
            }
        }

        if (!$isValid) {
            return redirect('myprofile/mycollections');
        }

        $keywords = [];
        if (!empty($request->tags)) {
            $tags = explode(",,;", $request->tags);
            //for each tag in the subjects array(?), get or create a new subjectkeyword
            foreach ($tags as $tag) {
                $subjectkeyword = SubjectKeyword::firstOrCreate(['keyword' => $tag]);
                array_push($keywords, $subjectkeyword);
            }
        }

        $filename = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            //Validate image file.
            if (!GeneralFunctions::validateUserUploadImage($image)) {
                return response()->json(['error' => 'Image must be a valid image file type and size.'], 422);
            }
            $filename = time() . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images', $image, $filename);
        }

        $collection = Collection::create([
            'name' => $collectionName,
            'description' => $description,
            'owner' => $user->id,
            'creator' => $request->creator,
            'public' => $request->public,
            'publisher' => $request->publisher,
            'contact' => $request->contact,
            'citation' => $request->citation,
            'doi' => $request->doi,
            'source_url' => $request->source_url,
            'linkback' => $request->linkback,
            'latitude_from' => $request->latitudefrom,
            'longitude_from' => $request->longitudefrom,
            'latitude_to' => $request->latitudeto,
            'longitude_to' => $request->longitudeto,
            'language' => $request->language,
            'license' => $request->license,
            'rights' => $request->rights,
            'temporal_from' => $temporalfrom,
            'temporal_to' => $temporalto,
            'created' => $request->created,
            'warning' => $request->warning,
            'image_path' => $filename
        ]);

        foreach ($keywords as $keyword) {
            $collection->subjectKeywords()->attach(['subject_keyword_id' => $keyword->id]);
        }

        return redirect('myprofile/mycollections/' . $collection->id);
    }

    /**
     * View my collection page.
     *
     * @param Request $request
     * @param $collectionID
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function viewMyCollection(Request $request, $collectionID)
    {
        $user = Auth::user();
        $collection = $user->collections()->find($collectionID);
        if (!$collection) {
            return redirect('myprofile/mycollections/');
        }

        // Get all datasets in the collection
        /**
         * Ivy's note:
         *    Only the public dataset can use the mappint functionality. as mobility information is collected for dataset mapping,
         *    public datasets are called here.
         **/
        [
            'datasetsHasMobInfo' => $dsHasMobInfo,
            'savedSearchesHasMobInfo' => $ssHasMobInfo,
            'collectionHasMobInfo' => $collHasMobInfo
        ] = self::getPublicCollectionMobilityInfo($request, $collection);

        return view('user.userviewcollection', [
            'collection' => $collection,
            'collectionHasMobInfo' => $collHasMobInfo,
            'datasetsHasMobInfo' => $dsHasMobInfo,
            'savedSearchesHasMobInfo' => $ssHasMobInfo,
        ]);
    }

    /**
     * Update a collection.
     *
     * @param Request $request
     * @param int $collectionID
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function editCollection(Request $request, $collectionID)
    {
        $user = auth()->user();

        //ensure the required fields are present
        $collectionName = $request->name;
        $description = $request->description;

        $isValid = true;
        if (!$collectionName || !$description) {
            $isValid = false;
        }

        //Check temporalfrom and temporalto is valid, continue if it is or reject if it is not
        $temporalfrom = $request->temporalfrom;
        if (isset($temporalfrom)) {
            $temporalfrom = GeneralFunctions::dateMatchesRegexAndConvertString($temporalfrom);
            if (!$temporalfrom) {
                $isValid = false;
            }
        }

        $temporalto = $request->temporalto;
        if (isset($temporalto)) {
            $temporalto = GeneralFunctions::dateMatchesRegexAndConvertString($temporalto);
            if (!$temporalto) {
                $isValid = false;
            }
        }

        // Check wether the collection ID provided is valid.
        $collection = $user->collections()->find($collectionID);
        if (!$collection) {
            $isValid = false;
        }

        if (!$isValid) {
            return redirect('myprofile/mycollections');
        }

        $keywords = [];
        if (!empty($request->tags)) {
            $tags = explode(",,;", $request->tags);
            //for each tag in the subjects array(?), get or create a new subjectkeyword
            foreach ($tags as $tag) {
                $subjectkeyword = SubjectKeyword::firstOrCreate(['keyword' => $tag]);
                array_push($keywords, $subjectkeyword);
            }
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            //Validate image file.
            if (!GeneralFunctions::validateUserUploadImage($image)) {
                return response()->json(['error' => 'Image must be a valid image file type and size.'], 422);
            }
            // Delete old image.
            if ($collection->image_path && Storage::disk('public')->exists('images/' . $collection->image_path)) {
                Storage::disk('public')->delete('images/' . $collection->image_path);
            }
            $filename = time() . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images', $image, $filename);
            $collection->image_path = $filename;
        }

        $collection->fill([
            'name' => $collectionName,
            'description' => $description,
            'owner' => $user->id,
            'creator' => $request->creator,
            'public' => $request->public,
            'publisher' => $request->publisher,
            'contact' => $request->contact,
            'citation' => $request->citation,
            'doi' => $request->doi,
            'source_url' => $request->source_url,
            'linkback' => $request->linkback,
            'latitude_from' => $request->latitudefrom,
            'longitude_from' => $request->longitudefrom,
            'latitude_to' => $request->latitudeto,
            'longitude_to' => $request->longitudeto,
            'language' => $request->language,
            'license' => $request->license,
            'rights' => $request->rights,
            'temporal_from' => $temporalfrom,
            'temporal_to' => $temporalto,
            'created' => $request->created,
            'warning' => $request->warning,
            'image_path' => $collection->image_path
        ]);
        $collection->save();

        // Re-attach all keywords.
        $collection->subjectKeywords()->detach();
        foreach ($keywords as $keyword) {
            $collection->subjectKeywords()->attach(['subject_keyword_id' => $keyword->id]);
        }

        return redirect('myprofile/mycollections/' . $collection->id);
    }

    /**
     * Ajax service to delete a collection.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function ajaxDeleteCollection(Request $request)
    {
        $collectionID = $request->id;
        // Respond 400 bad request if the request data is incomplete.
        if (!isset($collectionID)) {
            abort(400);
        }
        /**
         * @var Collection $collection
         */
        $collection = Collection::where([
            'id' => $collectionID,
            'owner' => Auth::user()->id,
        ])->first();
        // Respond 404 not found if the collection is not found.
        if (!$collection) {
            abort(404);
        }
        // Detach relationships.
        $collection->subjectKeywords()->detach();
        $collection->datasets()->detach();
        // Delete the collection.
        $collection->delete();
        return response()->json();
    }

    /**
     * Ajax service to remove a dataset from a collection.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxRemoveCollectionDataset(Request $request)
    {
        $collectionID = $request->id;
        $datasetID = $request->datasetID;
        // Respond 400 bad request if the request data is incomplete.
        if (!isset($collectionID) || !isset($datasetID)) {
            abort(400);
        }
        /**
         * @var Collection $collection
         */
        $collection = Collection::where([
            'id' => $collectionID,
            'owner' => Auth::user()->id,
        ])->first();
        // Respond 404 not found if the collection is not found.
        if (!$collection) {
            abort(404);
        }
        $collection->datasets()->detach($datasetID);
        return response()->json();
    }

    /**
     * Ajax service to add a dataset to a collection.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxAddCollectionDataset(Request $request)
    {
        $collectionID = $request->id;
        $datasetID = $request->datasetID;
        // Respond 400 bad request if the request data is incomplete.
        if (!isset($collectionID) || !isset($datasetID)) {
            abort(400);
        }
        /**
         * @var Collection $collection
         */
        $collection = Collection::where([
            'id' => $collectionID,
            'owner' => Auth::user()->id,
        ])->first();
        // Respond 404 not found if the collection is not found.
        if (!$collection) {
            abort(404);
        }
        /**
         * @var Dataset $dataset
         */
        $dataset = Dataset::find($datasetID);
        if (!$dataset) {
            // Respond 404 not found if the dataset is not found.
            abort(404);
        } else {
            // Respond 403 forbidden if trying to add a private dataset which is not owned by the current user.
            if (!$dataset->public && $dataset->owner() !== Auth::user()->id) {
                abort(403);
            }
        }
        // Check whether the dataset already exists in the collection.
        $existingDataset = $collection->datasets()->find($datasetID);
        if (empty($existingDataset)) {
            $collection->datasets()->attach($datasetID);
        }
        $response = [
            'id' => $dataset->id,
            'name' => $dataset->name,
            'size' => $dataset->getDataitemsCount(),
            'type' => $dataset->recordtype->type,
            'warning' => $dataset->warning,
            'contributor' => $dataset->ownerName() . ($dataset->owner() === Auth::user()->id ? ' (You) ' : ''),
            'visibility' => $dataset->public ? 'Public' : 'Private',
            'created' => \Carbon\Carbon::parse($dataset->created_at)->format('Y-m-d H:i:s'),
            'updated' => \Carbon\Carbon::parse($dataset->updated_at)->format('Y-m-d H:i:s'),
            'urlRoot' => url('publicdatasets'),
            'collectionID' => $collectionID,
        ];
        return response()->json($response);
    }

    /**
     * Ajax service to get the select options when adding a public dataset to a collection.
     *
     * @param Request $request
     * @param int $collectionID
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxGetPublicDatasetOptions(Request $request, $collectionID)
    {
        $options = [];
        $collection = Collection::find($collectionID);
        if (!$collection) {
            abort(404);
        }
        $exitingDatasetIDs = $collection->datasets()->pluck('id')->toArray();
        $addableDatasets = Dataset::where('public', 1)->whereNotIn('id', $exitingDatasetIDs)->get();
        foreach ($addableDatasets as $dataset) {
            $options[] = [
                'id' => $dataset->id,
                'name' => $dataset->name,
            ];
        }
        // Sort by name.
        usort($options, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        return response()->json($options);
    }

    /**
     * Ajax service to get the select options when adding a user dataset to a collection.
     *
     * @param Request $request
     * @param int $collectionID
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxGetUserDatasetOptions(Request $request, $collectionID)
    {
        $options = [];
        $collection = Collection::find($collectionID);
        if (!$collection) {
            abort(404);
        }
        $exitingDatasetIDs = $collection->datasets()->pluck('id')->toArray();
        $addableDatasets = Auth::user()->datasets()->whereNotIn('dataset.id', $exitingDatasetIDs)->get();
        foreach ($addableDatasets as $dataset) {
            $options[] = [
                'id' => $dataset->id,
                'name' => $dataset->name,
            ];
        }
        // Sort by name.
        usort($options, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        return response()->json($options);
    }

    /**
     * Ajax service to get the summary of a dataset to be added to a collection.
     *
     * @param Request $request
     * @param int $collectionID
     * @param int $datasetID
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxGetDatasetInfo(Request $request, $collectionID, $datasetID)
    {
        $collection = Collection::find($collectionID);
        if (!$collection) {
            abort(404);
        }
        $dataset = Dataset::find($datasetID);
        // Valid the dataset.
        if (!$dataset || (!$dataset->public && $dataset->owner() !== Auth::user()->id)) {
            abort(404);
        }
        return response()->json([
            'id' => $dataset->id,
            'name' => $dataset->name,
            'description' => $dataset->description,
            'warning' => $dataset->warning,
            'type' => $dataset->recordtype->type,
            'public' => $dataset->public,
            'ownerName' => $dataset->ownerName(),
            'allowanps' => $dataset->allowanps,
            'entries' => $dataset->getDataitemsCount(),
            'created_at' => \Carbon\Carbon::parse($dataset->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => \Carbon\Carbon::parse($dataset->updated_at)->format('Y-m-d H:i:s'),
            'url' => url("publicdatasets/{$dataset->id}"),
        ]);
    }

    /**
     * Ajax service to get the select options for the saved searches of the current user
     * that haven't been added to the collection with specific id.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxGetUserSavedSearch(Request $request)
    {
        $user = auth()->user();

        $collectionID = $request->collectionID;
        if (!isset($collectionID)) {
            abort(400);
        }

        // Get the saved searches that are already linked to this collection.
        $linkedSavedSearches = Collection::findOrFail($collectionID)->savedSearches()->pluck('saved_search.id')->toArray();;

        // Fetch saved searches not in the linked list.
        $searches = SavedSearch::where('user_id', $user->id)
            ->whereNotIn('id', $linkedSavedSearches)
            ->get();

        return response()->json($searches);
    }

    /**
     * Ajax service to to add the saved search to a collection.
     * Add relationship to table collection_saved_search
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxAddSavedSearch(Request $request)
    {
        $collectionID = $request->collectionID;
        $savedSearchID = $request->savedSearchID;
        if (!isset($collectionID) || !isset($savedSearchID)) {
            abort(400);
        }

        try {
            $collection = Collection::findOrFail($collectionID);
            $savedSearch = SavedSearch::findOrFail($savedSearchID);

            // Check if the saved search already exists in this collection
            if ($collection->savedSearches->contains($savedSearch->id)) {
                return response()->json('Selected search already exists in this collection.', 409); // 409 Conflict
            }

            $collection->savedSearches()->attach($savedSearch->id);

            return response()->json('Saved search successfully added to collection.', 200);
        } catch (\Exception $e) {
            return response()->json('Error adding saved search to collection.', 500);
        }
    }

    /**
     * Ajax service to remove a saved search from a collection.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxRemoveCollectionSavedSearch(Request $request)
    {
        $collectionID = $request->collectionID;
        $savedSearchID = $request->savedSearchID;
        // Check if both collectionID and savedSearchID are provided in the request
        if (!isset($collectionID) || !isset($savedSearchID)) {
            abort(400);
        }

        $collection = Collection::where([
            'id' => $collectionID,
            'owner' => Auth::user()->id,
        ])->first();
        // Respond 404 not found if the collection is not found.
        if (!$collection) {
            abort(404);
        }

        // Check if the saved search actually exists in this collection before attempting to remove
        if (!$collection->savedSearches->contains($savedSearchID)) {
            return response()->json('Saved search does not exist in this collection.', 404);
        }

        // Remove the saved search from the collection
        $collection->savedSearches()->detach($savedSearchID);

        return response()->json('Saved search removed from collection successfully', 200);
    }
}
