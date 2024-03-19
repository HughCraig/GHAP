<?php

namespace TLCMap\Http\Controllers;

use Illuminate\Support\Collection;
use TLCMap\Http\Helpers\UID;
use TLCMap\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Response;
use DOMDocument;
use TLCMap\ROCrate\ROCrateGenerator;
use TLCMap\Http\Helpers\GeneralFunctions;


class DatasetController extends Controller
{
    /********************************/
    /*   PUBLIC DATASET FUNCTIONS   */
    /********************************/

    /**
     * View all public datasets
     * @return view with all returned datasets
     */
    public function viewPublicDatasets(Request $request)
    {
        $datasets = Dataset::where('public', 1)->get();
        return view('ws.ghap.publicdatasets', ['datasets' => $datasets]);
    }

    /**
     * View a specific public dataset by id, if it exists and is public
     * If dataset does not exist with this id OR it is not public, @return redirect to viewPublicDatasets
     * else @return view with this dataset
     *
     */
    public function viewPublicDataset(Request $request, int $id)
    {
        $ds = Dataset::with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->where(['public' => 1, 'id' => $id])->first(); // get this dataset by id if it is also public
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return view('ws.ghap.publicdataset', ['ds' => $ds]); // if found return it with the next view
    }

    /**
     * Displays basic statistics for a public dataset identified by its ID.
     * 
     * @return view with dataset's basic statistics or redirect if dataset not found
     */
    public function viewPublicDatasetBasicStatistics(Request $request, int $id)
    {
        Log::info('viewPublicDatasetBasicStatistics');
        $ds = Dataset::where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return view('statistic.basicstatistics', ['ds' => $ds , 'statistic' => $ds->getBasicStatistics() ]); 
    }

    /**
     * Displays basic statistics for a private dataset identified by its ID.
     * Check ownership and redirects if the specified dataset is not found or not public.
     * @return view with dataset's basic statistics or redirect if dataset not found
     */
    public function viewPrivateDatasetBasicStatistics(Request $request, int $id)
    {
        $user = auth()->user();
        $ds = $user->datasets()->find($id);
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return view('statistic.basicstatistics', ['ds' => $ds , 'statistic' => $ds->getBasicStatistics() ]); 
    }

    /**
     * Returns a JSON representation of basic statistics for a specified public dataset.
     * Redirects if the specified dataset is not found or not public.
     * @return JSON response with basic statistics or redirect if dataset not found
     */
    public function viewPublicDatasetBasicStatisticsJSON(Request $request, int $id)
    {

        $ds = Dataset::where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return Response::make($ds->getBasicStatisticsJSON(), '200', array('Content-Type' => 'application/json')); //generate the json response
    }

    /**
     * Download a public dataset as a GeoJSON
     * @return Response with GeoJSON datatype AND download header, or redirect to public datasets page if not found
     */
    public function downloadPublicDatasetBasicStatisticsJSON(Request $request, int $id)
    {
        $ds = Dataset::where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        $filename = GeneralFunctions::replaceWithUnderscores($ds->name . '_BasicStats');
        return Response::make($ds->getBasicStatisticsJSON(), '200', array('Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="' . $filename . '.json"'));
    }

    /**
     * Returns a JSON representation of basic statistics for a specified private dataset.
     * Check ownership and redirects if the specified dataset is not found or not public.
     * @return JSON response with basic statistics or redirect if dataset not found
     */
    public function viewPrivateDatasetBasicStatisticsJSON(Request $request, int $id)
    {
        $user = auth()->user();
        $ds = $user->datasets()->find($id);
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return Response::make($ds->getBasicStatisticsJSON(), '200', array('Content-Type' => 'application/json')); //generate the json response
    }

    /**
     * Download a user owned dataset as geo JSON
     * @return Response with JSON datatype AND download header, or redirect to public datasets page if not found
     */
    public function downloadPrivateDatasetBasicStatisticsJSON(Request $request, int $id)
    {
        $user = auth()->user();
        $ds = $user->datasets()->find($id);
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        $filename = GeneralFunctions::replaceWithUnderscores($ds->name . '_BasicStats');
        return Response::make($ds->getBasicStatisticsJSON(), '200', array('Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="' . $filename . '.json"'));
    }

