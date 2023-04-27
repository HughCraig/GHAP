<?php

namespace TLCMap\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use TLCMap\Http\Helpers\GeneralFunctions;
use TLCMap\Models\Collection;
use TLCMap\Models\Dataset;
use TLCMap\Models\SubjectKeyword;
use TLCMap\ROCrate\ROCrateGenerator;

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
        return view('ws.ghap.publiccollections', ['collections' => $collections]);
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
        return view('ws.ghap.publiccollection', [
            'collection' => $collection,
            'datasets' => $datasets,
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
            // If the collection is not found or private, return 404.
            abort(404);
        }
        $result = $collection->toArray();
        $result['url'] = url("publiccollections/{$collection->id}");
        $datasets = $collection->datasets()->where('public', true)->get();
        if (!empty($datasets) && count($datasets) > 0) {
            $result['datasets'] = [];
            foreach ($datasets as $dataset) {
                $result['datasets'][] = [
                    'id' => $dataset->id,
                    'name' => $dataset->name,
                    'description' => $dataset->description,
                    'warning' => $dataset->warning,
                    'linkback' => $dataset->linkback,
                    'url' => url("publicdatasets/{$dataset->id}"),
                    'jsonURL' => url("publicdatasets/{$dataset->id}/json"),
                ];
            }
        }
        return response()->json($result);
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
                $subjectkeyword = SubjectKeyword::firstOrCreate(['keyword' => strtolower($tag)]);
                array_push($keywords, $subjectkeyword);
            }
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
        return view('user.userviewcollection', ['collection' => $collection]);
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
                $subjectkeyword = SubjectKeyword::firstOrCreate(['keyword' => strtolower($tag)]);
                array_push($keywords, $subjectkeyword);
            }
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
            'size' => count($dataset->dataitems),
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
            'entries' => count($dataset->dataitems),
            'created_at' => \Carbon\Carbon::parse($dataset->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => \Carbon\Carbon::parse($dataset->updated_at)->format('Y-m-d H:i:s'),
            'url' => url("publicdatasets/{$dataset->id}"),
        ]);
    }
}
