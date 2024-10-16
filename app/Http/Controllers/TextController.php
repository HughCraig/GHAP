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
use GuzzleHttp;
use Response;

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
            Log::info("File content: ");
            Log::info($filecontent);
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
        if (!$textID) {
            return redirect('myprofile/mytexts/');
        }
        return view('user.userparsetext', ['text' => $text]);
    }

    public function parseTextContent2(Request $request)
    {
        $user = Auth::user();
        $textID = $request->id;

        // Retrieve the text associated with the user
        $text = $user->texts()->find($textID);
        if (!$text) {
            return response()->json(['error' => 'Text not found'], 404);
        }

        $apiUrl = 'http://localhost:8002/api/geoparse';
        $data = [
            'api_key' => 'GSAP-APNR-MxroY7QYIANG8YLDidq9MLEqknsI1oui',
            'text' => $text->content,
            'method' => 'bert'
        ];

        Log::info($text->content);
        // Send the POST request to the external API
        try {
            $client = new GuzzleHttp\Client();
            $response = $client->post($apiUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($data),
            ]);


            // Get the response from the API
            $responseBody = json_decode($response->getBody(), true);

            return response()->json($responseBody);
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Failed to parse text: ' . $e->getMessage());

            // Return the error response with the exception message for debugging
            return response()->json(['error' => 'Failed to parse text.', 'message' => $e->getMessage()], 500);
        }
    }

    public function parseTextContent(Request $request)
    {
        $user = Auth::user();
        $text = $user->texts()->find("10");
        if (!$text) {
            return response()->json(['error' => 'Text not found'], 404);
        }

        $client = new GuzzleHttp\Client();
        $apiUrl = 'http://localhost:8002/api/geoparse';
        $data = [
            'api_key' => 'GSAP-APNR-MxroY7QYIANG8YLDidq9MLEqknsI1oui',
            'text' => $text->content,
            'method' => "bert",
        ];

        try {
            $response = $client->post($apiUrl, [
                'json' => $data,  // Guzzle automatically sets Content-Type to application/json and encodes the data
            ]);

            $responseBody = json_decode($response->getBody(), true);
            return response()->json($responseBody);
        } catch (\Exception $e) {
            Log::error('Failed to parse text: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send request', 'details' => $e->getMessage()], 500);
        }
    }
}