    /**
     * Displays advanced statistics for a public dataset identified by its ID.
     * Redirects to the list of public datasets if the specified dataset is not found or not public.
     * @return view with dataset's advanced statistics or redirect if dataset not found
     */
    public function viewPublicDatasetAdvancedStatistics(Request $request, int $id)
    {
        $ds = Dataset::where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return view('statistic.advancedstatistics', ['ds' => $ds , 'statistic' => $ds->getAdvancedStatistics() ]); 
    }

    /**
     * Displays advanced statistics for a private dataset identified by its ID.
     * Check ownership and redirects if the specified dataset is not found or not public.
     * @return view with dataset's advanced statistics or redirect if dataset not found
     */
    public function viewPrivateDatasetAdvancedStatistics(Request $request, int $id)
    {
        $user = auth()->user();
        $ds = $user->datasets()->find($id);
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return view('statistic.advancedstatistics', ['ds' => $ds , 'statistic' => $ds->getAdvancedStatistics() ]); 
    }

    /**
     * Displays the cluster analysis results for a public dataset identified by its ID.
     * Redirects to the list of public datasets if the specified dataset is not found or not public.
     * @return view with cluster analysis results or redirect if dataset not found
     */
    public function viewPublicDatasetClusterAnalysis(Request $request, int $id){
        $ds = Dataset::where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return view('statistic.clusteranalysis', ['ds' => $ds]);
    }

    /**
     * Displays the cluster analysis results for a private dataset identified by its ID.
     * Check ownership and redirects if the specified dataset is not found or not public.
     * @return view with cluster analysis results or redirect if dataset not found
     */
    public function viewPrivateDatasetClusterAnalysis(Request $request, int $id){
        $user = auth()->user();
        $ds = $user->datasets()->find($id);
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return view('statistic.clusteranalysis', ['ds' => $ds]);
    }

    /**
     * Returns a JSON representation of DBSCAN cluster analysis results for a specified public dataset.
     * Validates input parameters and redirects if the dataset is not found or not public.
     * @return JSON response with DBSCAN cluster analysis results or redirect if dataset not found
     */
    public function viewPublicDatasetClusterAnalysisDBScanJSON(Request $request, int $id)
    {
        $ds = Dataset::where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers'); // if not found redirect back

        if( $_GET["distance"] == null || $_GET["distance"] < 0 || !is_numeric($_GET["distance"]) ){
            return response()->json(['error' => 'Invalid distance'], 400);
        }

        return Response::make($ds->getClusterAnalysisDBScanJSON(), '200', array('Content-Type' => 'application/json')); //generate the json response
    }

    /**
     * Download a public dataset as DBSCAN cluster analysis geo JSON
     * @return Response with JSON datatype AND download header, or redirect to public datasets page if not found
     */
    public function downloadPublicDatasetClusterAnalysisDBScanJSON(Request $request, int $id)
    {
        $ds = Dataset::where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        $filename = GeneralFunctions::replaceWithUnderscores($ds->name . '_SpatialClusters');
        return Response::make($ds->getClusterAnalysisDBScanJSON(), '200', array('Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="' . $filename . '.json"'));
    }

    /** 
     * Returns a JSON representation of DBSCAN cluster analysis results for a specified private dataset.
     * Validates input parameters and redirects if the dataset is not found or not public.
     * @return JSON response with DBSCAN cluster analysis results or redirect if dataset not found
     */
    public function viewPrivateDatasetClusterAnalysisDBScanJSON(Request $request, int $id)
    {
        $user = auth()->user();
        $ds = $user->datasets()->find($id);
        if (!$ds) return redirect()->route('layers'); // if not found redirect back

        if( $_GET["distance"] == null || $_GET["distance"] < 0 || !is_numeric($_GET["distance"]) ){
            return response()->json(['error' => 'Invalid distance'], 400);
        }

        return Response::make($ds->getClusterAnalysisDBScanJSON(), '200', array('Content-Type' => 'application/json')); //generate the json response
    }

