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
            $query->orderBy('id');
        }])->where(['public' => 1, 'id' => $id])->first(); // get this dataset by id if it is also public
        if (!$ds) return redirect()->route('layers'); // if not found redirect back
        return view('ws.ghap.publicdataset', ['ds' => $ds]); // if found return it with the next view
    }

    /**
     * View a public dataset as a KML
     * Calls private function to generate KML for a dataset
     * @return Response with KML datatype, or redirect to public datasets page if not found
     */
    public function viewPublicKML(Request $request, int $id)
    {
        $dataset = Dataset::with(['dataitems' => function ($query) {
            $query->orderBy('id');
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
            $query->orderBy('id');
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
            $query->orderBy('id');
        }])->where(['public' => 1, 'id' => $id])->first(); //Get the first dataset with this id that is 'public', if it exists
        if (!$dataset) return redirect()->route('layers'); //redirect if not found (invalid id or not public)
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
            $query->orderBy('id');
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
            $query->orderBy('id');
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
            $query->orderBy('id');
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
            $query->orderBy('id');
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
            $query->orderBy('id');
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
            $query->orderBy('id');
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
            $query->orderBy('id');
        }])->find($id); //Search for this dataset id ONLY within datasets associated with this user
        if (!$dataset) return redirect('myprofile/mydatasets');
        $filename = 'TLCMLayer_' . $id;
        return Response::make($dataset->json(), '200', array('Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="' . $filename . '.json"'));
    }

    public function viewPrivateCSV(Request $request, int $id)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->with(['dataitems' => function ($query) {
            $query->orderBy('id');
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
            $query->orderBy('id');
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
