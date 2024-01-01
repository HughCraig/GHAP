<?php

namespace TLCMap\Http\Controllers\User;
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');

use TLCMap\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use TLCMap\Http\Helpers\UID;
use TLCMap\Models\User;
use TLCMap\Models\Role;
use TLCMap\Models\SavedSearch;
use TLCMap\Models\Dataset;
use TLCMap\Models\Dataitem;
use TLCMap\Models\SubjectKeyword;
use TLCMap\Models\RecordType;

use TLCMap\Mail\EmailChangedOld;
use TLCMap\Mail\EmailChangedNew;
use TLCMap\Mail\EmailChangedWebmaster;
use TLCMap\Mail\PasswordChanged;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

use TLCMap\Http\Helpers\GeneralFunctions;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | User Controller
    |--------------------------------------------------------------------------
    |
    |
    */

    public $dateheadings = [
        ["datestart", "dateend"],
        ["begin", "end"],
        ["startdate", "enddate"],
        ["start date", "end date"],
        ["date start", "date end"],
        ["start_date", "end_date"],
        ["date", "date"] // if there is a single date set begin and end to same
    ];

    public $llheadings = [
        ["latitude", "longitude"],
        ["lat", "long"],
        ["lat", "lng"]
    ];

    public $generalCols = [
        'id', 'title', 'type', 'linkback', 'external_url',
        'route_id', 'stop_idx', 'route_title'
    ];

    public $commonCols = [
        'placename', 'name', 'description', 'quantity',
        'latitude', 'longitude', "lat", "long", "lat", "lng",
    ];

    public $commonDateStartCols = ["datestart", "startdate", "begin", "date"];
    public $commonDateEndCols = ["dateend", "enddate", "end", "date"];

    public $commonDateCols = [];

    public $pairBasedPrefixes = [
        'departure', "origin", "arrival", "destination"
    ];

    public $pairBasedDateStartCols = [];
    public $pairBasedDateEndCols = [];
    // The common attributes and date attributes of pair of points
    public $pairBasedCommonCols = [];

    public $pointBasedNotForExtData = [];
    public $pairBasedNotForExtData = [];

    public function __construct() {
        $this->commonDateCols = array_unique(array_merge($this->commonDateStartCols, $this->commonDateEndCols));
        // Construct pair-based attributes
        $this->generatePairBasedCols($this->commonDateStartCols, $this->pairBasedDateStartCols);
        $this->generatePairBasedCols($this->commonDateEndCols, $this->pairBasedDateEndCols);
        $this->generatePairBasedCols($this->commonCols,  $this->pairBasedCommonCols);
        $this->pointBasedNotForExtData = array_merge($this->generalCols, $this->commonCols, $this->commonDateCols);
        $this->pairBasedNotForExtData = array_merge($this->generalCols, $this->pairBasedCommonCols, $this->pairBasedDateStartCols, $this->pairBasedDateEndCols);
    }

    private function generatePairBasedCols($sourceCols, &$targetArray) {
        foreach ($this->pairBasedPrefixes as $prefix) {
            foreach ($sourceCols as $col) {
                $targetArray[] = $prefix . '_' . $col;
            }
        }
    }

    public function userProfile(Request $request)
    {
        return view('user.userprofile');
    }

    public function editUserPage(Request $request)
    {
        return view('user.edituser');
    }

    public function editUserInfo(Request $request)
    {
        $user = auth()->user();
        $input = $request->all();
        $rules = ['name' => ['required', 'string', 'max:255']];

        $this->validate($request, $rules);

        $user->update(['name' => $request->input('name')]);
        return redirect('myprofile')->with('success', 'Profile updated!');
    }

    public function editUserPassword(Request $request)
    {
        $user = auth()->user();
        $notin = array_merge(explode(' ', strtolower($user->name)), explode('@', strtolower($user->email))); //cannot match username, or any part of the email address
        $rules = [ //rules for the validator
            'old_password' => function ($attribute, $value, $fail) { //custom rule to check hashed passwords match
                if (!Hash::check($value, auth()->user()->password)) {
                    $fail('Your current password doesnt match our database!'); //custom fail message
                }
            },
            'password' => [
                'required', 'string', 'min:8', 'max:16', 'confirmed', //10+ chars, must match the password-confirm box
                'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^A-Za-z0-9]/', //must contain 1 of each: lowercase uppercase number and special character
                'not_regex:/(.)\1{4,}/', //must not contain any repeating char 4 or more times
                function ($attribute, $value, $fail) use ($notin) {
                    $v = strtolower($value);
                    foreach ($notin as $n) {
                        if (strpos($v, $n) !== false) $fail('Password cannot contain any part of your name or email!');
                    }
                }
            ],
        ];

        $validator = Validator::make($request->all(), $rules); //create the validator

        if ($validator->fails()) return redirect()->back()->withErrors($validator->errors()); //if fails redirect back with errors

        $user->update(['password' => Hash::make($request->input('password'))]);

        //Email user
        Mail::to($user->email)->send(new PasswordChanged($user->name));

        return redirect('myprofile')->with('success', 'Password updated!'); //if input passes validation redirect with success message

    }

    public function editUserEmail(Request $request)
    {
        $user = auth()->user();

        $rules = [ //rules for the validator
            'emailpassword' => function ($attribute, $value, $fail) { //custom rule to check hashed passwords match
                if (!Hash::check($value, auth()->user()->password)) {
                    $fail('Incorrect password!'); //custom fail message
                }
            },
            'email' => [
                function ($attribute, $value, $fail) {
                    if (User::find($value)) $fail('A user with this email already exists!');
                },
                'required', 'email', 'confirmed']
        ];

        $validator = Validator::make($request->all(), $rules); //create the validator
        if ($validator->fails()) return redirect()->back()->withErrors($validator->errors()); //if fails redirect back with errors, else continue

        //vars for emails
        $old_email = $user->email;
        $new_email = $request->input('email');

        //WE NO LONGER NEED TO UPDATE PIVOT TABLES AS EMAIL IS NO LONGER THE PK OF THE USER TABLE!
        //UPDATE user_dataset pivot table to change all cases of old email to new email
        //$user->datasets()->newPivotStatement()->where('user_email', '=', $user->email)->update(array('user_email' => $request->input('email')));

        //UPDATE role_user pivot table to change all cases of old email to new email
        //$user->roles()->newPivotStatement()->where('user_email', '=', $user->email)->update(array('user_email' => $request->input('email')));

        //UPDATE user email
        $user->update(['email' => $request->input('email')]);

        //Send notification emails to old, new , and webmaster
        Mail::to($old_email)->send(new EmailChangedOld($old_email, $new_email));
        Mail::to($new_email)->send(new EmailChangedNew($old_email, $new_email));
        Mail::to(config('mail.webmasteremail'))->send(new EmailChangedWebmaster($old_email, $new_email));

        return redirect('myprofile')->with('success', 'Email updated!'); //if input passes validation redirect with success message
    }

    public function userDatasets(Request $request)
    {
        return view('user.userdatasets', ['user' => auth()->user()]);
    }

    public function userViewDataset(Request $request, int $id)
    {
        $user = auth()->user();
        if(!$user){
            return redirect('layers/' . $id); // Return to public view of dataset for non-logged in users
        }
        $dataset = $user->datasets()->with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->find($id);

        if (!$dataset) return redirect('myprofile/mydatasets');

        //lgas from DB
        $lgas = json_encode(Dataitem::getAllLga(), JSON_NUMERIC_CHECK);

        //feature_codes from DB
        $feature_terms = json_encode(Dataitem::getAllFeatures(), JSON_NUMERIC_CHECK);

        //parishes from DB
        $parishes = json_encode(Dataitem::getAllParishes(), JSON_NUMERIC_CHECK);

        $states = Dataitem::getAllStates();

        // recordtypes from db. Note that the DS and the Item both have a recordtype attribute
        $recordtypes = RecordType::types();
        if ($dataset->recordtype_id === null) {
            $dataset->recordtype_id = 1;
        }

        return view('user.userviewdataset', ['lgas' => $lgas, 'feature_terms' => $feature_terms, 'parishes' => $parishes, 'states' => $states, 'recordtypes' => $recordtypes, 'ds' => $dataset, 'user' => auth()->user()]);
    }

    public function userSavedSearches(Request $request)
    {
        $user = auth()->user();
        $searches = SavedSearch::where('user_id', $user->id)->get();
        $recordTypeMap = RecordType::getIdTypeMap();
        $recordtypes = RecordType::types();
        $subjectKeywordMap = [];
        foreach($searches as $search){
            $subjectKeywordMap[$search->id] = $search->subjectKeywords->toArray();
        }
        return view('user.usersavedsearches', ['searches' => $searches , 'recordTypeMap' => $recordTypeMap , 'recordtypes' => $recordtypes, 'subjectKeywordMap' => $subjectKeywordMap]);
    }

    /*
      userDeleteSearches moved to AJAX CONTROLLER
    */

    public function newDatasetPage(Request $request)
    {
        $recordtypes = RecordType::types();
        return view('user.usernewdataset', ['recordtypes' => $recordtypes]);
    }

    public function createNewDataset(Request $request)
    {
        $user = auth()->user();
        //ensure the required fields are present
        $datasetname = $request->dsn;
        $description = $request->description;
        $tags = explode(",,;", $request->tags);

        if (!$datasetname || !$description || !$tags) return redirect('myprofile/mydatasets');

        //Check temporalfrom and temporalto is valid, continue if it is or reject if it is not (do this in editDataset too)
        $temporalfrom = $request->temporalfrom;
        if (isset($temporalfrom)) {
            $temporalfrom = GeneralFunctions::dateMatchesRegexAndConvertString($temporalfrom);
            if (!$temporalfrom) return redirect('myprofile/mydatasets'); //The user bypassed the frontend js date check and submitted an incorrect date anyways, send them back to the datasets page
        }

        $temporalto = $request->temporalto;
        if (isset($temporalto)) {
            $temporalto = GeneralFunctions::dateMatchesRegexAndConvertString($temporalto);
            if (!$temporalto) return redirect('myprofile/mydatasets'); //The user bypassed the frontend js date check and submitted an incorrect date anyways, send them back to the datasets page
        }

        $keywords = [];
        //for each tag in the subjects array(?), get or create a new subjectkeyword
        foreach ($tags as $tag) {
            $subjectkeyword = SubjectKeyword::firstOrCreate(['keyword' => $tag]);
            array_push($keywords, $subjectkeyword);
        }

        $recordtype_id = RecordType::where('type', $request->recordtype)->first()->id;

        $filename = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            //Validate image file.
            if(!GeneralFunctions::validateUserUploadImage($image)){
                return response()->json(['error' => 'Image must be a valid image file type and size.'], 422);
            }
            $filename = time() . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images', $image, $filename);
        }

        $dataset = Dataset::create([
            'name' => $datasetname,
            'description' => $description,
            'recordtype_id' => $recordtype_id,
            'creator' => $request->creator,
            'public' => $request->public,
            'allowanps' => $request->allowanps,
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

        $user->datasets()->attach($dataset, ['dsrole' => 'OWNER']); //attach creator to pivot table as OWNER

        foreach ($keywords as $keyword) {
            $dataset->subjectKeywords()->attach(['subject_keyword_id' => $keyword->id]);
        }

        return redirect('myprofile/mydatasets/' . $dataset->id);
    }

    public function userEditDataset(Request $request, int $id)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->find($id);

        if (!$dataset) return redirect('myprofile/mydatasets'); //couldn't find dataset

        //Mandatory Fields
        $datasetname = $request->dsn;
        $description = $request->description;
        $tags = explode(",,;", $request->tags);

        if (!$datasetname || !$description || !$tags) return redirect('myprofile/mydatasets'); //Missing required fields

        //Check temporalfrom and temporalto is valid, continue if it is or reject if it is not
        $temporalfrom = $request->temporalfrom;
        if (isset($temporalfrom)) {
            $temporalfrom = GeneralFunctions::dateMatchesRegexAndConvertString($temporalfrom);
            if (!$temporalfrom) return redirect('myprofile/mydatasets'); //The user bypassed the frontend js date check and submitted an incorrect date anyways, send them back to the datasets page
        }

        $temporalto = $request->temporalto;
        if (isset($temporalto)) {
            $temporalto = GeneralFunctions::dateMatchesRegexAndConvertString($temporalto);
            if (!$temporalto) return redirect('myprofile/mydatasets'); //The user bypassed the frontend js date check and submitted an incorrect date anyways, send them back to the datasets page
        }

        $keywords = [];
        //for each tag in the subjects array(?), get or create a new subjectkeyword
        foreach ($tags as $tag) {
            $subjectkeyword = SubjectKeyword::firstOrCreate(['keyword' => $tag]);
            array_push($keywords, $subjectkeyword);
        }

        $recordtype_id = RecordType::where('type', $request->recordtype)->first()->id;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            //Validate image file.
            if(!GeneralFunctions::validateUserUploadImage($image)){
                return response()->json(['error' => 'Image must be a valid image file type and size.'], 422);
            }
            // Delete old image.
            if ($dataset->image_path && Storage::disk('public')->exists('images/' . $dataset->image_path)) {
                Storage::disk('public')->delete('images/' . $dataset->image_path);
            }
            $filename = time() . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images', $image, $filename);
            $dataset->image_path = $filename;
        }

        $dataset->fill([
            'name' => $datasetname,
            'description' => $description,
            'recordtype_id' => $recordtype_id,
            'creator' => $request->creator,
            'public' => $request->public,
            'allowanps' => $request->allowanps,
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
            'image_path' => $dataset->image_path
        ]);

        $dataset->save();

        $dataset->subjectKeywords()->detach(); //detach all keywords

        //Attach the new ones
        foreach ($keywords as $keyword) {
            $dataset->subjectKeywords()->attach(['subject_keyword_id' => $keyword->id]);
        }

        return redirect('myprofile/mydatasets/' . $id);
    }

    /*
      data item add/edit/delete in AJAX CONTROLLER
    */

    /*
        Add to the dataset from file - can be .csv, .kml, or .json
        Will return to dataset with error message if incorrect file extension or if incorrectly formatted data
        TODO: Need to think about the bulk update of mobility!
    */
    public function bulkAddDataitem(Request $request)
    {
        ini_set('upload_max_filesize', '10M');
        ini_set('post_max_size', '10M');

        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //currently logged in user
        $ds_id = $request->ds_id;
        $dataset = $user->datasets()->find($ds_id);

        if (!$dataset || ($dataset->pivot->dsrole != 'OWNER' && $dataset->pivot->dsrole != 'ADMIN' && $dataset->pivot->dsrole != 'COLLABORATOR'))
            return redirect('myprofile/mydatasets'); //if dataset not found for this user or not ADMIN/COLLABORATOR, go back

        //get file
        $file = $request->fileToUpload;

        //overwrite style?
        $appendStyle = $request->appendStyle;
        $overwriteJourney = $request->overwriteJourney;
        //single point-based dataset or origin-destination dataset?
        $pointBasedUpload = ($request->uploadODPairs == "on") ? false : true;

        //get file extension
        $ext = $file->getClientOriginalExtension();

        //get the fillable fields for dataitems
        $fillable = (new Dataitem())->getFillable();

        //Attempt a file read and parse
        try {
            if (strcasecmp($ext, 'csv') == 0) {
                //parse cs

                $arr = $this->csvToArray($file, $pointBasedUpload);
                if (!is_array($arr)) return redirect('myprofile/mydatasets/' . $ds_id)->with('error', 'Invalid date format in file on line ' . $arr); //if $arr is a number instead of an array, date format error where $arr is the offending line number
                // TODO !!!!
                // If $arr is a pairBased array, convert it into a pointBased array
                if ($pointBasedUpload == FALSE) {
                    $arr = $this->convertPairToPoints($arr);
                }
                $this->createDataitems($arr, $ds_id, $pointBasedUpload);

            } else if (strcasecmp($ext, 'kml') == 0) { //now handles extended data, journey
                $arr = $this->kmlToArray($file, $appendStyle);

                if (!is_array($arr)) return redirect('myprofile/mydatasets/' . $ds_id)->with('error', 'Invalid date format in file in node starting line ' . $arr);

                //If style/journey data found in KML && checkbox to overwrite was ticked - UPDATE THE DATASET
                if (array_key_exists('raw_journey', $arr)) {
                    if ($overwriteJourney == "on") $dataset->update(['kml_journey' => $arr['raw_journey']]);
                    unset($arr['raw_journey']); //remove the raw_journey from the end of the array
                }
                if (array_key_exists('raw_style', $arr, $pointBasedUpload)) {
                    if ($appendStyle == "on") $dataset->update(['kml_style' => $dataset['kml_style'] . $arr['raw_style']]); //APPEND not overwrite
                    unset($arr['raw_style']); //remove the raw_style from the end of the array
                }

                //Call the function to create all the new data items from this array
                $this->createDataitems($arr, $ds_id, $pointBasedUpload);

            } else if (strcasecmp($ext, 'json') == 0 || strcasecmp($ext, 'geojson') == 0) {
                //TODO extendeddata
                $arr = $this->geoJSONToArray($file);
                if (!is_array($arr)) return redirect('myprofile/mydatasets/' . $ds_id)->with('error', 'Invalid date format in file on line ' . $arr);

                $this->createDataitems($arr, $ds_id, $pointBasedUpload);
            } else {
                return redirect('myprofile/mydatasets/' . $ds_id)->with('error', 'Invalid file format for bulk add!'); //not a valid format, reload page with error msg
            }
        } catch (\Exception $e) {
            Log::error("IMPORT ERROR.");
            $extrainfo = "";
            if (isset($file)) {
                $extrainfo = $extrainfo . " " . $file->getClientOriginalName();
            }
            if (isset($arr)) {
                $extrainfo = $extrainfo . " Error on line " . json_encode($arr);
            }
            // Get the file and line number where the exception occurred
            $file = $e->getFile();
            $line = $e->getLine();

            LOG::error("Import error $file:$line - " . date(DATE_ATOM, mktime(0, 0, 0, 7, 1, 2000)) . " " . $e->getMessage() . " extra info " . $extrainfo);
            return redirect('myprofile/mydatasets/' . $ds_id)
                ->with('error', 'Error processing file. Please check it is in the right format and is less than 10Mb. If CSV, it must have
                a title or placename column. Check that lat, long and any dates are correct. ' .
                    date(DATE_ATOM, mktime(0, 0, 0, 7, 1, 2000)) . " " . $extrainfo)
                ->with('exception', $e->getMessage()); //file parsing threw an exception, reload page with error msg
        }  //catch any exception

        //update the dataset updated time
        $dataset->updated_at = Carbon::now();
        $dataset->save();

        return redirect('myprofile/mydatasets/' . $ds_id)->with('success', 'Successfully imported from file!'); //reload the page

    }

    /**
     * Converts the Pair Points in the given array to a new representation of point-based mobility dataset.
     *
     * @param array $arr The original array representing OD Mobility Dataset.
     * @return array The transformed array representing point-based mobility dataset.
     */
    function convertPairToPoints($arr) {
        $routes = [];
        $origin = [];
        $destination = [];
        $row = 0;
        $newArr = [];

        foreach ($arr as &$entry) {
            foreach ($entry as $key => $value) {
                $keyParts = explode('_', $key, 2);
                if (count($keyParts) > 1 && in_array($keyParts[0], $this->pairBasedPrefixes)) {
                    $prefix = $keyParts[0];
                    $newKey = $keyParts[1];

                    if ($prefix === 'origin' || $prefix === 'departure') {
                        $origin[$newKey] = $value;
                    } elseif ($prefix === 'destination' || $prefix === 'arrival') {
                        $destination[$newKey] = $value;
                    }
                } else {
                    $routes[$key] = $value;
                }
            }

            if ($row === 0) {
                $originStartValidKey = $this->getValidKey($origin, $this->commonDateStartCols);
                $originEndValidKey = $this->getValidKey($origin, $this->commonDateEndCols);
                $destStartValidKey = $this->getValidKey($origin, $this->commonDateStartCols);
                $destEndValidKey = $this->getValidKey($origin, $this->commonDateEndCols);
                $routeStartValidKey = $this->getValidKey($routes, $this->commonDateStartCols);
                $routeEndValidKey = $this->getValidKey($routes, $this->commonDateEndCols);
                $routeValidKeys = array_unique([$routeEndValidKey, $routeStartValidKey]);

            }
            // Handling missing time values
            // complement startdate and enddate with any available of other item of the pair or "date"
            // $origin['startdate'] = $origin[$originStartValidKey] ?? '';
            // unset($origin[$originStartValidKey]);
            // // if ($originStartValidKey !== 'startdate') {
            // //     unset($origin[$originStartValidKey]);
            // // }
            // $origin['enddate'] = $origin[$originEndValidKey] ?? '';
            // unset($origin[$originEndValidKey]);
            // // if ($originEndValidKey !== 'enddate') {
            // //     unset($origin[$originEndValidKey]);
            // // }
            // $destination['startdate'] = $destination[$destStartValidKey] ?? '';
            // unset($destination[$destStartValidKey]);
            // // if ($destination !== 'startdate') {
            // //     unset($destination[$destStartValidKey]);
            // // }
            // $destination['enddate'] = $destination[$destEndValidKey] ?? '';
            // unset($destination[$destEndValidKey]);
            // if ($destEndValidKey !== 'enddate') {
            //     unset($destination[$destEndValidKey]);
            // }

            // if ($origin['startdate'] === '' && $origin['enddate'] !== '') {
            //     $origin['startdate'] = $origin['enddate'];
            // }
            // if ($origin['enddate'] === '' && $origin['startdate'] !== '') {
            //     $origin['enddate'] = $origin['startdate'];
            // }
            // if ($destination['startdate'] === '' && $destination['enddate'] !== '') {
            //     $destination['startdate'] = $destination['enddate'];
            // }
            // if ($destination['enddate'] === '' && $destination['startdate'] !== '') {
            //     $destination['enddate'] = $destination['startdate'];
            // }

            // if ($origin['startdate'] !== '' && $origin['enddate'] !== '' && $destination['startdate'] !== '' && $destination['enddate'] !== '') {
            //     // pass
            // } else {
            //     // complement startdate + enddate in origin/destination with startdate and enddate of route respectively
            //     $routes['startdate'] = $routes[$routeStartValidKey] ?? '';
            //     if ($routes !== 'startdate') {
            //         unset($routes[$routeStartValidKey]);
            //     }
            //     $routes['enddate'] = $routes[$routeEndValidKey] ?? '';
            //     if ($routes !== 'enddate') {
            //         unset($routes[$routeEndValidKey]);
            //     }
            //     if ($origin['startdate'] !== '') {
            //         $origin['startdate'] = $routes['startdate'];
            //     }
            //     if ($origin['enddate'] !== '') {
            //         $origin['enddate'] = $routes['startdate'];
            //     }
            //     if ($destination['startdate'] !== '') {
            //         $destination['startdate'] = $routes['startdate'];
            //     }
            //     if ($destination['enddate'] !== '') {
            //         $destination['enddate'] = $routes['startdate'];
            //     }
            // }

            if (!empty($origin) && !empty($destination)) {
                // $keysToRemove = array('enddate', 'startdate');
                // foreach ($keysToRemove as $key) {
                //     if (array_key_exists($key, $routes)) {
                //         unset($routes[$key]);
                //     }
                // }

                $merged1 = array_merge($routes, $origin);
                $newArr[] = $merged1;

                $merged2 = array_merge($routes, $destination);
                $newArr[] = $merged2;

                // Reset arrays for the next iteration
                $routes = [];
                $origin = [];
                $destination = [];
            }
            $row++;

        }

        unset($entry); // Unset reference to last element
        return $newArr;
    }

    /**
     * Helper function to get a valid key from an array based on an array of possible keys.
     *
     * @param array $data The array to search for the keys.
     * @param array $possibleKeys An array of possible keys to look for.
     * @return mixed|null The value if a valid key is found, otherwise null.
     */
    private function getValidKey($data, $possibleKeys) {
        foreach ($possibleKeys as $key) {
            if (array_key_exists($key, $data)) {
                // return $data[$key];
                return $key;
            }
        }
        return "";
    }

    function userEditCollaborators(Request $request, int $id)
    {
        $user = auth()->user(); //check authorization
        $dataset = $user->datasets()->find($id); //find dataset for this user
        if (!$dataset || ($dataset->pivot->dsrole != 'ADMIN' && $dataset->pivot->dsrole != 'OWNER')) return redirect('myprofile/mydatasets'); //if DS id doesnt exist OR user not ADMIN, return to DS page
        return view('user.usereditcollaborators', ['ds' => $dataset, 'user' => auth()->user()]);
    }

    /*
      Will create dataitems from the given array - will ignore column names that are not present in Dataitem

      $arr takes an array where each entry represents a dataitem of the form (['ds_id' => thedatasetid, 'placename' => someplacename, 'latitude' => 123, => etc...])
      $ds_id is the id for the dataset to add this data item into

      TODO: If the user hase stop_idx itself?
     */
    function createDataitems($arr, $ds_id, $pointBasedUpload)
    {
        $fillable = (new Dataitem)->getFillable(); //array of all the columns in the dataitem table that are fillable

        // detect names of fields that contain the dates and lat long
        $datecols = $this->aliasColPair($this->dateheadings, $arr);
        $llcols = $this->aliasColPair($this->llheadings, $arr);

        $notForExtData = [
            "id", "title", "placename", "name", "description", "type", "linkback",
            "quantity", "created_at", "updated_at", "ghap_id", "route_id", 'stop_idx',
            "route_original_id", "route_title", ]; // because of special handling, as with date and lat long cols

        $extDataExclusions = array_merge($fillable, $datecols, $llcols, $notForExtData);

        // Exclude these columns.
        $excludeColumns = ['uid', 'datasource_id', ];

        $route_meta = [];
        $route_titles = [];
        $route_id = 1;
        $isMultiRoutes = NULL;
        for ($i = 0; $i < count($arr); $i++) { //FOREACH data item
            $culled_array = array(); //we will cull out all keys that are not present as fillable fields
            $extendeddata = array(); //and add anything else to extended data.

            //More elegant, automated solution - as long as the kml has the correct column names
            foreach ($arr[$i] as $key => &$value) {
                /* TODO:
                    This preg_replace cuts out all characters from <Data> names other than letters and underscores so we don't fail comparisons due to invisible chars...
                    - works for that but ignores many valid names! find a better solution
                */
                $key = $this->sanitiseKey($key);
                $value = $this->sanitiseValue($value);

                //$key = preg_replace('/[^a-zA-Z_ ]/', '', $key); //REMOVE ALL NON ALPHA CHARACTERS FROM KEY NAME - some unseen chars were affecting string comparison ('placename' == 'placename' equating to false)
                /**
                 * HERE IS THE SECTION FOR ADDING DATABASE ALIASES FOR USER UPLOADS OR FOR MANIPULATING DATA BEFORE ENTRY
                 *    eg the database column is "placename" but we also want to accept "title" into this column
                 *    Do this with the following line:
                 *        else if ($key == "title") { $culled_array["placename"] = $value; }
                 *
                 *    This will be overwritten if "placemark" is also present
                 *
                 *    ! Bill Pascoe: this is being changed as the policy for user layers is to have a col for both title and placename, since the title might not be a placename
                 *    and there must always be a title but placename is optional. If there is a placename and no title, the title defaults to placename.
                 *    none the less, user may upload file with 'title' or 'placename' as the column, or both. So we have to handle that.
                 */
                // we are looping each column and checking it's name looking to handle the crucial ones...
                //if array has the "type" key, change it to "recordtype_id" and change all of the values to the actual id for the given type, or "Other" if it does not match
                if (!in_array($key, $excludeColumns)) {
                    if ($key == "type" || $key == "record_type") {
                        //get the recordtype id from "type" name
                        $culled_array["recordtype_id"] = RecordType::getIdByType($value); //if recordtype does exist, set the recordtype_id to its id, otherwise set it to 1 (default "Other")
                    } else if($key == "layer_id"){
                        $culled_array["dataset_id"] = $value;
                    } else if ($key == "linkback") {
                        $culled_array["external_url"] = $value;
                    } else if (in_array($key, $fillable) && $key != 'id') {
                        //For all other keys (except id) push the key value combo into the culled array
                        $culled_array[$key] = $value;
                    } else if (!in_array($key, $extDataExclusions) && isset($value) && $value !== '') {
                        //if the key is present as a fillable field for dataitems, then we keep it - DO NOT PUSH ID< THIS IS GENERATED AUTOMATICALLY
                        array_push($extendeddata, $key); // all the extra cols that will go in 'extended data' kml chunk.
                    }
                }
            }

            // BP: set title to placename if title is empty.
            //
            // Now having looked at some aliases for column names, we can look at being forgiving and handling various cases of columns
            // that have common names, and also the whole placename issue.
            //
            //Handle title and placename
            if ((!isset($culled_array["title"])) && (isset($arr[$i]["placename"]))) {
                $culled_array["title"] = $arr[$i]["placename"];
            }
            if ((!isset($culled_array["title"])) && (isset($arr[$i]["name"]))) {
                $culled_array["title"] = $arr[$i]["name"];
            }
            if (!isset($culled_array["title"])) {
                throw new \Exception('Could not find a title, placename or name column to use as Title.');
            }
            if ($culled_array["title"] === NULL) {
                throw new \Exception('Title, placename or name column to use as Title is null or empty.');
            }

            // Handle possible names for date columns
            if (!isset($culled_array["datestart"]) && !empty($datecols)) {
                $culled_array["datestart"] = $arr[$i][$datecols[0]];
                $culled_array["dateend"] = $arr[$i][$datecols[1]];
            }

            // Handle possible names for latitude/longitude columns
            if (!isset($culled_array["longitude"]) && !empty($llcols)) {
                $culled_array["latitude"] = $arr[$i][$llcols[0]];
                $culled_array["longitude"] = $arr[$i][$llcols[1]];
            }

            // Handle extended data columns
            if (!empty($extendeddata)) {
                $extdata = '';
                foreach ($extendeddata as $ed) {
                    if (isset($arr[$i][$ed]) && $arr[$i][$ed] !== '') {
                        $extdata = $extdata .
                            '<Data name="' . trim($ed) . '"><value><![CDATA[' . trim($arr[$i][$ed]) . ']]></value></Data>';
                    }
                }
                if (!empty($extdata)) {
                    $culled_array["extended_data"] = '<ExtendedData>' . $extdata . '</ExtendedData>';
                } else {
                    $culled_array["extended_data"] = null;
                }
            }

            // TODO: define $isMultiRoutes in input para
            if ($isMultiRoutes === NULL) {
                if (!isset($culled_array["route_title"]) && !isset($culled_array["route_id"])) {
                    $isMultiRoutes = false;
                } else {
                    $isMultiRoutes = true;
                }
            }
            if ($isMultiRoutes === false) {
            } else if ($isMultiRoutes === true) {
                file_put_contents("test.log", var_export("whyFWEFWe", true), FILE_APPEND);
                if (isset($arr[$i]["route_id"])) {
                    file_put_contents("test.log", var_export($arr[$i]["route_id"], true));
                    $culled_array["route_original_id"] = $arr[$i]["route_id"];
                    if (!isset($arr[$i]["route_title"])) {
                        $culled_array["route_title"] = $arr[$i]["route_id"];
                    }
                } else {
                    file_put_contents("test.log", var_export("whye", true), FILE_APPEND);
                }
                if (!isset($route_titles[$culled_array["route_title"]])) {
                    $route_titles[$culled_array["route_title"]] = $route_id;
                    $route_id++;
                }
                $culled_array["route_id"] = null;
                $route_meta[$i]["route_id"] = $route_titles[$culled_array["route_title"]];
                $route_meta[$i]["earliest_date"] = $this->assignEarliestDate($culled_array);
                $route_meta[$i]["arr_idx"] = $i;
                $route_meta[$i]["dataset_id"] = $ds_id;
            }


            // Store the dataitem
            if (!empty($culled_array)) { //ignore empties
                $dataitemUID = $arr[$i]['ghap_id'] ?? null;
                $dataitemProperties = array_merge(array('dataset_id' => $ds_id), $culled_array);
                $route_meta[$i]["ghap_id"] = $this->createOrUpdateDataitem($dataitemProperties, $dataitemUID);
            }

        }

        if ($isMultiRoutes) {
            // Sort the $route_meta array by route_id, earliest_date, array_idx

            usort($route_meta, array($this, 'customSort'));

            // Assign stop_idx based on route_id grouping
            $stopIdxCounter = [];
            foreach ($route_meta as &$item) {
                $routeId = $item['route_id'];
                if (!array_key_exists($routeId, $stopIdxCounter)) {
                    $stopIdxCounter[$routeId] = 1;
                } else {
                    $stopIdxCounter[$routeId]++;
                }
                $item['stop_idx'] = $stopIdxCounter[$routeId];

            }
            foreach ($route_meta as &$item) {
                $dataitemUID = $item['ghap_id'];
                $dataitemProperties = [
                    'stop_idx' => $item['stop_idx'],
                    'dataset_id' => $item['dataset_id'],
                    'route_id' => $item['route_id'],
                ];
                $this->createOrUpdateDataitem($dataitemProperties, $dataitemUID);
            }
        }


    }

    /**
     * Create the new dataitem or update the existing one.
     *
     * This will firstly try to find the existing dataitem if there's a UID passed in. If the dataitem is found, it will
     * update the existing dataitem with the properties. This currently only works with CSV export/import as the UID
     * prefix is set to 't'.
     *
     * @param array $data
     *   The dataitem properties.
     * @param string $uid
     *   The dataitem UID.
     * @return string $uid
     *   The dataitem UID (existing / newly generated)
     */
    private function createOrUpdateDataitem($data, $uid = null)
    {
        // Find the existing dataitem if the UID presents.
        $dataitem = Dataitem::where('uid', $uid)->first();
        // Check the existing dataitem is in the correct dataset. If not, ignore the update.
        if (!empty($dataitem) && (string) $dataitem->dataset_id === (string) $data['dataset_id']) {
            // Update the existing dataitem if there's a match.
            $dataitem->fill($data)->save();
        } else {
            // Create the new dataitem. THIS WILL IGNORE EXACT DUPLICATES WITHIN THIS DATASET.
            $dataitem = Dataitem::firstOrCreate($data);
            // Generate UID.
            if (empty($dataitem->uid)) {
                $dataitem->uid = UID::create($dataitem->id, 't');
                $dataitem->save();
            }
        }
        return $dataitem->uid; // Return UID
    }