    /**
     * Download a user owned dataset as DBSCAN cluster analysis geo JSON
     * @return Response with JSON datatype AND download header, or redirect to public datasets page if not found
     */
    public function downloadPrivateDatasetClusterAnalysisDBScanJSON(Request $request, int $id)
    {
        $user = auth()->user();
        $ds = $user->datasets()->find($id);
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        $filename = GeneralFunctions::replaceWithUnderscores($ds->name . '_SpatialClusters');
        return Response::make($ds->getClusterAnalysisDBScanJSON(), '200', array('Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="' . $filename . '.json"'));
    }

    /**
     * Returns a JSON representation of Kmeans cluster analysis results for a specified public dataset.
     * Redirects if the specified dataset is not found or not public.
     * @return JSON response with Kmeans cluster analysis results or redirect if dataset not found
     */
    public function viewPublicDatasetClusterAnalysisKmeansJSON(Request $request, int $id)
    {
        $ds = Dataset::where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return Response::make($ds->getClusterAnalysisKmeansJSON(), '200', array('Content-Type' => 'application/json')); //generate the json response
    }

    /**
     * Download a public dataset as Kmeans cluster analysis geo JSON
     * @return Response with JSON datatype AND download header, or redirect to public datasets page if not found
     */
    public function downloadPublicDatasetClusterAnalysisKmeansJSON(Request $request, int $id)
    {
        $ds = Dataset::where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        $filename = GeneralFunctions::replaceWithUnderscores($ds->name . '_SpatialClusters');
        return Response::make($ds->getClusterAnalysisKmeansJSON(), '200', array('Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="' . $filename . '.json"'));
    }

    /**
     * Returns a JSON representation of Kmeans cluster analysis results for a specified private dataset.
     * Redirects if the specified dataset is not found or not public.
     * @return JSON response with Kmeans cluster analysis results or redirect if dataset not found
     */
    public function viewPrivateDatasetClusterAnalysisKmeansJSON(Request $request, int $id)
    {
        $user = auth()->user();
        $ds = $user->datasets()->find($id);
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return Response::make($ds->getClusterAnalysisKmeansJSON(), '200', array('Content-Type' => 'application/json')); //generate the json response
    }

    /**
     * Download a user owned dataset as Kmeans cluster analysis geo JSON
     * @return Response with JSON datatype AND download header, or redirect to public datasets page if not found
     */
    public function downloadPrivateDatasetClusterAnalysisKmeansJSON(Request $request, int $id)
    {
        $user = auth()->user();
        $ds = $user->datasets()->find($id);
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        $filename = GeneralFunctions::replaceWithUnderscores($ds->name . '_SpatialClusters');
        return Response::make($ds->getClusterAnalysisKmeansJSON(), '200', array('Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="' . $filename . '.json"'));
    }

    /**
     * Displays the temporal clustering results for a public dataset identified by its ID.
     * Redirects to the list of public datasets if the specified dataset is not found or not public.
     * @return view with temporal clustering results or redirect if dataset not found
     */
    public function viewPublicDatasetTemporalClustering(Request $request, int $id){
        $ds = Dataset::where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return view('statistic.temporalclustering', ['ds' => $ds]);
    }

    /**
     * Displays the temporal clustering results for a private dataset identified by its ID.
     * Check ownership and redirects if the specified dataset is not found or not public.
     * @return view with temporal clustering results or redirect if dataset not found
     */
    public function viewPrivateDatasetTemporalClustering(Request $request, int $id){
        $user = auth()->user();
        $ds = $user->datasets()->find($id);
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return view('statistic.temporalclustering', ['ds' => $ds]);
    }

    /**
     * Returns a JSON representation of temporal clustering results for a specified public dataset.
     * Redirects if the specified dataset is not found or not public.
     * @return JSON response with temporal clustering results or redirect if dataset not found
     */
    public function viewPublicDatasetTemporalClusteringJSON(Request $request, int $id)
    {
        $ds = Dataset::where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return Response::make($ds->getTemporalClusteringJSON(), '200', array('Content-Type' => 'application/json')); //generate the json response
    }

    /**
     * Download a public dataset as a GeoJSON
     * @return Response with GeoJSON datatype AND download header, or redirect to public datasets page if not found
     */
    public function downloadPublicDatasetTemporalClusteringJSON(Request $request, int $id)
    {
        $ds = Dataset::where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        $filename = GeneralFunctions::replaceWithUnderscores($ds->name . '_TemporalClusters');
        return Response::make($ds->getTemporalClusteringJSON(), '200', array('Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="' . $filename . '.json"'));
    }

