<?php

namespace TLCMap\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use TLCMap\Http\Helpers\GeneralFunctions;
use TLCMap\Models\Text;
use TLCMap\Models\TextType;
use TLCMap\Models\SubjectKeyword;
use Illuminate\Support\Facades\Storage;
use Config;
use TLCMap\Models\RecordType;
use TLCMap\Models\TextContext;

class TextController extends Controller
{

    /**
     * View all collections of the current user.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function viewMyTexts(Request $request)
    {
        return view('user.usertexts', [
            'user' => auth()->user()
        ]);
    }

    /**
     * Page of creating new collection.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function viewMyText(Request $request, $textID)
    {

        $user = Auth::user();
        $text = $user->texts()->find($textID);
        if (!$textID) {
            return redirect('myprofile/mytexts/');
        }
        return view('user.userviewtext', ['text' => $text]);
    }

    /**
     * Page of creating new collection.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function newText(Request $request)
    {
        $texttypes = TextType::types();
        return view('user.usernewtext', ['texttypes' => $texttypes]);
    }


    public function createNewText(Request $request)
    {
        $user = Auth::user();

        $textname = $request->textname;
        $description = $request->description;

        $tags = explode(",,;", $request->tags);
        if (!$textname || !$description || !$tags) return redirect('myprofile/mytexts');

        //Check temporalfrom and temporalto is valid, continue if it is or reject if it is not (do this in editDataset too)
        $temporalfrom = $request->temporalfrom;
        if (isset($temporalfrom)) {
            $temporalfrom = GeneralFunctions::dateMatchesRegexAndConvertString($temporalfrom);
            if (!$temporalfrom) return redirect('myprofile/mytexts'); //The user bypassed the frontend js date check and submitted an incorrect date anyways, send them back to the datasets page
        }

        $temporalto = $request->temporalto;
        if (isset($temporalto)) {
            $temporalto = GeneralFunctions::dateMatchesRegexAndConvertString($temporalto);
            if (!$temporalto) return redirect('myprofile/mytexts'); //The user bypassed the frontend js date check and submitted an incorrect date anyways, send them back to the datasets page
        }

        $keywords = [];
        //for each tag in the subjects array(?), get or create a new subjectkeyword
        foreach ($tags as $tag) {
            $subjectkeyword = SubjectKeyword::firstOrCreate(['keyword' => $tag]);
            array_push($keywords, $subjectkeyword);
        }

        $texttype_id = TextType::where('type', $request->texttype)->first()->id;

        $imageFilename = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            //Validate image file.
            if (!GeneralFunctions::validateUserUploadImage($image)) {
                return response()->json(['error' => 'Image must be a valid image file type and size.'], 422);
            }
            $imageFilename = time() . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images', $image, $imageFilename);
        }

        if ($request->hasFile('textfile')) {
            $textfile = $request->file('textfile');

            $filecontent = GeneralFunctions::validateUserUploadText($textfile);

            //Validate text file.
            if (!$filecontent) {
                return response()->json(['error' => 'Text file must be a valid text file type and size.'], 422);
            }
        } else {
            return response()->json(['error' => 'Text file is required.'], 422);
        }

        $text = Text::create([
            'name' => $textname,
            'description' => $description,
            'texttype_id' => $texttype_id,
            'creator' => $request->creator,
            'publisher' => $request->publisher,
            'contact' => $request->contact,
            'citation' => $request->citation,
            'doi' => $request->doi,
            'source_url' => $request->source_url,
            'linkback' => $request->linkback,
            'language' => $request->language,
            'license' => $request->license,
            'rights' => $request->rights,
            'temporal_from' => $temporalfrom,
            'temporal_to' => $temporalto,
            'created' => $request->created,
            'warning' => $request->warning,
            'image_path' => $imageFilename,
            'content' => $filecontent
        ]);

        $user->texts()->attach($text, ['dsrole' => 'OWNER']);

        foreach ($keywords as $keyword) {
            $text->subjectKeywords()->attach(['subject_keyword_id' => $keyword->id]);
        }

        return redirect('myprofile/mytexts/' . $text->id);
    }

    public function getTextContent(Request $request)
    {
        $user = Auth::user();
        $textID = $request->id;

        // Retrieve the text associated with the user
        $text = $user->texts()->find($textID);
        if (!$text) {
            return response()->json(['error' => 'Text not found'], 404);
        }

        return response()->json(['content' => $text->content]);
    }


    /**
     * Page of creating new collection.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function parseText(Request $request, $textID)
    {

        $user = Auth::user();
        $text = $user->texts()->find($textID);
        $recordtypes = RecordType::all();
        if (!$textID) {
            return redirect('myprofile/mytexts/');
        }
        return view('user.userparsetext', ['text' => $text,  'recordtypes' => $recordtypes]);
    }

    public function parseTextContent(Request $request)
    {
        $user = Auth::user();
        $textID = $request->id;
        $parseMethod = $request->method;

        // Retrieve the text associated with the user
        $text = $user->texts()->find($textID);
        if (!$text) {
            return response()->json(['error' => 'Text not found'], 404);
        }

        $client = new \GuzzleHttp\Client();
        $apiUrl = config('app.geoparsing_api_url');
        $data = [
            'api_key' => config('app.geoparsing_api_key'),
            'text' => $text->content,
            'method' =>  $parseMethod,
        ];

        if ($parseMethod == "dictionary" || $parseMethod == "dictionary_with_coords") {

            if ($request->hasFile('dictionary')) {
                $file = $request->file('dictionary');

                // Validate the file extension
                $allowedExtensions = ['csv'];
                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    return response()->json(['error' => 'The uploaded file must be a CSV file.'], 422);
                }

                $csvData = array_map('str_getcsv', file($file->getRealPath()));

                // If dictionary_with_coords, check for lat/lon values
                if ($parseMethod == "dictionary_with_coords") {
                    $places = [];
                    foreach ($csvData as $row) {
                        // Remove BOM from the first cell if present
                        $row[0] = preg_replace('/^\x{FEFF}/u', '', $row[0]);
                        if (count($row) < 3) {
                            return response()->json(['error' => 'Invalid CSV format. Expected Place Name, Latitude, Longitude'], 400);
                        }
                        $places[] = [$row[0], $row[1], $row[2]]; // Place, Lat, Lon
                    }
                    $data['places'] = $places;
                } else {
                    // For dictionary method, only place names are required
                    $places = array_column($csvData, 0);
                    // Remove BOM from the first entry if present
                    $places[0] = preg_replace('/^\x{FEFF}/u', '', $places[0]);
                    $data['places'] = $places;
                }
            } else {
                return response()->json(['error' => 'CSV file is required for dictionary methods'], 400);
            }
        }

        try {
            $response = $client->post($apiUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $data,
                'verify' => false,
            ]);

            $response = json_decode($response->getBody(), true);


            if (isset($response['data']) && $parseMethod !== "dictionary_with_coords") {

                $geocoding_method = $request->geocoding_method;
                $geocoding_bias = $request->geocoding_bias;

                foreach ($response['data']['place_names'] as $index => $place) {
                    $geocodeResult = $this->geocodePlace($client, $place['name'], $geocoding_method, $geocoding_bias);

                    $response['data']['place_names'][$index]['temp_lat'] = $geocodeResult['data']['geolocated_ents'][0]['lat'] ?? "";
                    $response['data']['place_names'][$index]['temp_lon'] = $geocodeResult['data']['geolocated_ents'][0]['lon'] ?? "";
                    $response['data']['place_names'][$index]['name'] = ucfirst($response['data']['place_names'][$index]['name']);
                }
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to parse text content', 'details' => $e->getMessage()], 500);
        }
    }

    public function addTextContext(Request $request)
    {
        $this->middleware('auth');

        TextContext::create([
            'dataitem_uid' => $request->dataitem_uid,
            'text_id' => $request->text_id,
            'start_index' => $request->start_index,
            'end_index' => $request->end_index,
            'sentence_start_index' => $request->sentence_start_index,
            'sentence_end_index' => $request->sentence_end_index,
            'line_index' => $request->line_index,
            'line_word_start_index' => $request->line_word_start_index,
            'line_word_end_index' => $request->line_word_end_index
        ]);

        return response()->json();
    }

    private function geocodePlace($client, $placeName, $geocoding_method, $bias)
    {
        $apiUrl = config('app.geocoding_api_url');
        $data = [
            'api_key' => config('app.geoparsing_api_key'),
            'place_name' => $placeName,
            'context' => ' ',
            'method' => $geocoding_method,
            'bias' => $bias
        ];

        try {
            $response = $client->post($apiUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $data,
                'verify' => false,
            ]);

            $body = json_decode($response->getBody(), true);

            return $body;
        } catch (\Exception $e) {
            return [
                'error' => 'Geocode Request failed: ' . $e->getMessage()
            ];
        }
    }

    public function deleteText(Request $request)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = Auth::user();

        $text = $user->texts()->find($request->id);
        if (!$text) {
            return response()->json(['error' => 'Text not found'], 404);
        }

        $text->users()->detach();
        $text->delete();
        return redirect('myprofile/mytexts');
    }
}