// trawl for lat long col names


// detect commonly used column or attribute names that come in pairs, such as 'begin' and 'end' or 'lat' and 'lng'.
// Pass in array of arrays of possible headings for date and latlong columns, and the key value array of headings from the spreadsheet.
// Returns the 2 element array containing the matched keys/column headings, or empty array.

// This looks like it should be something simple. It may be that it could be made simple, but here's why it's complicated at the moment.
// We can't just check if any of the headings match our list of possible date or lat lng keywords, because check if this heading isset in this other array is case sensitive.
// we don't want to put every possible case combination in our list of key words to check so we want case insenstive matching.
// You would think you could just loop through the first record, not the whole lot. TBH maybe you can and I haven't checked it properly,
// But one reason that maybe you can't is because null or empty values were left out of the key values pairs, so you have to loop the entire dataset, to see if there is a
// named date column for just a few records, while most were null or empty.
    function aliasColPair($cols, $arr)
    {

        $checkhead = $arr;
        $headkeysfound = ['', ''];
        //need to do case insenstive matching, so...
        for ($i = 0; $i < count($arr); $i++) {

            foreach (array_keys($arr[$i]) as $colkey) {

                foreach ($cols as $c) {

                    if (strtolower($colkey) === strtolower($c[0])) {

                        $headkeysfound[0] = $colkey;
                    }
                    if (strtolower($colkey) === strtolower($c[1])) {

                        $headkeysfound[1] = $colkey;
                    }
                }

                if (!empty($headkeysfound[0]) && !empty($headkeysfound[1])) {
                    return $headkeysfound;
                }
            }
            //$checkhead[$i] = strtolower($arr[$i]);
        }
        /*
            for($i = 0; $i < count($checkhead); $i++) {
              foreach ($cols as $c) {
                if ((isset($checkhead[$i][strtolower($c[0])])) && (isset($checkhead[strtolower($i)][strtolower($c[1])]))) {
                //if ((isset($arr[$i][$c[0]])) && (isset($arr[$i][$c[1]]))) {
                  return $c;
                }
              }
            }
            */
        return array();

    }

    // abandoned function?
    function matchLL($arr)
    {
        for ($i = 0; $i < count($arr); $i++) {
            foreach ($this->llheadings as $llc) {
            // foreach ($llcols as $llc) {
                if ((isset($arr[$i][$llc[0]])) && (isset($arr[$i][$llc[1]]))) {
                    return $llc;
                }
            }
        }
        return array();

    }


    // trawl the data for possible date columns
    // abandoned function?
    function matchDates($arr)
    {
        // note that col headings in csv already have no whitespace and converted to lower case
        for ($i = 0; $i < count($arr); $i++) {
            // foreach ($datecols as $dc) {
            foreach ($this->dateheadings as $dc) {
                if ((isset($arr[$i][$dc[0]])) && (isset($arr[$i][$dc[1]]))) {
                    return $dc;
                }
            }
        }
        return array();
    }


    /*
        Convert CSV file to array including:
            1. Get the content from CSV.
            2. Sanitize the headers.
            3. Handle date formatting.

        Source: https://stackoverflow.com/questions/35220048/import-csv-file-to-laravel-controller-and-insert-data-to-two-tables
        Note: Each line in the CSV must contain a value for all entries in the header.
        And handling the presence of columns by different names such as lat, lng, linkback, title, etc., is done elsewhere.

        Input:
        - $file: CSV file object to be converted.
        - $delimiter: (Optional) Delimiter used in the CSV file, default is ','.

        Output:
        - Returns an array containing the CSV data.

        !!!!!!
        `$lines = str_getcsv($file->get(),"\n");`
        This is failing to handle line breaks in cells. Need to handle that.
        $lines = fgetcsv($file->get(), "\n");//Split entire file into array of lines on \n    OLD: explode(PHP_EOL,$file->get());
        Try fgetcsv instead.

        Refactoring this entirely, as the old way didn't handle multiline cells. Output should be the same.
        The purpose of this section is to get the CSV, sanitize the headers, and handle date formatting.
        Note that handling the presence of columns by different names such as lat, lng, linkback, title, etc., is done elsewhere.
        (Feels like it could/should be done in the same process. But just need to get it working so not digressing...
        and it might make sense after all since this is just for CSV but later we can handle any input after ingest)
        And reformat like this:
        data[this] is now an array mapping header to field eg data[this] = ['placename' => 'newcastle', ... => ..., etc]
    */
    function csvToArray($file,  $pointBasedUpload = TRUE, $delimiter = ',')
    {

        $header = null;
        $data = array();
        // $datestartindex = false;
        // $dateendindex = false;

        $latitudeIndex = false;
        $longitudeIndex = false;

        try {

            if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
                // necessary if a large csv file
                set_time_limit(0);
                $row = 0;
                if ($pointBasedUpload == TRUE){
                    $notForExtData = $this->pointBasedNotForExtData;
                } else {
                    $notForExtData = $this->pairBasedNotForExtData;
                }

                while (($data = fgetcsv($handle)) !== FALSE) {
                    // number of fields in the csv
                    $col_count = count($data);

                   if(!$this->validateRow($data)) continue; //if the row is invalid, skip it

                    // sanitise headings
                    if ($row === 0) {

                        // sanitise and check for required fields
                        $header = $data;
                        foreach ($header as &$heading) {
                            $heading = $this->sanitiseKey($heading);
                        }

                        // if the uploaded data includes datestart or dateend values, store the index
                        // dates might be in datestart or date end, or there may be a single date field.
                        // ****************************
                        // Ivy's comment
                        // It seems that "datestart"/"startdate"/"start date"/"begin" has priority? (which not really make sense)
                        // Keep it anyway
                        // The previous method default there would only be one date column or one pair of date columns
                        // Regarding the mobility origin-destination dataset format for mobility mapping, the dataset could have
                        // more than one/one pair of column(s). Hence the the index storage should be an array rather than an integer
                        // TODO: the current method is too clumsy, but regarding there would not be a wide spreadsheet uploaded, just make it work for now
                        // $datestartindex = array_search('datestart', $header); //if the uploaded header includes datestart or dateend values, store the index
                        // $dateendindex = array_search('dateend', $header);
                        $dataStartStrings = $this->commonDateStartCols;
                        $dateEndStrings = $this->commonDateEndCols;

                        if ($pointBasedUpload == FALSE) {
                            $dataStartStrings = $this->pairBasedDateStartCols;
                            $dateEndStrings = $this->pairBasedDateEndCols;
                        }

                        $latitudeIndex = array_search('latitude', $header);
                        $longitudeIndex = array_search('longitude', $header);

                        $datestartindices = [];
                        $dateendindices = [];

                        foreach ($dataStartStrings as $dataStartString) {
                            $index = array_search($dataStartString, $header);
                            if ($index !== false) {
                                $datestartindices[] = $index;
                            }
                        }
                        foreach ($dateEndStrings as $dateEndString) {
                            $index = array_search($dateEndString, $header);
                            if ($index !== false) {
                                $dateendindices[] = $index;
                            }
                        }

                        // this part is just for checking the date formatting. We'll handle default and mapping fields back in the calling function.
                        if (empty($datestartindices) && empty($dateendindices)) {
                            $datestartindices[] = array_search('date', $header);
                            $dateendindices[] = array_search('date', $header);
                        }
                    } else { // not a heading format the dates
                        $fields = $data;

                        $dateIndices = array_merge($datestartindices, $dateendindices);
                        // Check if $dateIndices contains only false values, then set it to an empty array
                        if (count(array_unique($dateIndices)) === 1 && reset($dateIndices) === false) {
                        } else {
                            foreach ($dateIndices as $dateIndex) {
                                $parsedate = GeneralFunctions::dateMatchesRegexAndConvertString($fields[$dateIndex]);
                                if ($parsedate === -1) {
                                    return $row;
                                } else {
                                    $fields[$dateIndex] = $parsedate;
                                }
                            }
                        }

                        if($latitudeIndex !== false){
                            // Remove spaces, non-breaking spaces, and non-numeric characters.
                            $fields[$latitudeIndex] = preg_replace('/[^\d\.-]/', '', str_replace("\xc2\xa0", ' ', $fields[$latitudeIndex]));
                        }
                        if($longitudeIndex !== false){
                            // Remove spaces, non-breaking spaces, and non-numeric characters.
                            $fields[$longitudeIndex] = preg_replace('/[^\d\.-]/', '', str_replace("\xc2\xa0", ' ', $fields[$longitudeIndex]));
                        }

                        $outdata[] = array_combine($header, $fields); //data[this] is now an array mapping header to field eg data[this] = ['placename' => 'newcastle', ... => ..., etc]

                    }


                    $row++;
                }
                fclose($handle);
            } else {
                LOG::error("File import error NO HANDLE");
                throw new Exception('File import error NO HANDLE');
            }


        } catch (Exception $e) {
            LOG::error('File Import Caught exception: ', $e->getMessage(), "\n");
        }


        return $outdata; //return the array of lines and headers
    }

    /*
    * Valid each row of the imported csv file.
    * Ignore blank rows, rows are just empty space or rows are just comma
    */
    function validateRow($array) {
        foreach ($array as $value) {
            $trimmedValue = trim($value);
            if ($trimmedValue !== '' && $trimmedValue !== ',' ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sanitizes and standardizes the key (column names).
     *
     * @param string $s The input string to be sanitized.
     * @return string The sanitized string after removing spaces, dodgy characters,
     *                converting to UTF-8, and applying lowercase handling for specific keys.
     */
    function sanitiseKey($s)
    {
        // remove spaces and dodgy characters
        $s = trim($s);
        $s = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $s);
        $s = preg_replace('/[^a-zA-Z_ ]/', '', $s);
        $s = iconv("UTF-8", "UTF-8//IGNORE", $s);
        // this lc handling seems a bit clumsy, but we want to convert these main keys to lc for easy identification without having to
        // convert in a lot of clumsy comparison elsewhere, yet we can't just lc everything, cause we can't assume the case when outputting,
        // so need to retain case for other things like extended data. Noticed glitch between lcing everying in CSV, but not in KML, so was
        // no way out but this.
        $notForExtData = [
            "id", "title", "placename", "name", "description", "type", "linkback", "latitude", "longitude",
            "startdate", "enddate", "date", "datestart", "dateend", "begin", "end", "linkback", "external_url" ,
            "record_type" , "start_date", "end_date"
        ];
        if (in_array(strtolower($s), array_map('strtolower', $notForExtData))) {
            $s = strtolower($s);
        }
        return $s;
    }

    function sanitiseValue($s)
    {
        $s = iconv("UTF-8", "UTF-8//IGNORE", $s);
        return $s;
    }
    //array of asoc arrays of form  [['placename' => 'newcastle', 'latitude' => 123.456, etc], ['placename' => etc], ['placename' => etc]]
    //sppendStyle is true if we want to grab the styleUrl tag for each placemark (if it exists) and import that to the database as well
    function kmlToArray($file, $appendStyle = false)
    {
        //dd($this->getArea([[-10,-10],[-20,-10],[-20,-20],[-10,-20]])); //expected 100
        //dd($this->getCentroid([[-10,-10],[-20,-10],[-20,-20],[-10,-20]])); //expected -15,-15
        //dd($this->getMidpoint([[0,0], [0,5], [3,10]])); //expected 1,5

        $data = array();
        $xml_object = simplexml_load_file($file, null, LIBXML_NOERROR);
        $raw_journey = null;
        $raw_style = null;

        //compound all the style tags into one var
        if (!empty($xml_object->Document->Style)) {
            foreach ($xml_object->Document->Style as $style) {
                $raw_style .= $style->asXml();
            }
        }
        if (!empty($xml_object->Document->StyleMap)) {
            foreach ($xml_object->Document->StyleMap as $style) {
                $raw_style .= $style->asXml();
            }
        }

        //Get each placemark as an associative array, and put that into an array - include journey/style data on the end
        foreach ($xml_object->xpath("//*[local-name()='Placemark']") as $place) { //Get all Placemark objects regardless of where they are in the tree, and regardless of the kml namespace
            if (!empty($place->children('gx', TRUE)->Track)) { //if place is actually JOURNEY DATA
                $raw_journey = $place->children('gx', TRUE)->Track->asXml(); //set the var to the raw xml of the journey segment
            } else { //else it is a point, line, polygon, etc
                $curr = $this->placemarkToData($place, $appendStyle);
                if (!is_array($curr)) return $curr; //if $curr is a number instead of an array we had a date format error where $curr is the offending line number
                $data[] = $curr;
            }
        }

        //append raw journey and style info
        if ($raw_journey) $data = $data + array("raw_journey" => $raw_journey);
        if ($raw_style) $data = $data + array("raw_style" => $raw_style);
        return $data;
    }

    //Placemarks can be Points Polygons or LineStrings - we just need to work out the point we want to use and set it as coordinates[] (long, lat)
    function placemarkToData($place, $appendStyle = false)
    {
        $ed_out = array(); //reset the ed_out var
        $ed_raw = null; //holds raw content of extended data (including <ExtendedData> tag)

        if (!empty($place->Point)) {
            $coordinates = explode(',', $place->Point->coordinates); //coordinates of form <coordinates>LONG,LAT,ALT</coordinates>, split around commas
        } else if (!empty($place->Polygon)) {
            $trimmed = trim(str_replace(" ", "", $place->Polygon->outerBoundaryIs->LinearRing->coordinates));
            $points = explode("\n", $trimmed);
            for ($i = 0; $i < count($points); $i++) {
                $points[$i] = explode(",", $points[$i]);
            }
            $coordinates = $this->getCentroid($points);
        } else if (!empty($place->LineString)) {
            $trimmed = trim(str_replace(" ", "", $place->LineString->coordinates));
            $points = explode("\n", $trimmed);
            for ($i = 0; $i < count($points); $i++) {
                $points[$i] = explode(",", $points[$i]);
            }
            $coordinates = $this->getMidpoint($points);
        } else {
            return array();
        }

        $description = (!empty($place->description)) ? $place->description : "";

        $datestart = null;
        $dateend = null;
        $kml_style_url = null;

        if (!empty($place->TimeSpan)) { //Get Timespan values
            if (!empty($place->TimeSpan->begin)) { //if we have a begin node
                $datestart = $place->TimeSpan->begin;  //set $datestart value
                if (!($datestart = GeneralFunctions::dateMatchesRegexAndConvertString($datestart))) {
                    $node = dom_import_simplexml($place->TimeSpan->begin); //convert simplexml to DOMElement
                    return $node->getLineNo(); //get line number of the offending line
                } //else it was validly formatted
            }
            if (!empty($place->TimeSpan->end)) { //if we have an end node
                $dateend = $place->TimeSpan->end;  //set $dateend value
                if (!($dateend = GeneralFunctions::dateMatchesRegexAndConvertString($dateend))) {
                    $node = dom_import_simplexml($place->TimeSpan->end);
                    return $node->getLineNo();
                } //else it was validly formatted
            }
        }

        if ($appendStyle && !empty($place->styleUrl)) {
            $kml_style_url = $place->styleUrl->asXml();
        }


        //Grab all of the extended data - we cull irrelevant fields in the createDataitems function
        if (!empty($place->ExtendedData)) {
            $ed_raw = $place->ExtendedData->asXml();
            foreach ($place->ExtendedData->Data as $ed) { //for each entry in extended data
                $ed_out[strval($ed['name'])] = strval($ed->value); //such as <Data name = "description">, will pull 'description' out as the asoc array key
            }
        }

        return array("title"
            => strval($place->name), "description" => $description, "longitude" => $coordinates[0], "latitude" => $coordinates[1], "datestart" => $datestart, "dateend" => $dateend, "kml_style_url" => $kml_style_url)
            + $ed_out + array("extended_data" => $ed_raw); //adding on the extended data
    }

    //Return a coordinate array [longitude,latitude] representing the center of a polygon
    function getCentroid($points)
    {
        if ($points[0] != $points[count($points) - 1]) array_push($points, $points[0]); //if final point does not equal first point, add first point as final point
        $n = count($points);
        $A = $this->getArea($points);
        $Cx = $Cy = 0;
        for ($i = 0; $i < $n - 1; $i++) {
            $suf = ((float)$points[$i][0] * (float)$points[$i + 1][1] - (float)$points[$i + 1][0] * (float)$points[$i][1]); //end portion of the formula is the same for Cx and Cy on each iteration, so just calculate it once
            $Cx += ((float)$points[$i][0] + (float)$points[$i + 1][0]) * $suf;
            $Cy += ((float)$points[$i][1] + (float)$points[$i + 1][1]) * $suf;
        }
        $Cx *= (1 / (6 * $A));
        $Cy *= (1 / (6 * $A));
        return [$Cx, $Cy]; //long, lat
    }

    //Return the area of a Polygon from its poins
    function getArea($points)
    {
        if ($points[0] != $points[count($points) - 1]) array_push($points, $points[0]); //if final point does not equal first point, add first point as final point
        $n = count($points);
        Log::debug($points);
        Log::debug($n);
        $sum = 0;
        for ($i = 0; $i < $n - 1; $i++) {
            $sum += (((float)$points[$i][0] * (float)$points[$i + 1][1]) - ((float)$points[$i + 1][0] * (float)$points[$i][1])); //sum from 0 to n-1 (inclusive) of xi*yi+1 - xi+1*yi
        }
        return abs($sum / 2);
    }

    //Return a coordinate array [longitude,latitude] representing the midpoint of a line segment, or multiline segment (as the avg of points)
    function getMidpoint($points)
    {
        $n = count($points);
        $x = $y = 0;
        for ($i = 0; $i < $n; $i++) {
            $x += (float)$points[$i][0]; //sum of all x
            $y += (float)$points[$i][1]; //sum of all y
        }
        return [$x / $n, $y / $n]; //divide by n to get the average
    }


    function geoJSONToArray($file)
    {
        $data = array();
        $geojson = json_decode($file->get());
        $features = $geojson->features;
        foreach ($features as $feature) {
            $ed_out = array(); //reset the ed_out var
            foreach ($feature as $key => $value) { //for each data entry for this place
                if ($key != "type" && $key != "geometry" && $key != "properties")
                    $ed_out[strval($key)] = strval($value); //ignoring 'type' 'geometry' and 'properties', add each key val pair to $ed_out
            }
            $data[] = array("title" => $feature->properties->name, "longitude" => $feature->geometry->coordinates[0],
                    "latitude" => $feature->geometry->coordinates[1]) + $ed_out; //adding on the extended data
        }
        return $data;
    }

    function assignEarliestDate($dates_array) {
        $earliestDate = null;

        // Check if datestart exists and is not null
        if (isset($dates_array["datestart"]) && $dates_array["datestart"] !== null) {
            // Parse datestart and handle different date formats
            $earliestDate = $dates_array["datestart"];
        }

        // Check if dateend exists and is not null
        if (isset($dates_array["dateend"]) && $dates_array["dateend"] !== null) {
            if ($earliestDate === null || $dates_array["dateend"] < $earliestDate) {
                $earliestDate = $dates_array["dateend"];
            }
        }

        // Return the earliest date
        return $earliestDate;
    }

    // Define a custom sorting function for route metadata
    // function customSort($a, $b) {
    //     if ($a['route_id'] == $b['route_id']) {
    //         if ($a['earliest_date'] == $b['earliest_date']) {
    //             return $a['arr_idx'] - $b['arr_idx'];
    //         }
    //         return strtotime($a['earliest_date']) - strtotime($b['earliest_date']);
    //     }
    //     return $a['route_id'] - $b['route_id'];
    // }
    function customSort($a, $b) {
    // 首先按照 'route_id' 的值进行排序
    $routeIdComparison = strcmp($a['route_id'], $b['route_id']);
    if ($routeIdComparison !== 0) {
        return $routeIdComparison;
    }

    // 对于相同的 'route_id'，按照 'earliest_date' 的日期先后顺序进行排序
    $earliestDateComparison = $a['earliest_date'] - $b['earliest_date'];
    if ($earliestDateComparison !== 0) {
        return $earliestDateComparison;
    }

    // 对于同一 'route_id' 和 'earliest_date'，按照 'arr_idx' 的值进行排序
    return $a['arr_idx'] - $b['arr_idx'];
}

}