    /**
     * Returns a JSON representation of temporal clustering results for a specified private dataset.
     * Check ownership and redirects if the specified dataset is not found or not public.
     * @return JSON response with temporal clustering results or redirect if dataset not found
     */
    public function viewPrivateDatasetTemporalClusteringJSON(Request $request, int $id)
    {
        $user = auth()->user();
        $ds = $user->datasets()->find($id);
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return Response::make($ds->getTemporalClusteringJSON(), '200', array('Content-Type' => 'application/json')); //generate the json response
    }

    /**
     * Download a user owned dataset as temporal clustering geo JSON
     * @return Response with JSON datatype AND download header, or redirect to public datasets page if not found
     */
    public function downloadPrivateDatasetTemporalClusteringJSON(Request $request, int $id)
    {
        $user = auth()->user();
        $ds = $user->datasets()->find($id);
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        $filename = GeneralFunctions::replaceWithUnderscores($ds->name . '_TemporalClusters');
        return Response::make($ds->getTemporalClusteringJSON(), '200', array('Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="' . $filename . '.json"'));
    }

    /**
     * Displays the closeness analysis interface for a public dataset identified by its ID.
     * @return view with closeness analysis interface or redirect if dataset not found
     */
    public function viewPublicDatasetClosenessAnalysis(Request $request, int $id){
        $ds = Dataset::where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers'); // if not found redirect back

        $layers = json_encode(Dataset::getAllPublicLayersAndIDs());
        return view('statistic.closenessanalysis', ['ds' => $ds, 'layers' => $layers]);
    }

    /**
     * Displays the closeness analysis interface for a private dataset identified by its ID.
     * Check ownership and redirects if the specified dataset is not found or not public.
     * @return view with closeness analysis interface or redirect if dataset not found
     */
    public function viewPrivateDatasetClosenessAnalysis(Request $request, int $id){
        $user = auth()->user();
        $ds = $user->datasets()->find($id);
        if (!$ds) return redirect()->route('layers'); // if not found redirect back

        $layers = json_encode(Dataset::getAllPublicLayersAndIDs());
        return view('statistic.closenessanalysis', ['ds' => $ds, 'layers' => $layers]);
    }

    /**
     * Returns a JSON representation of closeness analysis results between the specified dataset and a target layer.
     * 
     * @return JSON response with closeness analysis results or error response if target layer is invalid
     */
    public function viewPublicDatasetClosenessAnalysisJSON(Request $request, int $id)
    {
        $ds = Dataset::where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers'); // if not found redirect back

        if( !$_GET["targetLayer"] ){
            return response()->json(['error' => 'Invalid target layer'], 400);
        }
        $targerDs = Dataset::where(['public' => 1, 'id' => $_GET["targetLayer"]])->first();
        if (!$targerDs) return response()->json(['error' => 'Invalid target layer'], 400);

        return Response::make($ds->getClosenessAnalysisJSON(), '200', array('Content-Type' => 'application/json')); //generate the json response
    }

    /**
     * Returns a JSON representation of Closeness analysis results for a specified private dataset with another public dataset.
     * Check ownership and redirects if the specified dataset is not found or not public.
     * @return JSON response with temporal clustering results or redirect if dataset not found
     */
    public function viewPrivateDatasetClosenessAnalysisJSON(Request $request, int $id)
    {
        $user = auth()->user();
        $ds = $user->datasets()->find($id);
        if (!$ds) return redirect()->route('layers'); // if not found redirect back

        if( !$_GET["targetLayer"] ){
            return response()->json(['error' => 'Invalid target layer'], 400);
        }
        $targerDs = Dataset::where(['public' => 1, 'id' => $_GET["targetLayer"]])->first();
        if (!$targerDs) return response()->json(['error' => 'Invalid target layer'], 400);

        return Response::make($ds->getClosenessAnalysisJSON(), '200', array('Content-Type' => 'application/json')); //generate the json response
    }

