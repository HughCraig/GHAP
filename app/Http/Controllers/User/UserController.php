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
use phpDocumentor\Reflection\PseudoTypes\True_;
use TLCMap\Http\Helpers\GeneralFunctions;
use TLCMap\Models\Route;

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
        ["lat", "lon"],
        ["lat", "lng"]
    ];

    public $genPairCols = [
        "routeecetory_id", "route_ori_id", "route_title", "route_description",
    ];

    public $genPointCols = [
        'ghap_id', 'id', 'title', 'placename', 'name', 'description', 'type', "record_type", 'linkback', 'external_url',
        'quantity', "stop_idx"
    ];
    // Date column names have preferrable order, only when no preferred date column provided will find "date" as start_date/end_date
    // Check $findCsvColumnIndex for detecting logic
    public $commonDateStartCols = ["datestart", "startdate", "start_date", "begin", "date"];
    public $commonDateEndCols = ["dateend", "enddate", "end_date", "end", "date"];
    public $commonDateCols = [];

    public $commonLatCols = ['latitude', "lat"];
    public $commonLonCols = ['longitude', "long", "lng"];
    public $commonCoordCols = [];

    public $originPrefixes = [
        'departure', "origin"
    ];
    public $destintionPrefixes = [
        "arrival", "destination"
    ];

    // The date, coordinate attributes and common attributes of pair of points
    public $originPointInPairCols = [];
    public $destinationPointInPairCols = [];

    public $pointBasedNotForExtData = [];
    public $pairBasedNotForExtData = [];

    public $mobilityRecordTypeId;
    public $otherRecordTypeId;

    public function __construct()
    {
        $this->mobilityRecordTypeId = RecordType::getIdByType("Mobility");
        $this->otherRecordTypeId = RecordType::getIdByType("Other");

        $this->commonDateCols = array_unique(array_merge($this->commonDateStartCols, $this->commonDateEndCols));
        $this->commonCoordCols = array_merge($this->commonLatCols, $this->commonLonCols);
        // Construct pair-based attributes
        $pointInPairCols = array_merge(
            $this->genPointCols,
            $this->commonDateCols,
            $this->commonCoordCols
        );

        $this->addODPrefix($pointInPairCols,  $this->originPrefixes, $this->originPointInPairCols);
        $this->addODPrefix($pointInPairCols,  $this->destintionPrefixes, $this->destinationPointInPairCols);
        $this->pointBasedNotForExtData = array_merge(
            $this->genPairCols,
            $this->genPointCols,
            $this->commonCoordCols,
            $this->commonDateCols
        );
        $this->pairBasedNotForExtData = array_merge(
            $this->genPairCols,
            $this->originPointInPairCols,
            $this->destinationPointInPairCols
        );
    }



    private function addODPrefix($sourceCols, $prefixes, &$targetArray)
    {
        foreach ($prefixes as $prefix) {
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
                'required', 'email', 'confirmed'
            ]
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
        if (!$user) {
            return redirect('layers/' . $id); // Return to public view of dataset for non-logged in users
        }

        $dataset = $user->datasets()->with(['dataitemsWithRoute' => function ($query) {
            $query->orderBy('dataset_order');
        }])->withCount(['dataitems', 'subjectKeywords', 'routes'])->find($id);

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

        // get mobility status for mapping
        $hasmobinfo = $dataset->getMappingMobilityInfo();

        return view('user.userviewdataset', [
            'lgas' => $lgas, 'feature_terms' => $feature_terms, 'parishes' => $parishes, 'states' => $states,
            'recordtypes' => $recordtypes, 'ds' => $dataset, 'user' => auth()->user(),
            'hasmobinfo' => $hasmobinfo
        ]);
    }

    public function userSavedSearches(Request $request)
    {
        $user = auth()->user();
        $searches = SavedSearch::where('user_id', $user->id)->get();
        $recordTypeMap = RecordType::getIdTypeMap();
        $recordtypes = RecordType::types();
        $subjectKeywordMap = [];
        foreach ($searches as $search) {
            $subjectKeywordMap[$search->id] = $search->subjectKeywords->toArray();
        }
        return view('user.usersavedsearches', ['searches' => $searches, 'recordTypeMap' => $recordTypeMap, 'recordtypes' => $recordtypes, 'subjectKeywordMap' => $subjectKeywordMap]);
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
            if (!GeneralFunctions::validateUserUploadImage($image)) {
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

        // Check if 'redirect' parameter is present and false
        if ($request->has('redirect') && $request->redirect == 'false') {
            return response()->json(['dataset_id' => $dataset->id], 201);
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
            if (!GeneralFunctions::validateUserUploadImage($image)) {
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

    /**
     * Add to the dataset from file - can be .csv, .kml, or .json
     * Will return to dataset with error message if incorrect file extension or if incorrectly formatted data
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

        // mobility dataset with routes
        $hasRoute = $request->input('uploadMobDatasetWithRoute') === 'on';
        $pointBasedUpload = !($request->input('isODPairs') === 'on');
        $routePurpose = $request->input('datasetPurpose', null);

        if (($hasRoute === false && $pointBasedUpload === true) || ($hasRoute === false && $routePurpose === null)) {
        }
        // validate route options
        $routeRules = [
            'uploadMobDatasetWithRoute' => 'sometimes|nullable',
            'datasetPurpose' => 'required_if:uploadMobDatasetWithRoute,on'
        ];
        $routeValidator = Validator::make($request->all(), $routeRules);
        if ($routeValidator->fails()) {
            return redirect('myprofile/mydatasets/' . $ds_id)->with('error', 'When uploading a mobility dataset with routes, you must select a purpose for the dataset.');
        }

        //get file extension
        $ext = $file->getClientOriginalExtension();

        //get the fillable fields for dataitems
        $fillable = (new Dataitem())->getFillable();

        //Attempt a file read and parse
        try {
            /**
             * Ivy's NOTE TO DEVELOPERS:
             * Current implementation uses a single transaction for the entire import process.
             * This approach ensures data consistency but may lead to the following issues:
             * 1. Long-running transactions could potentially block other database operations.
             * 2. For large datasets, this might cause performance issues or timeouts.
             * 3. If an error occurs late in the process, all previous work will be rolled back.
             *
             * Potential improvements to consider:
             * - Implement batch processing to commit smaller chunks of data.
             * - Use queues for processing large datasets asynchronously.
             * - Implement more granular error handling to avoid rolling back the entire import on minor issues.
             * - Monitor transaction duration and implement timeout mechanisms if necessary. (It's implemented in csvToArray)
             */
            DB::beginTransaction();

            if (strcasecmp($ext, 'csv') == 0) {
                // parse csv

                // Get the original file name
                $oriFileName = $file->getClientOriginalName();

                // Convert CSV to array
                $convertedData = $this->csvToArray($file, $dataset, $hasRoute, $pointBasedUpload, $routePurpose);

                if (is_string($convertedData)) {
                    // Log the error with file name and timestamp
                    Log::error("IMPORT ERROR: {$oriFileName} - " . date(DATE_ATOM, mktime(0, 0, 0, 7, 1, 2000)) . $convertedData);

                    // Redirect with error message
                    return redirect('myprofile/mydatasets/' . $ds_id)
                        ->with('error', $convertedData);
                }

                // Extract points and routes from converted data
                $points = $convertedData['points'];
                $routes = $convertedData['routes'] ?? [];

                if ($hasRoute) {
                    if ($routes) {
                        $routes = $this->prepareRoutes($routes, $routePurpose);
                        $routes = $this->createRoutes($routes, $ds_id);
                        $points = $this->preparePoints($points, $routes);
                    } else {
                        // Log error and redirect if routes are expected but not found
                        Log::error("IMPORT ERROR: {$oriFileName} - " . date(DATE_ATOM, mktime(0, 0, 0, 7, 1, 2000)) . "No route data found. Please check your file and try again.");

                        return redirect('myprofile/mydatasets/' . $ds_id)
                            ->with('error', "No route data found. Please check your file and try again.");
                    }
                }
                $modifiedDataitems = $this->createDataitems($points, $ds_id);

                /**
                 * Handle point removals
                 *
                 * This section performs the following operations:
                 * 1. Identifies dataitem points to be removed from the route
                 * 2. Executes batch deletion of corresponding route_order entries
                 *
                 * Points may be removed from the route due to:
                 * - Emptying of route_id for rows with existing ghap_id
                 * - Conversion of "Mobility" record type to other types
                 *
                 * @var bool $dropPointFromRoute Indicates if any points were removed from the route
                 */
                $affectedRouteIds = [];
                $dataitemIdsToRemove = collect($modifiedDataitems)
                    ->filter(function ($item) {
                        return $item['dropFromRoute'] === true;
                    })
                    ->pluck('id')
                    ->filter()
                    ->all();
                if (!empty($dataitemIdsToRemove)) {
                    $affectedRouteIds = Route::deleteRouteOrdersByDataitemIds($dataitemIdsToRemove);
                }

                if ($hasRoute) {
                    // Update dataitem_id to route.dataitems
                    $routes = $routes->map(function ($route) use ($modifiedDataitems) {
                        $route['dataitems'] = collect($route['dataitems'])->map(function ($dataitem) use ($modifiedDataitems) {
                            $arrIdx = $dataitem['arrIdx'];
                            if (isset($modifiedDataitems[$arrIdx])) {
                                $dataitem['dataitem_id'] = $modifiedDataitems[$arrIdx]['id'];
                            }
                            return $dataitem;
                        });

                        return $route;
                    });
                    // Prepare route orders
                    $routeOrders = $this->prepareRouteOrders($routes, $routePurpose);

                    // Upsert route_order
                    Route::upsertPositionAndRouteIdBatch($routeOrders);

                    $affectedRouteIds = array_unique(array_merge(
                        $affectedRouteIds,
                        $routes->pluck('id')->toArray()
                    ));
                }

                if (!empty($affectedRouteIds)) {
                    Route::checkAndUpdateStatuses($affectedRouteIds);
                }
            } else if (strcasecmp($ext, 'kml') == 0) { //now handles extended data, journey
                $arr = $this->kmlToArray($file, $appendStyle);

                if (!is_array($arr)) return redirect('myprofile/mydatasets/' . $ds_id)->with('error', 'Invalid date format in file in node starting line ' . $arr);

                //If style/journey data found in KML && checkbox to overwrite was ticked - UPDATE THE DATASET
                if (array_key_exists('raw_journey', $arr)) {
                    if ($overwriteJourney == "on") $dataset->update(['kml_journey' => $arr['raw_journey']]);
                    unset($arr['raw_journey']); //remove the raw_journey from the end of the array
                }
                if (array_key_exists('raw_style', $arr)) {
                    if ($appendStyle == "on") $dataset->update(['kml_style' => $dataset['kml_style'] . $arr['raw_style']]); //APPEND not overwrite
                    unset($arr['raw_style']); //remove the raw_style from the end of the array
                }

                //Call the function to create all the new data items from this array
                $this->createDataitems($arr, $ds_id);
            } else if (strcasecmp($ext, 'json') == 0 || strcasecmp($ext, 'geojson') == 0) {
                //TODO extendeddata
                $arr = $this->geoJSONToArray($file);
                if (!is_array($arr)) return redirect('myprofile/mydatasets/' . $ds_id)->with('error', 'Invalid date format in file on line ' . $arr);

                $this->createDataitems($arr, $ds_id);
            } else {
                return redirect('myprofile/mydatasets/' . $ds_id)->with('error', 'Invalid file format for bulk add!'); //not a valid format, reload page with error msg
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

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
            Log::error("Exception stack trace: " . $e->getTraceAsString());
            return redirect('myprofile/mydatasets/' . $ds_id)
                ->with('error', 'Error processing file. Please check it is in the right format and is less than 10Mb. If CSV, it must have
                a title or placename column. Check that lat, long and any dates are correct. ' .
                    date(DATE_ATOM, mktime(0, 0, 0, 7, 1, 2000)) . " " . $extrainfo)
                ->with('exception', $e->getMessage()); //file parsing threw an exception, reload page with error msg
        }  //catch any exception

        // update dataset type if there is any mobility-related modification
        $dataset->updateMobilityRecordType();

        //update the dataset updated time
        $dataset->updated_at = Carbon::now();
        $dataset->save();

        return redirect('myprofile/mydatasets/' . $ds_id)->with('success', 'Successfully imported from file!'); //reload the page

    }

    function userEditCollaborators(Request $request, int $id)
    {
        $user = auth()->user(); //check authorization
        $dataset = $user->datasets()->find($id); //find dataset for this user
        if (!$dataset || ($dataset->pivot->dsrole != 'ADMIN' && $dataset->pivot->dsrole != 'OWNER')) return redirect('myprofile/mydatasets'); //if DS id doesnt exist OR user not ADMIN, return to DS page
        return view('user.usereditcollaborators', ['ds' => $dataset, 'user' => auth()->user()]);
    }

    /**
     * Will create dataitems from the given array - will ignore column names that are not present in Dataitem
     *
     * @param array $arr
     *   The array where each entry represents a dataitem of the form (['ds_id' => thedatasetid, 'placename' => someplacename, 'latitude' => 123, => etc...])
     * @param string $ds_id
     *   The id for the dataset to add this data item into.
     *
     */
    function createDataitems($arr, $ds_id)
    {
        $fillable = (new Dataitem)->getFillable(); //array of all the columns in the dataitem table that are fillable

        // Detect names of fields that contain the dates and lat long
        $datecols = $this->aliasColPair($this->dateheadings, $arr);
        $llcols = $this->aliasColPair($this->llheadings, $arr);

        $notForExtData = [
            "id", "title", "placename", "name", "description", "type", "linkback", "created_at", "updated_at", "ghap_id",
            "quantity", "route_id"
        ]; // because of special handling, as with date and lat long cols

        $extDataExclusions = array_merge($fillable, $datecols, $llcols, $notForExtData);

        // Exclude these columns.
        $excludeColumns = ['uid', 'datasource_id', "stop_idx", "route_ori_id", "route_title", "route_description"];

        // Collect id and $dropFromRoute of dataitems (mainly for route_order create/update/drop)
        $modifiedDataitems = [];

        for ($i = 0; $i < count($arr); $i++) { //FOREACH data itemele
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
                    } else if ($key == "layer_id") {
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
            // Ivy: title is regarded as empty when it's an empty string like "" and "    ".
            //
            // Now having looked at some aliases for column names, we can look at being forgiving and handling various cases of columns
            // that have common names, and also the whole placename issue.
            //
            //Handle title and placename
            if ((!isset($culled_array["title"]) || empty(trim($culled_array["title"]))) && (isset($arr[$i]["placename"]))) {
                $culled_array["title"] = $arr[$i]["placename"];
            }
            if ((!isset($culled_array["title"]) || empty(trim($culled_array["title"]))) && (isset($arr[$i]["name"]))) {
                $culled_array["title"] = $arr[$i]["name"];
            }
            if (!isset($culled_array["title"]) || empty(trim($culled_array["title"]))) {
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

            $isMobility = false;

            // Handle quantity
            if (isset($culled_array["quantity"])) {
                if (!empty(trim($culled_array["quantity"]))) {
                    $isMobility = true;
                } else {
                    $culled_array["quantity"] = null;
                }
            }

            if (isset($culled_array["route_id"])) {
                if (!empty(trim($culled_array["route_id"]))) {
                    $isMobility = true;
                } else {
                    $culled_array["route_id"] = null;
                }
            }

            if ($isMobility) {
                // If point has mobility-related field ,set recordtyp as "Mobility"
                $culled_array["recordtype_id"] = $this->mobilityRecordTypeId;
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

            if (!empty($culled_array)) { //ignore empties
                $dataitemUID = $arr[$i]['ghap_id'] ?? null;
                $dataitemProperties = array_merge(array('dataset_id' => $ds_id), $culled_array);
                $processResult = $this->createOrUpdateDataitem($dataitemProperties, $dataitemUID);

                $modifiedDataitems[] = [
                    'id' => $processResult['id'],
                    'dropFromRoute' => $processResult['dropFromRoute']
                ];
            } else {
                $modifiedDataitems[] = [
                    'id' => null,
                    'dropFromRoute' => false
                ];
            }
        }

        return $modifiedDataitems;
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
     * @param string|null $uid
     *   The dataitem UID.
     * @return array An associative array containing:
     *               - 'id': The ID of the dataitem
     *               - 'uid': The UID of dataitem (existing / newly generated)
     *               - 'dropFromRoute': Boolean indicating if the dataitem is removed from a route
     */
    private function createOrUpdateDataitem($data, $uid = null)
    {
        // Find the existing dataitem if the UID presents.
        $dataitem = Dataitem::where('uid', $uid)->first();
        $dropFromRoute = false;

        // Check the existing dataitem is in the correct dataset. If not, ignore the update.
        if (!empty($dataitem) && (string) $dataitem->dataset_id === (string) $data['dataset_id']) {

            $prevRouteId = $dataitem->route_id;
            $dataitem->fill($data);

            // if dataitem is not mobility record type
            if ($dataitem->recordtype_id !== $this->mobilityRecordTypeId) {
                $dataitem->quantity = null;
                $dataitem->route_id = null;
            }
            if (!$dataitem->quantity && !$dataitem->route_id && $dataitem->recordtype_id === $this->mobilityRecordTypeId) {
                $dataitem->recordtype_id = $this->otherRecordTypeId;
            }

            // if original dataitem is associated with a route, check whether it will be dropped in the update
            $dropFromRoute = $prevRouteId && !$dataitem->route_id;

            $dataitem->save();
        } else {
            // Create the new dataitem. THIS WILL IGNORE EXACT DUPLICATES WITHIN THIS DATASET.
            $dataitem = Dataitem::firstOrCreate($data);
            // Generate UID.
            if (empty($dataitem->uid)) {
                $dataitem->uid = UID::create($dataitem->id, 't');
                $dataitem->save();
            }
        }

        return [
            'id' => $dataitem->id,
            'uid' => $dataitem->uid,
            'dropFromRoute' => $dropFromRoute
        ];
    }

    /**
     * Handle special logic for mobility record types.
     *
     * This method sets quantity and route_id to null if the record type is not mobility.
     * It also determines if the dataitem is going to be dropped from a route based on route_id changes.
     *
     * @param Dataitem $dataitem The dataitem to process
     * @param int|null $prevRouteId The previous route ID of the dataitem
     * @return bool True if the dataitem should be dropped from a route, false otherwise
     */
    private function handleMobilityRecordType(Dataitem $dataitem, ?int $prevRouteId): bool
    {
        // if dataitem is not mobility record type
        if ($dataitem->recordtype_id !== $this->mobilityRecordTypeId) {
            $dataitem->quantity = null;
            $dataitem->route_id = null;
        }

        return $prevRouteId && !$dataitem->route_id;
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


    /**
     * Convert CSV file to array, processing headers, data, and routes if applicable.
     *
     * This function performs the following operations:
     * 1. Opens and reads the CSV file.
     * 2. Processes and validates the CSV header.
     * 3. Handles date formatting and coordinates cleansing.
     * 4. Extracts and processes dataset with route information (if present).
     * 5. Supports both point-based and pair-based uploads (for CSV with route).
     * 6. Handles various route purposes (addNewPlaces, reorderRoutes, reorganizeRoutes).
     * Function hierarchy:
     * 1. csvToArray
     *    1.1 processCsvHeader
     *        1.1.1 findFirstMatchingCsvPointColumnIndex
     *        1.1.2 findFirstMatchingCsvPairColumnIndex
     *        1.1.3 processCsvRouteHeaders
     *            1.1.3.1 findColumnIndices
     *            1.1.3.2 findPairColumnIndices
     *            1.1.3.3 validateCsvRouteHeaders
     *                1.1.3.3.1 throwCsvRouteHeaderError
     *    1.2 validateRow (for non-route data)
     *    1.3 processCsvDateFields
     *    1.4 processCsvCoordFields
     *    1.5 processCsvPointWithRoute (for point-based route data)
     *        1.5.1 getValidateRowWithRouteFunction
     *            1.5.1.1 getAddNewPlacesValidator
     *            1.5.1.2 getReorderValidator
     *            1.5.1.3 getReorganizeValidator
     *        1.5.2 processPointData
     *            1.5.2.1 processCsvDateFields
     *            1.5.2.2 processCsvCoordFields
     *            1.5.2.3 extractRouteDataFromCsvPoint
     *    1.6 processCsvPairWithRoute (for pair-based route data)
     *        (Similar structure to processCsvPointWithRoute)
     * Source: https://stackoverflow.com/questions/35220048/import-csv-file-to-laravel-controller-and-insert-data-to-two-tables
     * Note: Each line in the CSV must contain a value for all entries in the header.
     * Ivy's note:
     *      The function handles columns with various names, sanitizing date and coordinate values.
     *
     * !!!!!!
     * `$lines = str_getcsv($file->get(),"\n");`
     * This is failing to handle line breaks in cells. Need to handle that.
     * $lines = fgetcsv($file->get(), "\n");//Split entire file into array of lines on \n    OLD: explode(PHP_EOL,$file->get());
     * Try fgetcsv instead.
     *
     * Refactoring this entirely, as the old way didn't handle multiline cells. Output should be the same.
     * The purpose of this section is to get the CSV, sanitize the headers, and handle date formatting.
     * Note that handling the presence of columns by different names such as lat, lng, linkback, title, etc., is done elsewhere.
     * (Feels like it could/should be done in the same process. But just need to get it working so not digressing...
     * and it might make sense after all since this is just for CSV but later we can handle any input after ingest)
     * And reformat like this:
     * data[this] is now an array mapping header to field eg data[this] = ['placename' => 'newcastle', ... => ..., etc]
     *
     * @param mixed $file CSV file object to be converted.
     * @param Dataset $dataset The dataset object containing relevant information for validation.
     * @param bool $hasRoute Indicates whether the dataset includes route information.
     * @param bool $pointBasedUpload Indicates whether the upload is point-based (true) or pair-based (false).
     * @param string|null $routePurpose The purpose of route processing (null if no route).
     * @param string $delimiter Delimiter used in the CSV file, default is ','.
     *
     * @return array|int Returns an associative array containing:
     *                   - 'points': An array of processed CSV data.
     *                   - 'routes': An array of extracted route information on each point (if available).
     *                   Or returns a string containing error information if processing fails.
     *
     * @throws \Exception If there's an error opening the file.
     *
     * @note This function now handles multiline cells correctly using fgetcsv().
     * @note The function sanitizes headers, processes dates and coordinates, and validates data based on route purpose.
     * @note For non-route data, it performs basic row validation and processing.
     * @note For route data, it uses specialized processing functions based on upload type (point or pair).
     *
     * @details CSV Format and Output for Different Route Purposes:
     *
     * 1. addNewPlaces:
     *    CSV Format:
     *    - Required columns: regular point fields, route_id (OR route_ori_id)
     *    - Optional columns: stop_idx and other place-related information
     *    Output:
     *    - points: Array of place data including route_id
     *    - routes: Array of route data with id (route_id) OR ori_id (route_ori_id), and arrIdx matching to original point in $points
     *
     * 2. reorderRoutes:
     *    CSV Format:
     *    - Required columns: regular point fields, route_id, ghap_id
     *    - Optional columns: stop_idx and other place-related information
     *    Output:
     *    - points: Array of place data including route_id and ghap_id
     *    - routes: Array of route data with id (route_id), ghap_id, and arrIdx matching to original point in $points
     *
     * 3. reorganizeRoutes:
     *    CSV Format:
     *    - Required columns: regular point fields, route_id, ghap_id
     *    - New routes/places should use format: new_route_X, new_place_X
     *    - Optional columns: stop_idx and other place-related information
     *    Output:
     *    - points: Array of place data including route_id and ghap_id (new or existing)
     *    - routes: Array of route data with id, ghap_id, and arrIdx
     *
     * Note: For pair-based uploads, each row in the CSV represents both origin and destination,
     * and the column names should be prefixed accordingly (check $this->originPrefixes & $this->destintionPrefixes).
     *
     */
    function csvToArray($file,  $dataset, $hasRoute = FALSE, $pointBasedUpload = TRUE, $routePurpose = null, $delimiter = ',')
    {

        $header = [];
        $data = array();

        try {
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === FALSE) {
                LOG::error("File import error NO HANDLE");
                throw new \Exception('File import error NO HANDLE');
            }

            set_time_limit(0);

            $header = fgetcsv($handle);
            // extract and validate csv header
            $processedHeaderResults = $this->processCsvHeader($header, $hasRoute, $pointBasedUpload, $routePurpose);
            if ($processedHeaderResults['status']) {
                $processedHeader = $processedHeaderResults['data'];
            } else {
                $errorLog = $processedHeaderResults['error'];
                return $errorLog;
            }

            if ($hasRoute === false) {
                $outdata = [];
                $row = 0;

                $specialColumns = [];
                if (isset($processedHeader['routeindices']['route_id'])) {
                    $specialColumns['route_id'] = [
                        'index' => $processedHeader['routeindices']['route_id'],
                        'mustBeEmpty' => true
                    ];
                }
                $validateRow = !empty($specialColumns)
                    ? function ($data) use ($specialColumns) {
                        return $this->validateRowWithSpecialColumns($data, $specialColumns);
                    }
                    : [$this, 'validateRow'];
                $dateIndices = array_filter(
                    [
                        $processedHeader['datestartindices'],
                        $processedHeader['dateendindices']
                    ]
                );
                $coordIndices = array_filter([$processedHeader['latindices'], $processedHeader['lonindices']]);

                while (($data = fgetcsv($handle)) !== FALSE) {
                    $row++;

                    $validationResult = $validateRow($data);

                    if (!$validationResult['status']) {
                        if ($validationResult['stopProcess']) {
                            $errorLog = "Row $row: " . $validationResult['error'] . "\n";
                            return $errorLog; // If route_id has value in this situation, stop upload
                        }
                        continue; // if the row is invalid but just has all empty values, skip it
                    }

                    $fields = $data;

                    // Process date
                    $dateResult = $this->processCsvDateFields($fields, $dateIndices);

                    if (!$dateResult['status']) {
                        $errorLog = "Row $row: " . $dateResult['error'] . "\n";
                        return $errorLog;
                    }

                    // Process coordinates
                    $fields = $this->processCsvCoordFields($fields, $coordIndices);

                    // Combine headers and values of fields
                    $outdata[] = array_combine($processedHeader['header'], $fields); //data[this] is now an array mapping header to field eg data[this] = ['placename' => 'newcastle', ... => ..., etc]
                }
                $outdata['points'] = $outdata;
            } else {
                // processCsvWithRoute
                if ($pointBasedUpload) {
                    $outdata = $this->processCsvPointWithRoute($handle, $dataset, $processedHeader, $routePurpose);
                } else {
                    $outdata = $this->processCsvPairWithRoute($handle, $dataset, $processedHeader, $routePurpose);
                }
            }

            fclose($handle);
        } catch (Exception $e) {
            LOG::error('File Import Caught exception: ', $e->getMessage(), "\n");
        }

        return $outdata;
    }

    /*
    * Valid each row of the imported csv file.
    * Ignore blank rows, rows are just empty space or rows are just comma
    */
    function validateRow($array)
    {
        $hasNonEmptyValue = false;
        foreach ($array as $value) {
            $trimmedValue = trim($value);
            if ($trimmedValue !== '' && $trimmedValue !== ',') {
                $hasNonEmptyValue = true;
                break;
            }
        }
        return $hasNonEmptyValue
            ? ['status' => true]
            : ['status' => false, 'error' => 'Row contains only empty values.', 'stopProcess' => false];
    }

    /**
     * Validate a row with special column requirements.
     *
     * @param array $array The row data to validate.
     * @param array $specialColumns An array of special columns with their validation rules.
     * @return array Validation result with status, error message, and process control flag.
     */
    private function validateRowWithSpecialColumns($array, $specialColumns)
    {
        foreach ($specialColumns as $columnName => $rules) {
            $columnIndex = $rules['index'];
            $mustBeEmpty = $rules['mustBeEmpty'] ?? false;

            $value = trim($array[$columnIndex]);

            if ($mustBeEmpty && !empty($value)) {
                return [
                    'status' => false,
                    'error' => "Column '$columnName' must be empty but contains a value.",
                    'stopProcess' => true
                ];
            } elseif (!$mustBeEmpty && empty($value)) {
                return [
                    'status' => false,
                    'error' => "Column '$columnName' must not be empty but is empty.",
                    'stopProcess' => true
                ];
            }
        }

        $hasNonEmptyValue = false;
        foreach ($array as $index => $value) {
            if (!isset($specialColumns[$index])) {
                $trimmedValue = trim($value);
                if ($trimmedValue !== '' && $trimmedValue !== ',') {
                    $hasNonEmptyValue = true;
                    break;
                }
            }
        }

        return $hasNonEmptyValue
            ? ['status' => true]
            : ['status' => false, 'error' => 'Row contains only empty values.', 'stopProcess' => false];
    }

    /**
     * Sanitizes and standardizes the key (column names).
     *
     * @param string $s The input string to be sanitized.
     * @param bool $pointBasedUpload Determines which set of keys should be converted to lowercase.
     *      True for point-based upload, false for pair-based upload.
     * @return string The sanitized string after removing spaces, dodgy characters,
     *                converting to UTF-8, and applying lowercase handling for specific keys.
     */
    function sanitiseKey($s, $pointBasedUpload = True)
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
        if ($pointBasedUpload == TRUE) {
            $notForExtData = $this->pointBasedNotForExtData;
        } else {
            $notForExtData = $this->pairBasedNotForExtData;
        }

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
            $data[] = array(
                "title" => $feature->properties->name, "longitude" => $feature->geometry->coordinates[0],
                "latitude" => $feature->geometry->coordinates[1]
            ) + $ed_out; //adding on the extended data
        }
        return $data;
    }

    /**
     * Process CSV header to identify and store indices for date, coordinate, and route columns.
     *
     * This function performs the following operations:
     * 1. Sanitizes the header column names.
     * 2. Identifies and stores indices for date start, date end, and coordinate columns.
     * 3. Handles both point-based and pair-based upload formats.
     * 4. Processes route fields if applicable.
     *
     * @param array $header The original CSV header array.
     * @param bool $hasRoute Indicates whether the CSV contains route data.
     * @param bool $pointBasedUpload True for point-based upload, false for pair-based upload.
     * @param string $routePurpose The purpose of route processing (e.g., "addNewPlaces", "reorderRoutes").
     *
     * @return array An associative array containing:
     *               - 'header': Processed CSV header array
     *               - 'datestartindices': Array of indices for date start columns
     *               - 'dateendindices': Array of indices for date end columns
     *               - 'latindices': Array of indices for latitude columns
     *               - 'lonindices': Array of indices for longitude columns
     *               - 'routeindices': Array of indices for route columns (if applicable)
     *
     * @note For point-based uploads, date and coordinate indices are simple arrays.
     *       For pair-based uploads, these indices are nested arrays with 'origin' and 'destination' keys.
     *
     * @note The route indices ('routeindices') format depends on the upload type:
     *       - For point-based: A simple array of column name to index mappings.
     *       - For pair-based: A nested array with 'origin' and 'destination' keys, each containing column mappings.
     *
     * @note This function uses predefined lists of common column names for dates and coordinates,
     *       and applies appropriate prefixes for pair-based uploads.
     *
     * @see processCsvRouteHeaders() for detailed route column processing.
     *
     * @throws \RuntimeException If duplicate fields are found in pair-based uploads.
     *
     * @todo The current method is somewhat clumsy and may need optimization for handling
     *       wider spreadsheets in the future. For now, it's functional for typical use cases.
     */
    private function processCsvHeader(array $header, bool $hasRoute, bool $pointBasedUpload, $routePurpose): array
    {
        $sanitizedHeader = array_map(function ($item) use ($pointBasedUpload) {
            return $this->sanitiseKey($item, $pointBasedUpload);
        }, $header);

        if ($hasRoute && !$pointBasedUpload) {
            $processedHeader = [
                'origin' => [],
                'destination' => []
            ];
            $seenFields = [
                'origin' => [],
                'destination' => []
            ];
            foreach ($sanitizedHeader as $index => $column) {
                $isOrigin = false;
                $isDestination = false;

                // extract "origin" point column
                foreach ($this->originPrefixes as $prefix) {
                    if (strpos($column, $prefix) === 0) {
                        $fieldName = preg_replace('/^(' . preg_quote($prefix, '/') . ')_?/', '', $column);
                        if (isset($seenFields['origin'][$fieldName])) {
                            return [
                                'status' => false,
                                'error' => "Duplicate origin field: '{$seenFields['origin'][$fieldName]}' and '$column'"
                            ];
                        }
                        $processedHeader['origin'][$fieldName] = $index;
                        $seenFields['origin'][$fieldName] = $column;
                        $isOrigin = true;
                        break;
                    }
                }

                // extract "destination" point column
                if (!$isOrigin) {
                    foreach ($this->destintionPrefixes as $prefix) {
                        if (strpos($column, $prefix) === 0) {
                            $fieldName = preg_replace('/^(' . preg_quote($prefix, '/') . ')_?/', '', $column);
                            if (isset($seenFields['destination'][$fieldName])) {
                                return [
                                    'status' => false,
                                    'error' => "Duplicate destination field: '{$seenFields['destination'][$fieldName]}' and '$column'"
                                ];
                            }
                            $processedHeader['destination'][$fieldName] = $index;
                            $seenFields['destination'][$fieldName] = $column;
                            $isDestination = true;
                            break;
                        }
                    }
                }

                // extract shared columns
                if (!$isOrigin && !$isDestination) {
                    $processedHeader['origin'][$column] = $index;
                    $processedHeader['destination'][$column] = $index;
                }
            }
        } else {
            $processedHeader = $sanitizedHeader;
        }


        $indices = [
            'datestart' => $this->commonDateStartCols,
            'dateend' => $this->commonDateEndCols,
            'lat' => $this->commonLatCols,
            'lon' => $this->commonLonCols
        ];

        if ($pointBasedUpload) {
            $result = [];
            foreach ($indices as $key => $cols) {
                $result[$key . 'indices'] = $this->findFirstMatchingCsvPointColumnIndex($sanitizedHeader, $cols);
            }
        } else {
            foreach ($indices as $key => $cols) {
                $result[$key . 'indices'] = [
                    'origin' => $this->findFirstMatchingCsvPairColumnIndex($processedHeader['origin'], $cols),
                    'destination' => $this->findFirstMatchingCsvPairColumnIndex($processedHeader['destination'], $cols)
                ];
            }
        }

        $routeColsIndices = [];
        if ($hasRoute) {
            $routeColsIndices = $this->processCsvRouteHeaders($processedHeader, $pointBasedUpload, $routePurpose);
        } else {
            // Check for 'route_id' when user hasn't checked "Upload mobility dataset with routes"
            $routeIdIndex = array_search('route_id', $processedHeader);
            if ($routeIdIndex !== false) {
                $routeColsIndices['route_id'] = $routeIdIndex;
            }
        }

        return [
            'status' => true,
            'data' => array_merge([
                'header' => $processedHeader,
                'routeindices' => $routeColsIndices
            ], $result)
        ];
    }

    /**
     * Find the index of the first matching column in a point-based CSV header.
     *
     * This function searches through the header array for the first occurrence
     * of any string from the searchStrings array.
     *
     * @param array $header The CSV header array to search in
     * @param array $searchStrings An array of strings to search for in the header
     * @return int|null The index of the first matching column, or null if no match is found
     */
    private function findFirstMatchingCsvPointColumnIndex(array $header, array $searchStrings)
    {
        foreach ($searchStrings as $string) {
            $index = array_search($string, $header);
            if ($index !== false) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Find the value of the first matching column in a pair-based CSV header.
     *
     * This function searches through the header array for the first occurrence
     * of any string from the searchStrings array as a key, and returns its value.
     *
     * @param array $header The CSV header array to search in (key-value pairs)
     * @param array $searchStrings An array of strings to search for as keys in the header
     * @return mixed|null The value of the first matching column, or null if no match is found
     */
    private function findFirstMatchingCsvPairColumnIndex(array $header, array $searchStrings)
    {
        foreach ($searchStrings as $string) {
            if (isset($header[$string])) {
                return $header[$string];
            }
        }
        return null;
    }

    /**
     * Process CSV route headers and validate them based on the upload type and purpose.
     *
     * @param array $processedHeader The processed CSV header
     * @param bool $pointBasedUpload Whether the upload is point-based or pair-based
     * @param string $routePurpose The purpose of the route
     * @return array The indices of route columns
     *
     * Example return format for point-based upload:
     * [
     *     'route_id' => 0,
     *     'ghap_id' => 1,
     *     'stop_idx' => 2,
     *     // ...
     * ]
     *
     * Example return format for pair-based upload:
     * [
     *     'origin' => [
     *         'route_id' => 0,
     *         'ghap_id' => 1,
     *         // ...
     *     ],
     *     'destination' => [
     *         'route_id' => 5,
     *         'ghap_id' => 6,
     *         // ...
     *     ]
     * ]
     */
    private function processCsvRouteHeaders(array $processedHeader, bool $pointBasedUpload, string $routePurpose): array
    {
        /**
         * Shall we use $originPrefixes and $destintionPrefixes here?
         */
        $routeColsConfigs = [
            "addNewPlaces" => [
                "requiredCols" => ['route_id', 'route_ori_id'],
                "forbiddenCols" => [],
                "requireExclusice" => true
            ],
            "reorderRoutes" => [
                // "requiredCols" => ['route_id', 'ghap_id', 'origin_ghap_id', 'destination_ghap_id'],
                "requiredCols" => ['route_id', 'ghap_id'],
                "forbiddenCols" => ['route_ori_id'],
                "requireExclusice" => false
            ],
            "reorganizeRoutes" => [
                // "requiredCols" => ['route_id', 'ghap_id', 'origin_ghap_id', 'destination_ghap_id'],
                "requiredCols" => ['route_id', 'ghap_id'],
                "forbiddenCols" => ['route_ori_id'],
                "requireExclusice" => false
            ]
        ];

        if (!isset($routeColsConfigs[$routePurpose])) {
            throw new \InvalidArgumentException("Invalid route purpose: $routePurpose");
        }
        $routeColsConfigs = $routeColsConfigs[$routePurpose];
        $optionalRouteCols = ['stop_idx', 'route_title', 'route_description'];
        if ($routePurpose === "addNewPlaces") {
            $optionalRouteCols[] = 'ghap_id';
        }
        $routeCols = array_merge($routeColsConfigs['requiredCols'], $optionalRouteCols);

        if ($pointBasedUpload) {
            $routeCols = array_merge($routeColsConfigs['requiredCols'], $optionalRouteCols);
            $routeColsIndices = $this->findColumnIndices($processedHeader, $routeCols);
            $this->validateCsvRouteHeaders($processedHeader, $routeColsConfigs, $pointBasedUpload, $routePurpose);
        } else {
            $originRouteColsIndices = $this->findPairColumnIndices($processedHeader['origin'], $routeCols);
            $destinationRouteColsIndices = $this->findPairColumnIndices($processedHeader['destination'], $routeCols);
            $this->validateCsvRouteHeaders(array_keys($processedHeader['origin']), $routeColsConfigs, $routePurpose, 'origin');
            $this->validateCsvRouteHeaders(array_keys($processedHeader['destination']), $routeColsConfigs, $routePurpose, 'destination');
            $routeColsIndices = [
                'origin' => $originRouteColsIndices,
                'destination' => $destinationRouteColsIndices
            ];
        }

        return $routeColsIndices;
    }

    /**
     * Find column indices for point-based uploads.
     *
     * @param array $header The CSV header
     * @param array $columns The columns to find
     * @return array An associative array of column names and their indices
     */
    private function findColumnIndices(array $header, array $columns)
    {
        $indices = [];
        foreach ($columns as $column) {
            $index = array_search($column, $header);
            if ($index !== false) {
                $indices[$column] = $index;
            }
        }
        return $indices;
    }

    /**
     * Find column indices for pair-based uploads.
     *
     * @param array $header The processed header for origin or destination
     * @param array $columns The columns to find
     * @return array An associative array of column names and their indices
     */
    private function findPairColumnIndices(array $header, array $columns)
    {
        $indices = [];
        foreach ($columns as $column) {
            if (isset($header[$column])) {
                $indices[$column] = $header[$column];
            }
        }
        return $indices;
    }

    /**
     * Validate CSV route headers against the configuration.
     *
     * @param array $allCols All columns related to the point within the route in CSV
     * @param array $config The configuration for the route purpose
     * @param string $routePurpose The purpose of the route
     * @param string $pointType The point type (origin or destination) for pair-based uploads
     * @throws \RuntimeException If validation fails
     */
    private function validateCsvRouteHeaders(array $allCols, array $config, string $routePurpose, string $pointType = ''): void
    {
        // $allCols = array_keys($routeColsIndices);

        // Check forbidden columns
        $forbiddenColsPresent = array_intersect($config['forbiddenCols'], $allCols);
        if (!empty($forbiddenColsPresent)) {
            $this->throwCsvRouteHeaderError("Forbidden route columns present", $forbiddenColsPresent, $routePurpose);
        }

        // Check required columns
        if ($config['requireExclusice']) {
            $intersection = array_intersect($config['requiredCols'], $allCols);
            $intersectionCount = count($intersection);
            if ($intersectionCount !== 1) {
                if ($intersectionCount === 0) {
                    $this->throwCsvRouteHeaderError("Missing required route column", $config['requiredCols'], $routePurpose, $pointType, " or ");
                } else {
                    $this->throwCsvRouteHeaderError("Only one of the required route columns should be present", $config['requiredCols'], $routePurpose, $pointType, " or ");
                }
            }
        } else {
            $missingCols = array_diff($config['requiredCols'], $allCols);
            if (!empty($missingCols)) {
                $this->throwCsvRouteHeaderError("Missing required route columns", $missingCols, $routePurpose, $pointType);
            }
        }
    }

    /**
     * Throw a CSV route header error.
     *
     * @param string $message The error message
     * @param array $columns The columns involved in the error
     * @param string $routePurpose The purpose of the route
     * @param string $pointType The point type (origin or destination) for pair-based uploads
     * @param string $separator The separator to use when joining column names
     * @throws \RuntimeException
     */
    private function throwCsvRouteHeaderError(string $message, array $columns, string $routePurpose, string $pointType = '', string $separator = ", "): void
    {
        $columnStr = implode($separator, $columns);
        $pointTypeStr = $pointType ? " for $pointType" : "";
        $errorMsg = "$message$pointTypeStr for $routePurpose: $columnStr";
        LOG::error($errorMsg);
        throw new \RuntimeException($errorMsg);
    }

    /**
     * Process date fields in the CSV data.
     *
     * @param array $fields The array of fields from a CSV row.
     * @param array $dateIndices The indices of date fields to process.
     * @return array An associative array with 'status' and either 'error' or 'data' keys.
     *               If successful, returns ['status' => true, 'data' => processed_fields].
     *               If an error occurs, returns ['status' => false, 'error' => error_message].
     */
    private function processCsvDateFields(array $fields, array $dateIndices)
    {
        // Check if $dateIndices contains only false values, then set it to an empty array
        foreach ($dateIndices as $dateIndex) {
            $parsedate = GeneralFunctions::dateMatchesRegexAndConvertString($fields[$dateIndex]);
            if ($parsedate === -1) {
                return [
                    'status' => false,
                    'error' => "Invalid date format in column $dateIndex"
                ];
            } else {
                $fields[$dateIndex] = $parsedate;
            }
        }
        return [
            'status' => true,
            'data' => $fields
        ];
    }

    /**
     * Process coordinate (latitude & longitude) fields in the CSV data.
     *
     * @param array $fields The array of fields from a CSV row.
     * @param array $coordIndices The indices of coordinate fields to process.
     * @return array The processed fields array with sanitized coordinate values.
     */
    private function processCsvCoordFields(array $fields, $coordIndices)
    {
        foreach ($coordIndices as $coordIndex) {
            $fields[$coordIndex] = preg_replace('/[^\d\.-]/', '', str_replace("\xc2\xa0", ' ', $fields[$coordIndex]));
        }
        return $fields;
    }

    /**
     * Get the appropriate row validation function for CSV row based on the route purpose.
     *
     * @param Dataset $dataset The dataset object containing relevant information for validation.
     * @param array $processedHeader Processed header information.
     * @param string $routePurpose The purpose of route processing ('addNewPlaces', 'reorderRoutes', or 'reorganizeRoutes').
     * @param array $routeFields Array of route field names.
     * @return callable A function to validate CSV rows based on the specified route purpose.
     */
    private function getValidateRowWithRouteFunction($dataset, $processedHeader, $routePurpose, $routeFields)
    {
        if ($routePurpose === "addNewPlaces") {
            return $this->getAddNewPlacesValidator($dataset, $processedHeader, $routeFields);
        } elseif ($routePurpose === "reorderRoutes") {
            return $this->getReorderValidator($dataset, $processedHeader);
        } elseif ($routePurpose === "reorganizeRoutes") {
            return $this->getReorganizeValidator($dataset, $processedHeader);
        } else {
            return [$this, 'validateRow'];
        }
    }

    /**
     * Get a validator function for the 'addNewPlaces' route purpose.
     *
     * @param Dataset $dataset The dataset object containing relevant information for validation.
     * @param array $processedHeader Processed header information.
     * @param array $routeFields Array of route field names.
     * @return callable A function to validate CSV rows for adding new places.
     */
    private function getAddNewPlacesValidator($dataset, $processedHeader, $routeFields)
    {
        if (in_array('route_id', $routeFields)) {
            $routeIds = $dataset->getAllRouteIdsAsStrings();
            $routeIdIndex = isset($processedHeader['routeindices']['route_id'])
                ? $processedHeader['routeindices']['route_id']
                : null;

            return function ($row) use ($routeIds, $routeIdIndex) {
                $hasNonEmptyValue = false;
                $routeIdValue = null;

                foreach ($row as $index => $value) {
                    $trimmedValue = trim($value);
                    if ($trimmedValue !== '' && $trimmedValue !== ',') {
                        $hasNonEmptyValue = true;
                        if ($index === $routeIdIndex) {
                            $routeIdValue = $trimmedValue;
                            break;
                        }
                    }
                }

                if (!$hasNonEmptyValue) {
                    return ['status' => true, 'error' => 'Empty row'];
                }

                if ($routeIdValue === null) {
                    return ['status' => false, 'error' => 'Missing route_id'];
                }

                if (!in_array($routeIdValue, $routeIds)) {
                    return ['status' => false, 'error' => 'Invalid route_id'];
                }

                return ['status' => true];
            };
        } elseif (
            isset($processedHeader['routeindices']['route_ori_id']) && !isset($processedHeader['routeindices']['ghap_id'])
        ) {
            /**
             * $processedHeader['routeindices']['route_ori_id'] must have index, otherwise it must have thrown error when processing CSV headers
             * when the csv only set route_ori_id.
             * When the csv set route_ori_id, "route_ori_id" column would be the only column for identifier of (new) route,
             * so it must have value
             */
            $specialColumns['route_ori_id'] = [
                'index' => $processedHeader['routeindices']['route_ori_id'],
                'mustBeEmpty' => false
            ];
            return function ($row) use ($specialColumns) {
                return $this->validateRowWithSpecialColumns($row, $specialColumns);
            };
        } elseif (isset($processedHeader['routeindices']['ghap_id'])) {
            //When the csv has route_ori_id + ghap_id, the place having the uid (ghap_id) must not have route_id in DB (It must be an isolated place)
            $routeOriIdIndex = $processedHeader['routeindices']['route_ori_id'];
            $ghapIdIndex = $processedHeader['routeindices']['ghap_id'];

            $isolatedPlaceGhapIds = $dataset->getUIDsOfIsolatedPlaces()->flip()->all();

            return function ($row) use ($routeOriIdIndex, $ghapIdIndex, $isolatedPlaceGhapIds) {
                $hasNonEmptyValue = false;
                $routeOriIdValue = null;
                $ghapIdValue = null;

                foreach ($row as $index => $value) {
                    $trimmedValue = trim($value);
                    if ($trimmedValue !== '' && $trimmedValue !== ',') {
                        $hasNonEmptyValue = true;
                        if ($index === $routeOriIdIndex) {
                            $routeOriIdValue = $trimmedValue;
                        } elseif ($index === $ghapIdIndex) {
                            $ghapIdValue = $trimmedValue;
                        }
                        if ($routeOriIdValue !== null && $ghapIdValue !== null) {
                            break;
                        }
                    }
                }

                if (!$hasNonEmptyValue) {
                    return ['status' => true, 'error' => 'Empty row'];
                }

                if (empty($routeOriIdValue)) {
                    return [
                        'status' => false,
                        'error' => "Column 'route_ori_id' must not be empty but is empty.",
                        'stopProcess' => true
                    ];
                }
                if (!isset($isolatedPlaceGhapIds[$ghapIdValue])) {
                    return [
                        'status' => false,
                        'error' => "Place with ghap_id '{$ghapIdValue}' is not an isolated place or does not exist.",
                        'stopProcess' => true
                    ];
                }

                return ['status' => true];
            };
        }
    }

    /**
     * Get a validator function for the 'reorderRoutes' route purpose.
     *
     * @param Dataset $dataset The dataset object containing relevant information for validation.
     * @param array $processedHeader Processed header information.
     * @return callable A function to validate CSV rows for reordering routes.
     */
    private function getReorderValidator($dataset, $processedHeader)
    {
        $routeDataItems = $dataset->getDataitemsWithRouteIDAndUIDAsStrings();
        $routeIdIndex = $processedHeader['routeindices']['route_id'];
        $ghapIdIndex = $processedHeader['routeindices']['ghap_id'];

        return function ($row) use ($routeDataItems, $routeIdIndex, $ghapIdIndex) {
            $result = $this->validateRouteAndGhapId($row, $routeIdIndex, $ghapIdIndex);
            if (!$result['status']) {
                return $result;
            }

            $routeId = $result['routeId'];
            $ghapId = $result['ghapId'];

            if (!isset($routeDataItems[$routeId]) || !in_array($ghapId, $routeDataItems[$routeId]['uids'])) {
                return ['status' => false, 'error' => 'Invalid route_id or ghap_id'];
            }

            return ['status' => true];
        };
    }

    /**
     * Get a validator function for the 'reorganizeRoutes' route purpose.
     *
     * @param Dataset $dataset The dataset object containing relevant information for validation.
     * @param array $processedHeader Processed header information.
     * @return callable A function to validate CSV rows for reorganizing routes.
     */
    private function getReorganizeValidator($dataset, $processedHeader)
    {
        $routeDataItems = $dataset->getDataitemsWithRouteIDAndUIDAsStrings();
        $routeIdIndex = $processedHeader['routeindices']['route_id'];
        $ghapIdIndex = $processedHeader['routeindices']['ghap_id'];

        $allUids = [];
        foreach ($routeDataItems as $routeData) {
            $allUids = array_merge($allUids, $routeData['uids']);
        }
        $allUids = array_unique($allUids);

        return function ($row) use ($routeDataItems, $routeIdIndex, $ghapIdIndex, $allUids) {
            $result = $this->validateRouteAndGhapId($row, $routeIdIndex, $ghapIdIndex);
            if (!$result['status']) {
                return $result;
            }

            $routeId = $result['routeId'];
            $ghapId = $result['ghapId'];

            $routeExists = isset($routeDataItems[$routeId]);
            $isValidRouteId = $routeExists || preg_match('/^[Nn]ew_route_[0-9]+$/', $routeId);
            $isNewGhapId = substr($ghapId, 0, 1) !== 't';

            $isValidGhapId = in_array($ghapId, $allUids) || $isNewGhapId;
            if ($isValidRouteId && $isValidGhapId) {
                return ['status' => true];
            } else {
                return ['status' => false, 'error' => 'Invalid route_id or ghap_id'];
            }
        };
    }

    /**
     * Validate route_id and ghap_id fields in a CSV row.
     *
     * @param array $row The CSV row data.
     * @param int $routeIdIndex The index of the route_id field.
     * @param int $ghapIdIndex The index of the ghap_id field.
     * @return array An associative array with validation status and extracted IDs if successful.
     */
    private function validateRouteAndGhapId($row, $routeIdIndex, $ghapIdIndex)
    {
        $hasNonEmptyValue = false;
        $routeId = null;
        $ghapId = null;

        foreach ($row as $index => $value) {
            $trimmedValue = trim($value);
            if ($trimmedValue !== '' && $trimmedValue !== ',') {
                $hasNonEmptyValue = true;
                if ($index === $routeIdIndex) {
                    $routeId = $trimmedValue;
                } elseif ($index === $ghapIdIndex) {
                    $ghapId = $trimmedValue;
                }
                if ($hasNonEmptyValue && $routeId !== null && $ghapId !== null) {
                    break;
                }
            }
        }

        if (!$hasNonEmptyValue) {
            return ['status' => true, 'error' => 'Empty row'];
        }
        if ($routeId === null) {
            return ['status' => false, 'error' => 'Missing route_id'];
        }
        if ($ghapId === null) {
            return ['status' => false, 'error' => 'Missing ghap_id'];
        }

        return ['status' => true, 'routeId' => $routeId, 'ghapId' => $ghapId];
    }

    /**
     * Process point data from CSV fields.
     *
     * @param array $fields The array of fields from a CSV row.
     * @param array $dateIndices The indices of date fields to process.
     * @param array $coordIndices The indices of coordinate fields to process.
     * @param array $routeFields The indices of route fields to process.
     * @return array An associative array with processing status, processed fields, and extracted route data.
     */
    private function processPointData($fields, $dateIndices, $coordIndices, $routeFields)
    {
        // Process date fields
        $dateResult = $this->processCsvDateFields($fields, $dateIndices);
        if (!$dateResult['status']) {
            return ['status' => false, 'error' => $dateResult['error']];
        }

        // Process coordinates fields
        $fields = $this->processCsvCoordFields($fields, $coordIndices);

        // Process route fields
        $routeData = $this->extractRouteDataFromCsvPoint($fields, $routeFields);

        return [
            'status' => true,
            'fields' => $fields,
            'routeData' => $routeData
        ];
    }

    /**
     * Extract route data from a CSV row array.
     *
     * This static method processes a single row of CSV data to extract route information
     * based on the provided field mappings.
     *
     * @param array $fields An array representing a single row of CSV data.
     * @param array $routeIndices An associative array where keys are route field names
     *                                and values are their corresponding column indices in the CSV.
     * @return array An associative array containing the extracted route data.
     */
    private static function extractRouteDataFromCsvPoint($fields, $routeIndices)
    {
        $routeData = [];
        foreach ($routeIndices as $fieldName => $columnIndex) {
            if ($columnIndex !== null && isset($fields[$columnIndex])) {
                $key = str_replace('route_', '', $fieldName);
                // lower id & ori_id
                $routeData[$key] = $fields[$columnIndex];
            }
        }
        return $routeData;
    }

    /**
     * Process CSV data that includes route information for point-based uploads.
     *
     * This function reads a CSV file line by line, validates each row, processes date and coordinate fields,
     * and extracts route data. It handles different route purposes such as adding new places,
     * reordering routes, or reorganizing routes.
     *
     * @param resource $handle The file handle for the CSV file.
     * @param Dataset $dataset The dataset object containing relevant information for validation.
     * @param array $processedHeader The processed header information including indices for various field types.
     * @param string $routePurpose The purpose of route processing ('addNewPlaces', 'reorderRoutes', or 'reorganizeRoutes').
     *
     * @return array|string An array containing 'points' and 'routes' data if successful,
     *                      or a string containing error information if an error occurs during processing.
     *
     * @throws \Exception If there's an error in file reading or data processing.
     */
    private function processCsvPointWithRoute($handle, $dataset, $processedHeader, $routePurpose)
    {
        $outdata = [];
        $outRouteData = [];
        $outdataIdx = 0;
        $row = 0; // Header has been processed, start from row 1

        $dateIndices = array_filter(
            [
                $processedHeader['datestartindices'],
                $processedHeader['dateendindices']
            ]
        );
        $coordIndices = array_filter([$processedHeader['latindices'], $processedHeader['lonindices']]);
        //get names of route fields
        $routeFields = array_keys($processedHeader['routeindices']);
        // set validateRow according to routePurpose
        $validateRow = $this->getValidateRowWithRouteFunction(
            $dataset,
            $processedHeader,
            $routePurpose,
            $routeFields
        );

        while (($data = fgetcsv($handle)) !== FALSE) {
            $row++;

            $validationResult = $validateRow($data);

            if (!$validationResult['status']) {
                $errorLog = "Row $row: " . $validationResult['error'] . "\n";
                return $errorLog;
            }

            // Process point in the row
            $processedPoint = $this->processPointData($data, $dateIndices, $coordIndices, $processedHeader['routeindices']);
            if (!$processedPoint['status']) {
                return "Row $row: " . $processedPoint['error'] . "\n";
            }

            $fields = $processedPoint['fields'];
            $routeData = $processedPoint['routeData'];
            $routeData['arrIdx'] = $outdataIdx;

            $outRouteData[] = $routeData;
            $outdata[] = array_combine($processedHeader['header'], $fields);
            $outdataIdx++;
        }

        return ['points' => $outdata, 'routes' => $outRouteData];
    }

    /**
     * Process CSV data that includes route information for pair-based uploads.
     *
     * This function reads a CSV file line by line, validates each row, processes date and coordinate fields,
     * and extracts route data. It handles different route purposes such as adding new places,
     * reordering routes, or reorganizing routes.
     *
     * @param resource $handle The file handle for the CSV file.
     * @param Dataset $dataset The dataset object containing relevant information for validation.
     * @param array $processedHeader The processed header information including indices for various field types.
     * @param string $routePurpose The purpose of route processing.
     * @return array|string An array containing 'points' and 'routes' data if successful, or an error string.
     */
    private function processCsvPairWithRoute($handle, $dataset, $processedHeader, $routePurpose)
    {
        $outdata = [];
        $outRouteData = [];
        $outdataIdx = 0;
        $row = 0;

        $originHeader = [
            'header' => $processedHeader['header']['origin'],
            'datestartindices' => isset($processedHeader['datestartindices']['origin']) ? $processedHeader['datestartindices']['origin'] : null,
            'dateendindices' => isset($processedHeader['dateendindices']['origin']) ? $processedHeader['dateendindices']['origin'] : null,
            'latindices' => $processedHeader['latindices']['origin'],
            'lonindices' => $processedHeader['lonindices']['origin'],
            'routeindices' => $processedHeader['routeindices']['origin']
        ];
        $originRouteFields = $processedHeader['routeindices']['origin'];
        $originDateIndices = array_filter([$originHeader['datestartindices'], $originHeader['dateendindices']]);
        $originCoordIndices = array_filter([$originHeader['latindices'], $originHeader['lonindices']]);

        $destHeader = [
            'header' => $processedHeader['header']['destination'],
            'datestartindices' => isset($processedHeader['datestartindices']['destination']) ? $processedHeader['datestartindices']['destination'] : null,
            'dateendindices' => isset($processedHeader['dateendindices']['destination']) ? $processedHeader['dateendindices']['destination'] : null,
            'latindices' => $processedHeader['latindices']['destination'],
            'lonindices' => $processedHeader['lonindices']['destination'],
            'routeindices' => $processedHeader['routeindices']['destination']
        ];
        $destRouteFields = $processedHeader['routeindices']['destination'];
        $destDateIndices = array_filter([$destHeader['datestartindices'], $destHeader['dateendindices']]);
        $destCoordIndices = array_filter([$destHeader['latindices'], $destHeader['lonindices']]);

        $validateRowOrigin = $this->getValidateRowWithRouteFunction($dataset, $originHeader, $routePurpose, $originRouteFields);
        $validateRowDest = $this->getValidateRowWithRouteFunction($dataset, $destHeader, $routePurpose, $destRouteFields);

        while (($data = fgetcsv($handle)) !== FALSE) {
            $row++;

            // Validate O point & D point
            $originValidationResult = $validateRowOrigin($data);
            if (!$originValidationResult['status']) {
                return "Row $row (Origin): " . $originValidationResult['error'];
            }
            $destValidationResult = $validateRowDest($data);
            if (!$destValidationResult['status']) {
                return "Row $row (Destination): " . $destValidationResult['error'];
            }

            // Clean (and validate) O point & D point
            $processedOrigin = $this->processPointData(
                $data,
                $originDateIndices,
                $originCoordIndices,
                $originHeader['routeindices']
            );
            if (!$processedOrigin['status']) {
                return "Row $row (Origin): " . $processedOrigin['error'];
            }
            $processedDest = $this->processPointData(
                $data,
                $destDateIndices,
                $destCoordIndices,
                $destHeader['routeindices']
            );
            if (!$processedDest['status']) {
                return "Row $row (Destination): " . $processedDest['error'];
            }

            // Collect O point and route
            $originRouteData = $processedOrigin['routeData'];
            $originRouteData['arrIdx'] = $outdataIdx;
            $outRouteData[] = $originRouteData;
            $originFields = [];
            foreach ($originHeader['header'] as $fieldName => $index) {
                if (is_int($index) && isset($processedOrigin['fields'][$index])) {
                    $originFields[$fieldName] = $processedOrigin['fields'][$index];
                } else {
                    $originFields[$fieldName] = null;
                }
            }
            $outdata[] = $originFields;
            $outdataIdx++;

            // Collect D point and route
            $destRouteData = $processedDest['routeData'];
            $destRouteData['arrIdx'] = $outdataIdx;
            $outRouteData[] = $destRouteData;
            $destFields = [];
            foreach ($destHeader['header'] as $fieldName => $index) {
                if (is_int($index) && isset($processedDest['fields'][$index])) {
                    $destFields[$fieldName] = $processedDest['fields'][$index];
                } else {
                    $destFields[$fieldName] = null;
                }
            }
            $outdata[] = $destFields;
            $outdataIdx++;
        }

        return ['points' => $outdata, 'routes' => $outRouteData];
    }

    /**
     * Prepare route data based on the specified purpose.
     *
     * @param array $routes An array of route information.
     * @param string $routePurpose The purpose of route processing ('addNewPlaces', 'reorderRoutes', or 'reorganizeRoutes').
     * @return \Illuminate\Support\Collection A collection of prepared route data.
     * @throws \Exception If an invalid route purpose is provided.
     *
     * The structure of $preparedRoutes is as follows:
     * ['ROUTE_1_KEY':
     *     {
     *         'id': string|int,          // Route ID (string for new routes, int for existing ones)
     *         'isNew': bool,             // Whether this is a new route
     *         'insertStopIdx': int|string, // Where to insert new stops ('append' or an integer)
     *         'title': string,           // Route title (optional, present in all cases)
     *         'description': string,     // Route description (optional, present in all cases)
     *         'dataitems': [             // Array of data items for this route
     *             {
     *                 'arrIdx': int,     // Original array index
     *                 'sortIndex': int   // Sorted index
     *             },
     *             // ... more data items
     *         ]
     *     },
     *     // ... more routes
     * ]
     *
     * Note: The presence and content of certain fields may vary depending on the specific route purpose.
     */
    public function prepareRoutes($routes, $routePurpose)
    {
        $preparedRoutes = array();

        switch ($routePurpose) {
            case 'addNewPlaces':
                $preparedRoutes = $this->prepareRouteAddNewPlaces($routes);
                break;
            case 'reorderRoutes':
                $preparedRoutes = $this->prepareRouteReorderRoutes($routes);
                break;
            case 'reorganizeRoutes':
                $preparedRoutes = $this->prepareRouteReorganizeRoutes($routes);
                break;
            default:
                throw new \Exception("Invalid route purpose");
        }

        return $preparedRoutes;
    }

    /**
     * Prepare route data for adding new places.
     *
     * @param array $routes Array of route information for each point.
     * @return \Illuminate\Support\Collection A collection of prepared route data.
     *
     * Example of the returned collection structure:
     * ['ROUTE_1_KEY':
     *     {
     *         'id': 'new_route_1',       // String ID for new routes
     *         'isNew': true,             // Always true for new routes
     *         'insertStopIdx': 'append', // 'append' or an integer
     *         'title': 'New Route 1',    // Title is the route ID for new routes if not provided
     *         'description': 'A new route description', // Optional
     *         'dataitems': [
     *             { 'arrIdx': 0, 'sortIndex': 0 },
     *             { 'arrIdx': 1, 'sortIndex': 1 }
     *         ]
     *     },
     *     // ... more routes
     * ]
     */
    private function prepareRouteAddNewPlaces($routes)
    {
        $routeCollection = collect($routes);

        return $routeCollection
            ->groupBy(function ($route) {
                // Group by id or ori_id, whichever is present
                return isset($route['id']) ? $route['id'] : $route['ori_id'];
            })
            ->map(function ($groupedRoutes, $routeId) {
                $firstRoute = $groupedRoutes->first();
                $isNew = !isset($firstRoute['id']);
                $hasStopIdx = isset($firstRoute['stop_idx']);

                $minStopIdx = $groupedRoutes->pluck('stop_idx')->filter()->min();
                if ($minStopIdx === null || $minStopIdx <= 0) {
                    $minStopIdx = 1;
                }

                $route = [
                    'id' => $isNew ? $routeId : (int)$routeId,
                    'isNew' => $isNew,
                    // $minStopIdx is not guaranteed to be valid (could larger than exisiting route size, we shall validate it in route creationg process)
                    'insertStopIdx' => $hasStopIdx ? $minStopIdx : 'append',
                    'dataitems' => $groupedRoutes
                        ->sortBy(function ($route) use ($hasStopIdx) {
                            return $hasStopIdx ? [$route['stop_idx'] ?? PHP_INT_MAX, $route['arrIdx']] : $route['arrIdx'];
                        })
                        ->values()
                        ->map(function ($route, $index) {
                            return [
                                'arrIdx' => $route['arrIdx'],
                                'sortIndex' => $index,
                            ];
                        }),
                ];

                $route += $this->addRouteTitleAndDescription($groupedRoutes, $routeId, $isNew);

                return $route;
            });
    }

    /**
     * Prepare route data for reordering.
     *
     * @param array $routes An array of route information.
     * @return \Illuminate\Support\Collection A collection of prepared route data.
     *
     * Example of the returned collection structure:
     * ['ROUTE_1_KEY':
     *     {
     *         'id': 1,                   // Integer ID for existing routes
     *         'isNew': false,            // Always false for reordering
     *         'insertStopIdx': 2,        // Minimum stop_idx or 'append'
     *         'title': 'Existing Route', // Optional
     *         'description': 'An existing route', // Optional
     *         'dataitems': [
     *             { 'arrIdx': 2, 'sortIndex': 0 },
     *             { 'arrIdx': 1, 'sortIndex': 1 },
     *             { 'arrIdx': 0, 'sortIndex': 2 }
     *         ]
     *     },
     *     // ... more routes
     * ]
     */
    private function prepareRouteReorderRoutes($routes)
    {
        $routeCollection = collect($routes);

        return $routeCollection
            ->groupBy('id')
            ->map(function ($groupedRoutes, $routeId) {
                // Check if any route in the group has a stop_idx
                $hasStopIdx = isset($groupedRoutes->first()['stop_idx']);
                // Get the minimum stop_idx if present
                $minStopIdx = $groupedRoutes->pluck('stop_idx')->filter()->min();
                if ($minStopIdx === null || $minStopIdx <= 0) {
                    $minStopIdx = 1;
                }

                $route = [
                    'id' => (int)$routeId,
                    'isNew' => false,
                    'insertStopIdx' => $hasStopIdx ? $minStopIdx : 'append',
                    'dataitems' => $groupedRoutes
                        ->sortBy(function ($route) use ($hasStopIdx) {
                            return $hasStopIdx ? [$route['stop_idx'] ?? PHP_INT_MAX, $route['arrIdx']] : $route['arrIdx'];
                        })
                        ->values()
                        ->map(function ($route, $index) {
                            return [
                                'arrIdx' => $route['arrIdx'],
                                'sortIndex' => $index,
                            ];
                        }),
                ];

                $route += $this->addRouteTitleAndDescription($groupedRoutes, $routeId);

                return $route;
            });
    }

    /**
     * Prepare route data for reorganizing routes.
     *
     * @param array $routes An array of route information.
     * @return \Illuminate\Support\Collection A collection of prepared route data.
     *
     * Example of the returned collection structure:
     * [
     *     'ROUTE_1_KEY' => [
     *         'id' => 'new_route_2',       // String for new routes, int for existing
     *         'isNew' => true,             // true for new routes, false for existing
     *         'insertStopIdx' => 1,        // Integer or 'append'
     *         'title' => 'Reorganized Route', // Optional
     *         'description' => 'A reorganized route', // Optional
     *         'dataitems' => [
     *             ['arrIdx' => 1, 'sortIndex' => 0],
     *             ['arrIdx' => 0, 'sortIndex' => 1],
     *             ['arrIdx' => 2, 'sortIndex' => 2]
     *         ]
     *     ],
     *     // ... more routes
     * ]
     */
    private function prepareRouteReorganizeRoutes($routes)
    {
        $routeCollection = collect($routes);

        return $routeCollection
            ->groupBy('id')
            ->map(function ($groupedRoutes, $routeId) {
                $isNew = preg_match('/^[Nn]ew_route/', $routeId) !== 0;
                $id = $isNew ? $routeId : (int)$routeId;

                $hasStopIdx = isset($groupedRoutes->first()['stop_idx']);
                $minStopIdx = $groupedRoutes->pluck('stop_idx')->filter()->min();
                if ($minStopIdx === null || $minStopIdx <= 0) {
                    $minStopIdx = 1;
                }

                $route = [
                    'id' => $id,
                    'isNew' => $isNew,
                    'insertStopIdx' => $hasStopIdx ? $minStopIdx : 'append',
                    'dataitems' => $groupedRoutes
                        ->sortBy(function ($route) use ($hasStopIdx) {
                            return $hasStopIdx ? [$route['stop_idx'] ?? PHP_INT_MAX, $route['arrIdx']] : $route['arrIdx'];
                        })
                        ->values()
                        ->map(function ($route, $index) {
                            return [
                                'arrIdx' => $route['arrIdx'],
                                'sortIndex' => $index,
                            ];
                        }),
                ];

                $route += $this->addRouteTitleAndDescription($groupedRoutes, $routeId, $isNew);

                return $route;
            });
    }

    /**
     * Add title and description to the route data.
     *
     * This function extracts unique titles and descriptions from the grouped routes
     * and adds them to the route data if they exist. It is a helper function for prepareRouteXXXXX.
     *
     * @param \Illuminate\Support\Collection $groupedRoutes Collection of routes grouped by ID.
     * @param string|int $routeId The ID of the current route.
     * @param bool $isNew Whether the route is new (default: false).
     * @return array An array containing title and/or description if they exist.
     */
    private function addRouteTitleAndDescription($groupedRoutes, $routeId, $isNew = false)
    {
        $result = [];

        // Collect all unique titles and descriptions
        $titles = $groupedRoutes->pluck('title')->unique()->filter();
        $descriptions = $groupedRoutes->pluck('description')->unique()->filter();

        // Add title if it exists
        if ($titles->isNotEmpty()) {
            $result['title'] = $titles->first();
        } elseif ($isNew) {
            $result['title'] = (string)$routeId;
        }

        // Add description if it exists
        if ($descriptions->isNotEmpty()) {
            $result['description'] = $descriptions->first();
        }

        return $result;
    }

    /**
     * Create new routes and update existing ones in the database.
     * This function handles both the creation of new routes and the updating of existing routes.
     * It also ensures that each route's dataitems are associated with the correct route_id.
     *
     * The function performs the following operations:
     * 1. Separates new routes from existing routes in the input collection.
     * 2. For new routes:
     *    - Inserts them into the database.
     *    - Updates the route objects with newly assigned IDs.
     *    - Adds the route_id to each dataitem within the route.
     * 3. For existing routes:
     *    - Updates their 'size' attribute in the database. (Ignore now)
     *    - Refreshes the route objects with updated data from the database.
     *    - Adds or updates the route_id for each dataitem within the route.
     * 4. Merges and returns the updated collection of both new and existing routes.
     *
     * @param \Illuminate\Support\Collection $routes Collection of routes to be created or updated
     * @param int $ds_id Dataset ID
     * @return \Illuminate\Support\Collection Updated collection of routes
     */
    function createRoutes($routes, $ds_id)
    {
        // Separate new routes and existing routes
        $newRoutes = $routes->where('isNew', true);
        $existingRoutes = $routes->where('isNew', false);

        if ($existingRoutes->isNotEmpty()) {

            // Batch update existing routes and get updated data
            $updatedCount = Route::updateRouteMetadataBatch($existingRoutes);

            // Update existing routes with new data
            if ($updatedCount > 0) {
                $existingRoutes = $existingRoutes->map(function ($route) {
                    // Add route_id into route.dataitems
                    $route['dataitems'] = collect($route['dataitems'])->map(function ($dataitem) use ($route) {
                        $dataitem['route_id'] = $route['id'];
                        return $dataitem;
                    })->toArray();
                    return $route;
                });
            }
        }

        if ($newRoutes->isNotEmpty()) {
            // Convert $newRoutes to an indexed array while preserving original keys
            $indexedRoutes = $newRoutes->values();
            $originalKeys = $newRoutes->keys()->toArray();

            // Prepare data for new routes
            $newRoutesData = $indexedRoutes->map(function ($route) use ($ds_id) {
                return [
                    'title' => $route['title'],
                    'description' => $route['description'] ?? null,
                    'dataset_id' => $ds_id,
                ];
            })->toArray();

            /**
             * NOTE: This is not an ideal practice for large datasets as it inserts routes one by one,
             * which can be slow when dealing with a large number of records.
             * If possible, consider upgrading to a newer version of Laravel that supports createMany(),
             * which would allow for more efficient bulk insertion.
             *
             * @todo Replace this loop with createMany() when upgrading to a compatible Laravel version.
             */
            $createdRoutes = collect();
            foreach ($newRoutesData as $routeData) {
                $createdRoutes->push(Route::create($routeData));
            }

            // Update $indexedRoutes, add new IDs and update dataitems
            $indexedRoutes = $indexedRoutes->map(function ($route, $index) use ($createdRoutes) {
                $createdRoute = $createdRoutes[$index];
                $route['id'] = $createdRoute->id;
                $route['dataitems'] = collect($route['dataitems'])->map(function ($dataitem) use ($createdRoute) {
                    $dataitem['route_id'] = $createdRoute->id;
                    return $dataitem;
                })->toArray();
                return $route;
            });

            // Convert $indexedRoutes back to the original associative array format
            $newRoutes = collect(array_combine($originalKeys, $indexedRoutes->toArray()));
        }

        // Merge new and existing routes
        return $newRoutes->concat($existingRoutes);
    }

    /**
     * Prepare points data by updating route_id and removing unnecessary fields.
     *
     * @param array $points The original points data
     * @param mixed $routes The routes data (expected to be iterable)
     * @return array The processed points data
     */
    private function preparePoints(array $points, $routes): array
    {
        foreach ($routes as $route) {
            foreach ($route['dataitems'] as $dataitem) {
                $index = $dataitem['arrIdx'];
                if (isset($points[$index])) {
                    // Update route_id for the point
                    $points[$index]['route_id'] = $route['id'];

                    // Check and remove ghap_id if it doesn't start with 't'
                    if (isset($points[$index]['ghap_id']) && substr($points[$index]['ghap_id'], 0, 1) !== 't') {
                        unset($points[$index]['ghap_id']);
                    }
                }
            }
        }

        return $points;
    }

    /**
     * Prepare route orders based on processed routes with valid attributes.
     *
     * This function takes an array of routes, each containing a valid route ID,
     * insert stop index, and an array of dataitem IDs. It calculates new positions
     * for the dataitems within each route and prepares an array of route orders
     * suitable for database insertion.
     *
     * @param array $routes An array of route data. Each route should contain:
     *                      - 'id': The route ID
     *                      - 'insertStopIdx': The insertion stop index
     *                      - 'dataitems': A collection of dataitems, each with 'dataitem_id' and 'sortIndex'
     *
     * @return array An array of route orders, each containing:
     *               - 'route_id': The ID of the route
     *               - 'dataitem_id': The ID of the dataitem
     *               - 'position': The calculated new position for the dataitem within the route
     */
    public function prepareRouteOrders($routes)
    {
        $routeOrders = [];

        foreach ($routes as $route) {
            $routeId = $route['id'];
            $insertStopIdx = $route['insertStopIdx'];

            // Find the route instance (route has been created in $this->createRoutes)
            $routeInstance = Route::findOrCreateRoute($routeId);

            // Ensure we use the actual route ID (in case a new route was created)
            $routeId = $routeInstance->id;

            $modifiedDataitemIds = $route['dataitems']->pluck('dataitem_id')->toArray();

            // Calculate new positions for dataitems
            $newPositions = $routeInstance->calculateNewPositions(
                $insertStopIdx === 'append' ? null : $insertStopIdx,
                count($modifiedDataitemIds),
                $modifiedDataitemIds
            );

            // Sort dataitems by sortIndex
            $sortedDataitems = $route['dataitems']->sortBy('sortIndex');

            // Prepare data for insertion
            $routeOrders = array_merge($routeOrders, $sortedDataitems->map(
                function ($dataitem, $index) use ($routeId, $newPositions) {
                    return [
                        'route_id' => $routeId,
                        'dataitem_id' => $dataitem['dataitem_id'],
                        'position' => $newPositions[$index]
                    ];
                }
            )->all());
        }

        return $routeOrders;
    }
}