    /**
     * View a public dataset as a KML
     * Calls private function to generate KML for a dataset
     * @return Response with KML datatype, or redirect to public datasets page if not found
     */
    public function viewPublicKML(Request $request, int $id)
    {
        $dataset = Dataset::with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->where(['public' => 1, 'id' => $id])->first(); //Get the first dataset with this id that is 'public', if it exists
        if (!$dataset) return redirect()->route('layers'); //redirect if not found (invalid id or not public)
        return Response::make($dataset->kml(), '200', array('Content-Type' => 'text/xml')); //generate the KML response
    }

    /**
     * Download a public dataset as a KML
     * Calls private function to generate KML for a dataset
     * @return Response with KML datatype AND download header, or redirect to public datasets page if not found
     */
    public function downloadPublicKML(Request $request, int $id)
    {
        $dataset = Dataset::with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->where(['public' => 1, 'id' => $id])->first(); //Get the first dataset with this id that is 'public', if it exists
        if (!$dataset) return redirect()->route('layers');
        $filename = 'TLCMLayer_' . $id;
        return Response::make($dataset->kml(), '200', array('Content-Type' => 'text/xml', 'Content-Disposition' => 'attachment; filename="' . $filename . '.kml"'));
    }

    /**
     * View a public dataset as a GeoJSON
     * Calls private function to generate GeoJSON for a dataset
     * @return Response with GeoJSON datatype, or redirect to public datasets page if not found
     */
    public function viewPublicJSON(Request $request, int $id)
    {

        $dataset = Dataset::with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->where(['public' => 1, 'id' => $id])->first(); //Get the first dataset with this id that is 'public', if it exists
        if (!$dataset) return Response::make(Dataset::getRestrictedDatasetGeoJSON(), '200', array('Content-Type' => 'application/json'));
        return Response::make($dataset->json(), '200', array('Content-Type' => 'application/json')); //generate the json response
    }

    /**
     * Download a public dataset as a GeoJSON
     * Calls private function to generate GeoJSON for a dataset
     * @return Response with GeoJSON datatype AND download header, or redirect to public datasets page if not found
     */
    public function downloadPublicJson(Request $request, int $id)
    {
        $dataset = Dataset::with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->where(['public' => 1, 'id' => $id])->first(); //Get the first dataset with this id that is 'public', if it exists
        if (!$dataset) return redirect()->route('layers');
        $filename = 'TLCMLayer_' . $id;
        return Response::make($dataset->json(), '200', array('Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="' . $filename . '.json"'));
    }

    /**
     * View a public dataset as a CSV
     * Calls private function to generate CSV for a dataset
     * @return Response with CSV datatype, or redirect to public datasets page if not found
     */
    public function viewPublicCSV(Request $request, int $id)
    {
        $dataset = Dataset::with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->where(['public' => 1, 'id' => $id])->first(); //Get the first dataset with this id that is 'public', if it exists
        if (!$dataset) return redirect()->route('layers'); //redirect if not found (invalid id or not public)
        return Response::make($dataset->csv(), '200', array('Content-Type' => 'text/csv')); //generate the CSV response
    }

    /**
     * Download a public dataset as a CSV
     * Calls private function to generate CSV for a dataset
     * @return Response with CSV datatype AND download header, or redirect to public datasets page if not found
     */
    public function downloadPublicCSV(Request $request, int $id)
    {
        $dataset = Dataset::with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->where(['public' => 1, 'id' => $id])->first(); //Get the first dataset with this id that is 'public', if it exists
        if (!$dataset) return redirect()->route('layers');
        $filename = 'TLCMLayer_' . $id;
        return Response::make($dataset->csv(), '200', array('Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="' . $filename . '.CSV"'));
    }
    /********************************/
    /*   PRIVATE DATASET FUNCTIONS  */
    /********************************/

    /**
     * View a private user dataset as a KML (if allowed)
     * Calls private function to generate KML for a dataset
     * @return Response with KML datatype or redirect to user datasets page if not found (or not authorized)
     *      - if not logged in the auth middleware will redirect to login (specified at route config)
     */
    public function viewPrivateKML(Request $request, int $id)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->find($id); //Search for this dataset id ONLY within datasets associated with this user
        if (!$dataset) return redirect('myprofile/mydatasets');
        return Response::make($dataset->kml(), '200', array('Content-Type' => 'text/xml'));
    }

    /**
     * Download the private user dataset as a KML (if allowed)
     * Calls private function to generate KML for a dataset
     * @return Response with KML datatype AND download header or redirect to user datasets page if not found (or not authorized)
     *      - if not logged in the auth middleware will redirect to login (specified at route config)
     */
    public function downloadPrivateKML(Request $request, int $id)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->find($id); //Search for this dataset id ONLY within datasets associated with this user
        if (!$dataset) return redirect('myprofile/mydatasets');
        $filename = 'TLCMLayer_' . $id;
        return Response::make($dataset->kml(), '200', array('Content-Type' => 'text/xml', 'Content-Disposition' => 'attachment; filename="' . $filename . '.kml"'));
    }

    /**
     * View a private user dataset as a GeoJSON (if allowed)
     * Calls private function to generate GeoJSON for a dataset
     * @return Response with GeoJSON datatype or redirect to user datasets if not found (or not authorized)
     *      - if not logged in the auth middleware will redirect to login (specified at route config)
     */
    public function viewPrivateJSON(Request $request, int $id)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->find($id); //Search for this dataset id ONLY within datasets associated with this user
        if (!$dataset) return redirect('myprofile/mydatasets');
        return Response::make($dataset->json(), '200', array('Content-Type' => 'application/json'));
    }

    /**
     * Download the private user dataset as a GeoJSON (if allowed)
     * Calls private function to generate GeoJSON for a dataset
     * @return Response with GeoJSON datatype AND download header or redirect to user datasets if not found (or not authorized)
     *      - if not logged in the auth middleware will redirect to login (specified at route config)
     */
    public function downloadPrivateJSON(Request $request, int $id)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->find($id); //Search for this dataset id ONLY within datasets associated with this user
        if (!$dataset) return redirect('myprofile/mydatasets');
        $filename = 'TLCMLayer_' . $id;
        return Response::make($dataset->json(), '200', array('Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="' . $filename . '.json"'));
    }

    public function viewPrivateCSV(Request $request, int $id)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->find($id); //Search for this dataset id ONLY within datasets associated with this user
        if (!$dataset) return redirect('myprofile/mydatasets');
        return Response::make($dataset->csv(), '200', array('Content-Type' => 'text/csv'));
    }

    /**
     * Download the private user dataset as a CSV (if allowed)
     * Calls private function to generate CSV for a dataset
     * @return Response with CSV datatype AND download header or redirect to user datasets if not found (or not authorized)
     *      - if not logged in the auth middleware will redirect to login (specified at route config)
     */
    public function downloadPrivateCSV(Request $request, int $id)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->find($id); //Search for this dataset id ONLY within datasets associated with this user
        if (!$dataset) return redirect('myprofile/mydatasets');
        $filename = 'TLCMLayer_' . $id;
        return Response::make($dataset->csv(), '200', array('Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="' . $filename . '.CSV"'));
    }

    /**
     * Download RO-Crate archive of a public dataset.
     *
     * @param Request $request
     * @param int $datasetID
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|null
     * @throws \Throwable
     */
    public function downloadPublicROCrate(Request $request, $datasetID)
    {
        $dataset = Dataset::where([
            'id' => $datasetID,
            'public' => true,
        ])->first();
        if (!$dataset) {
            abort(404);
        }
        $crate = ROCrateGenerator::generateDatasetCrate($dataset);
        if ($crate) {
            $timestamp = date("YmdHis");
            return response()->download($crate, "ghap-ro-crate-layer-{$dataset->id}-{$timestamp}.zip")->deleteFileAfterSend();
        }
        return null;
    }

    /**
     * Download RO-Crate archive of as the owner of the dataset.
     *
     * @param Request $request
     * @param int $datasetID
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|null
     * @throws \Throwable
     */
    public function downloadPrivateROCrate(Request $request, $datasetID)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->find($datasetID);
        if (!$dataset) {
            abort(404);
        }
        $crate = ROCrateGenerator::generateDatasetCrate($dataset);
        if ($crate) {
            $timestamp = date("YmdHis");
            return response()->download($crate, "ghap-ro-crate-layer-{$dataset->id}-{$timestamp}.zip")->deleteFileAfterSend();
        }
        return null;
    }
}
