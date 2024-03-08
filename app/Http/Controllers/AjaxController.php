<?php

namespace TLCMap\Http\Controllers;

use Illuminate\Http\Request;
use TLCMap\Http\Helpers\UID;
use TLCMap\Models\SavedSearch;
use TLCMap\Models\Dataitem;
use TLCMap\Models\Dataset;
use TLCMap\Models\CollabLink;
use TLCMap\Models\RecordType;
use TLCMap\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use TLCMap\Mail\CollaboratorEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use TLCMap\Models\SubjectKeyword;

use TLCMap\Http\Helpers\GeneralFunctions;

class AjaxController extends Controller
{
    /**
     * Get values from form
     * Check they are all present, all numbers, and that mins arent > maxs
     * Return the values in response, or error message
     */
    public function ajaxbbox(Request $request)
    {
        $minlong = $request->minlong;
        $minlat = $request->minlat;
        $maxlong = $request->maxlong;
        $maxlat = $request->maxlat;

        if (!isset($minlong) || !isset($minlat) || !isset($maxlong) || !isset($maxlat)) return response()->json("Please ensure all 4 bounding box inputs are filled", 401);
        if (!is_numeric($minlong) || !is_numeric($minlat) || !is_numeric($maxlong) || !is_numeric($maxlat)) return response()->json("Please ensure all 4 bounding box inputs are numbers", 401);

        if ($maxlong - $minlong >= 360 || $maxlong - $minlong <= -360) { //if we have wrapped over the entire world
            $minlong = -180;
            $maxlong = 180;
        } else { //wrap back into range
            while ($minlong < -180) {
                $minlong += 360;
            }
            while ($minlong > 180) {
                $minlong -= 360;
            }
            while ($maxlong < -180) {
                $maxlong += 360;
            }
            while ($maxlong > 180) {
                $maxlong -= 360;
            }
        }

        return response()->json(array('minlong' => $minlong, 'minlat' => $minlat, 'maxlong' => $maxlong, 'maxlat' => $maxlat));
    }

    /**
     * Get values from form and save to the users searches
     */
    public function ajaxsavesearch(Request $request)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user_id = auth()->user()->id;

        $name = $request->name;
        $searchquery = $request->searchquery;
        $count = $request->count;
        $description = $request->description;
        $recordtype = $request->recordtype;
        $warning = $request->warning;
        $latitudefrom = $request->latitudefrom;
        $longitudefrom = $request->longitudefrom;
        $latitudeto = $request->latitudeto;
        $longitudeto = $request->longitudeto;
        $temporalfrom = $request->temporalfrom;
        $temporalto = $request->temporalto;

        $msg = "";
        if (!isset($user_id)) {
            $msg .= "user_id not set. ";
        }
        if (!isset($searchquery)) {
            $msg .= "Query not set. ";
        }
        if (!isset($count)) {
            $msg .= "Count not set. ";
        }
        if (!isset($name)) {
            $msg .= "Search Name not set. ";
        }
        if (!isset($description)) {
            $msg .= "Search description not set. ";
        }
        if (isset($temporalfrom)) {
            $temporalfrom = GeneralFunctions::dateMatchesRegexAndConvertString($temporalfrom);
            if (!$temporalfrom) {
                $msg .= "Temporal from date is in incorrect format. ";
            }
        }
        if (isset($temporalto)) {
            $temporalto = GeneralFunctions::dateMatchesRegexAndConvertString($temporalto);
            if (!$temporalto) {
                $msg .= "Temporal to date is in incorrect format. ";
            }
        }

        if ($msg === "") {
            $SavedSearch = SavedSearch::create([
                'user_id' => $user_id,
                'name' => $name,
                'query' => $searchquery,
                'count' => $count,
                'description' => $description,
                'recordtype_id' => RecordType::where('type', $recordtype)->first()->id,
                'warning' => $warning,
                'latitude_from' => $latitudefrom,
                'longitude_from' => $longitudefrom,
                'latitude_to' => $latitudeto,
                'longitude_to' => $longitudeto,
                'temporal_from' => $temporalfrom,
                'temporal_to' => $temporalto
            ]); //create the savedsearch db entry

            //Add subject keywords to relationship table
            $keywords = [];
            $tags = explode(",,;", $request->tags);
            foreach ($tags as $tag) {
                $subjectkeyword = SubjectKeyword::firstOrCreate(['keyword' => $tag]);
                array_push($keywords, $subjectkeyword);
            }
            foreach ($keywords as $keyword) {
                $SavedSearch->subjectKeywords()->attach(['subject_keyword_id' => $keyword->id]);
            }

            return response()->json();
        } else {
            return response()->json($msg, 401);
        }
    }

    /**
     * Delete this users search
     */
    public function ajaxdeletesearch(Request $request)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user_id = $request->user()->id;
        $delete_id = $request->delete_id;

        $msg = "";
        if (!isset($user_id)) {
            $msg .= "user_id not set. ";
        }
        if (!isset($delete_id)) {
            $msg .= "Delete_id not set. ";
        }


        if ($msg === "") {
            $savedSearch = SavedSearch::where([['user_id', $user_id], ['id', $delete_id]])->first();
            if ($savedSearch) {
                $savedSearch->subjectKeywords()->detach();
                $savedSearch->collections()->detach();
                $savedSearch->delete();
            }
            return response()->json();
        }
    }

    /**
     * Get values from form and save to the metadata section of users saved search
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxeditsearch(Request $request)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user_id = auth()->user()->id;
        $searchID = $request->id;

        $savedSearch = SavedSearch::where([['user_id', $user_id], ['id', $searchID]])->first();
        if (!$savedSearch) {
            return redirect('myprofile/mysearches');
        }

        $name = $request->name;
        $description = $request->description;
        $recordtype = $request->recordtype;
        $warning = $request->warning;
        $latitudefrom = $request->latitudefrom;
        $longitudefrom = $request->longitudefrom;
        $latitudeto = $request->latitudeto;
        $longitudeto = $request->longitudeto;
        $temporalfrom = $request->temporalfrom;
        $temporalto = $request->temporalto;

        $keywords = [];
        $tags = explode(",,;", $request->tags);
        foreach ($tags as $tag) {
            $subjectkeyword = SubjectKeyword::firstOrCreate(['keyword' => $tag]);
            array_push($keywords, $subjectkeyword);
        }

        $msg = "";
        if (!isset($name)) {
            $msg .= "Search Name not set. ";
        }
        if (!isset($description)) {
            $msg .= "Search description not set. ";
        }
        if (isset($temporalfrom)) {
            $temporalfrom = GeneralFunctions::dateMatchesRegexAndConvertString($temporalfrom);
            if (!$temporalfrom) {
                $msg .= "Temporal from date is in incorrect format. ";
            }
        }
        if (isset($temporalto)) {
            $temporalto = GeneralFunctions::dateMatchesRegexAndConvertString($temporalto);
            if (!$temporalto) {
                $msg .= "Temporal to date is in incorrect format. ";
            }
        }

        if ($msg === "") {
            $savedSearch->fill([
                'name' => $name,
                'description' => $description,
                'recordtype_id' => RecordType::where('type', $recordtype)->first()->id,
                'warning' => $warning,
                'latitude_from' => $latitudefrom,
                'longitude_from' => $longitudefrom,
                'latitude_to' => $latitudeto,
                'longitude_to' => $longitudeto,
                'temporal_from' => $temporalfrom,
                'temporal_to' => $temporalto
            ]);

            $savedSearch->save();

            $savedSearch->subjectKeywords()->detach(); //re attach subject keywords
            foreach ($keywords as $keyword) {
                $savedSearch->subjectKeywords()->attach(['subject_keyword_id' => $keyword->id]);
            }

            return response()->json();
        } else {
            return response()->json($msg, 401);
        }
    }

    /**
     * View a dataitem.
     *
     * This controller only apply when a logged in user requesting a dataitem from one of his/her owned dataset.
     *
     * Accept URL parameters:
     * - id: The ID of the dataitem.
     * - dataset_id: The ID of the dataset which the dataitem belongs to.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxviewdataitem(Request $request)
    {
        $dataitemID = $request->id;
        $datasetID = $request->dataset_id;
        $dataitem = null;
        $user = auth()->user();
        if (!empty($user) && !empty($datasetID)) {
            $dataset = $user->datasets()->find($datasetID);
            if (!empty($dataset) && !empty($dataitemID)) {
                $dataitem = $dataset->dataitems()->with('recordtype')->where('id', $dataitemID)->first();
            }
        }
        if (empty($dataitem)) {
            abort(404);
        }
        $extendedData = $dataitem->getExtendedData();
        $dataitem->extendedData = $extendedData ? $extendedData : null;
        return response()->json($dataitem);
    }

    /**
     * Delete this dataitem
     */
    public function ajaxdeletedataitem(Request $request)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user();
        $id = $request->id; //id of dataitem to be deleted
        $ds_id = $request->ds_id;

        $dataset = $user->datasets()->find($ds_id);
        if (!$dataset || ($dataset->pivot->dsrole != 'OWNER' && $dataset->pivot->dsrole != 'ADMIN')) return redirect('myprofile/mydatasets'); //if dataset not found for this user OR not ADMIN, go back

        $dataitem = $dataset->dataitems()->find($id);
        if (!$dataitem) return redirect('myprofile/mydatasets'); //if dataitem not found for this dataset, go back

        $dataitem->delete();

        $dataset->updated_at = Carbon::now();
        $dataset->save();

        return response()->json(['time' => $dataset->updated_at->toDateTimeString(), 'count' => count($dataset->dataitems)]);;
    }

    /**
     * Change the order of dataitems in one dataset
     */
    public function ajaxchangedataitemorder(Request $request)
    {
        $this->middleware('auth'); // Ensure the user is logged in
        $user = auth()->user(); // Get the currently logged in user

        $ds_id = $request->ds_id; //id of dataset
        $dataset = $user->datasets()->find($ds_id);
        if (!$dataset || ($dataset->pivot->dsrole != 'OWNER' && $dataset->pivot->dsrole != 'ADMIN'))
            return redirect('myprofile/mydatasets'); //if dataset not found for this user OR not ADMIN, go back

        $newOrder = $request->input('newOrder'); // The new order of the dataitems
        if (is_null($newOrder)) {
            return response()->json(['error' => 'Invalid order data'], 400);
        }

        foreach ($newOrder as $order => $dataitemID) {
            $dataitem = $dataset->dataitems()->find($dataitemID);
            if ($dataitem) {
                $dataitem->dataset_order = $order;
                $dataitem->save();
            }
        }

        return response()->json(['message' => 'Order updated successfully']);
    }

    /**
     * Edit this dataitem
     * TODO: Shall I validate route meta information?
     */
    public function ajaxeditdataitem(Request $request)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //currently logged in user
        $id = $request->id; //id of dataitem to be edited
        $ds_id = $request->ds_id; //id of dataset

        $placename = $request->placename;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $datestart = $request->datestart;
        $dateend = $request->dateend;
        $title = $request->title;
        $quantity = $request->quantity;
        $route_id = $request->routeId;
        $route_original_id = $request->routeOriId;
        $route_title = $request->routeTitle;
        $extendedData = $request->extendedData;

        // records must have title, may have placename, if no title, assume placename is title
        if ($title === NULL) {
            $title = $placename;
        }

        $recordtype_id = RecordType::where('type', $request->recordtype)->first()->id;
        if (!$recordtype_id) return redirect('myprofile/mydatasets'); //invalid record type (likely caused by manually editing the html of the page)

        $dataset = $user->datasets()->find($ds_id);
        if (!$dataset || ($dataset->pivot->dsrole != 'OWNER' && $dataset->pivot->dsrole != 'ADMIN')) return redirect('myprofile/mydatasets'); //if dataset not found for this user OR not ADMIN, go back

        $dataitem = $dataset->dataitems()->find($id);
        if (!$dataitem) return redirect('myprofile/mydatasets'); //if dataitem not found for this dataset, go back

        //insert values into the dataset
        if (!(isset($placename) || isset($title)) || !isset($latitude) || !isset($longitude)) return redirect('myprofile/mydatasets');

        $e1 = $datestart; //copy pre conversion values
        $e2 = $dateend;
        if (isset($datestart)) $datestart = GeneralFunctions::dateMatchesRegexAndConvertString($datestart); //datestart and dateend will be NULL if not set, FALSE if wrong format, or a string representing the converted date
        if (isset($dateend)) $dateend = GeneralFunctions::dateMatchesRegexAndConvertString($dateend);
        if ($datestart === false || $dateend === false) return response()->json(['error' => 'Your date values are in the incorrect format.', 'e1' => $e1, 'e2' => $e2], 422); //if either didnt match, send error

        if ($request->hasFile('image')) {
            $image = $request->file('image');

            // Validate image file.
            if (!GeneralFunctions::validateUserUploadImage($image)) {
                return response()->json(['error' => 'Image must be a valid image file type and size.'], 422);
            }

            // Delete old image.
            if ($dataitem->image_path && Storage::disk('public')->exists('images/' . $dataitem->image_path)) {
                Storage::disk('public')->delete('images/' . $dataitem->image_path);
            }

            // Save new image.
            $filename = time() . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images', $image, $filename);
            $dataitem->image_path = $filename;
        }

        $dataitem->fill([
            'title' => $title,
            'recordtype_id' => $recordtype_id,
            'description' => $request->description,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'datestart' => $datestart,
            'dateend' => $dateend,
            'quantity' => $quantity,
            "route_id" => $route_id,
            "route_original_id" => $route_original_id,
            "route_title" => $route_title,
            'state' => $request->state,
            'feature_term' => $request->featureterm,
            'lga' => $request->lga,
            'source' => $request->source,
            'external_url' => $request->url,
            'placename' => $request->placename,
            'image_path' => $dataitem->image_path
        ]);
        $dataitem->setExtendedData(json_decode($extendedData, true));
        $dataitem->save();

        $dataset->updated_at = Carbon::now();
        $dataset->save();

        return response()->json(['time' => $dataitem->updated_at->toDateTimeString(), 'datestart' => $datestart, 'dateend' => $dateend]);
    }


    /**
     * Add a dataitem to this dataset
     * return data doesnt work as intended, so we set the ajax to just reload (dataitem.js::add data item)
     */
    public function ajaxadddataitem(Request $request)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //currently logged in user
        $ds_id = $request->ds_id;
        $dataset = $user->datasets()->find($ds_id);

        if (!$dataset || ($dataset->pivot->dsrole != 'OWNER' && $dataset->pivot->dsrole != 'ADMIN' && $dataset->pivot->dsrole != 'COLLABORATOR'))
            return redirect('myprofile/mydatasets'); //if dataset not found for this user or not ADMIN/COLLABORATOR, go back

        $route_id = $request->route_id;
        $title = $request->title;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $recordtype_id = RecordType::where('type', $request->recordtype)->first()->id;
        $description = $request->description;
        $quantity = $request->quantity;
        $route_id = $request->routeId;
        $route_original_id = $request->routeOriId;
        $route_title = $request->routeTitle;
        $datestart = $request->datestart;
        $dateend = $request->dateend;
        $state = $request->state;
        $feature_term = $request->featureterm;
        $lga = $request->lga;
        $source = $request->source;
        $external_url = $request->url;
        $placename = $request->placename;
        $extendedData = $request->extendedData;

        if ($title === NULL) {
            $title = $placename;
        }

        if (!isset($title) || !isset($latitude) || !isset($longitude) || !isset($recordtype_id))
            return response()->json(['error' => 'Requires Title, Latitude, Longitude and Record Type. '], 422);
        if (!is_numeric($latitude) || !is_numeric($longitude)) return response()->json(['error' => 'Latitude and Longitude must be number only.'], 422);

        $e1 = $datestart; //copy pre conversion values
        $e2 = $dateend;
        if (isset($datestart)) $datestart = GeneralFunctions::dateMatchesRegexAndConvertString($datestart); //datestart and dateend will be NULL if not set, FALSE if wrong format, or a string representing the converted date
        if (isset($dateend)) $dateend = GeneralFunctions::dateMatchesRegexAndConvertString($dateend);
        if ($datestart === false || $dateend === false) return response()->json(['error' => 'Your date values are in the incorrect format.'], 422); //if either didnt match, send error

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
        $maxOrder = $dataset->dataitems()->max('dataset_order');
        $dataset_order = $maxOrder !== null ? $maxOrder + 1 : 0;

        //Validate the format of added quantity
        $eQTY = $quantity;
        if (isset($quantity)) {
            $quantity = GeneralFunctions::naturalNumberMatchesRegex($quantity);
        }
        if ($quantity === false) return response()->json(['error' => 'Your quantity values are in the incorrect format.', 'eQTY' => $eQTY], 422);

        //Validate route information
        /**
         * 1. The priority of the route information columns is as follows: route_id > route_original_id > route_title.
         * 2. If only one attribute exists, the system will find a matching route or create a new one.
         *    If multiple attributes exist, the system will find the matching route following the priority order.
         * 3. If the attribute of matched route is none, update the attribute with new added attribute.
         *    However, if the attribute of matched route is not none, the value of newly added attribute would be ignored.
         * 4. If no matching route found, a new route_id would be assigned to the newly added place(point).
         * 5. The format of route_id is constrained to integer.
         **/

        $route_exists = false;
        // Define the array of route attributes in priority order
        $route_attrs = ['route_id', 'route_original_id', 'route_title'];

        // Iterate over the route attributes
        foreach ($route_attrs as $route_attr) {
            // Set other route attributes
            $other_route_attrs = array_diff($route_attrs, [$route_attr]);

            // Check if the attribute is not null and not an empty string
            if (!is_null($$route_attr) && $$route_attr !== '') {

                // Check if a route exists for the current attribute
                $route_exists = $dataset->dataitems()->get()->contains($route_attr, $$route_attr);

                // If the matched route isn't found, break the loop
                if ($route_exists === false) {
                    continue;
                }

                // Retrieve the matched route
                $matched_route = $dataset->dataitems()
                    ->where($route_attr, $$route_attr)
                    ->where(function ($query) use ($other_route_attrs) {
                        // Check if any other attribute in priority order is not null
                        foreach ($other_route_attrs as $other_route_attr) {
                            $query->orWhereNotNull($other_route_attr);
                        }
                    })
                    ->first();

                // If a matched route is found, update other attributes if original route atrributes are null
                if ($matched_route) {
                    foreach ($other_route_attrs as $other_route_attr) {
                        $$other_route_attr = $matched_route->$other_route_attr ?? $$other_route_attr;
                    }
                }

                // If the matched route is found, and route information of matched route is updated, break the loop
                if ($route_exists !== false) {
                    break;
                }
            }
        }

        // If a route does not exist, assign a new route_id
        if ($route_exists === false) {
            $max_route_id = $dataset->dataitems()->max('route_id');
            $route_id = $max_route_id !== null ? $max_route_id + 1 : 1;
        }

        //match added route information
        $dataitem = Dataitem::create([
            'dataset_id' => $ds_id,
            'title' => $title,
            'recordtype_id' => $recordtype_id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'description' => $description,
            'quantity' => $quantity,
            'route_id' => $route_id,
            'route_original_id' => $route_original_id,
            'route_title' => $route_title,
            'datestart' => $datestart,
            'dateend' => $dateend,
            'state' => $state,
            'feature_term' => $feature_term,
            'lga' => $lga,
            'source' => $source,
            'external_url' => $external_url,
            'placename' => $placename,
            'image_path' => $filename,
            'dataset_order' => $dataset_order
        ]);
        $isDirty = false;
        Log::debug('Quantity value: ' . $dataitem);
        // Generate UID.
        if ($dataitem->id) {
            $dataitem->uid = UID::create($dataitem->id, 't');
            $isDirty = true;
        }
        // Set extended data.
        if (!empty($extendedData)) {
            $dataitem->setExtendedData(json_decode($extendedData, true));
            $isDirty = true;
        }
        if ($isDirty) {
            $dataitem->save();
        }

        $dataset->updated_at = Carbon::now();
        $dataset->save();
        return response()->json(['dataitem' => $dataitem, 'time' => $dataset->updated_at->toDateTimeString(), 'count' => count($dataset->dataitems)]);
    }

    /*
     *  BULK file add will be done in the usercontroller, as using AJAX for such a hefty task is not ideal
     */

    /*
     *  Delete this entire dataset
     */
    public function ajaxdeletedataset(Request $request)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //currently logged in user
        $id = $request->id; //id of dataset to delete

        //get datset
        $dataset = $user->datasets()->find($id);
        if (!$dataset || $user->id != $dataset->owner()) return redirect('myprofile/mydatasets'); //only delete if owner

        $dataset->users()->detach();

        $dataset->delete();

        return response()->json();
    }

    public function ajaxdestroysharelinks(Request $request)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //check authorization
        $id = $request->id; //id of dataset to modify

        $dataset = $user->datasets()->find($id); //find dataset for this user
        if (!$dataset || ($dataset->pivot->dsrole != 'ADMIN' && $dataset->pivot->dsrole != 'OWNER')) return redirect('myprofile/mydatasets'); //if DS id doesnt exist OR if user is not the owner, return to DS page

        //Get sharelinks for this DS, delete them
        $dataset->collablinks()->delete();

        //Return to collab page
        return response()->json(); //redirect('myprofile/mydatasets/'.$id.'/collaborators');
    }

    public function ajaxgeneratesharelink(Request $request)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //check authorization
        $id = $request->id; //id of dataset to modify
        $dsrole = $request->dsrole; //role to give to whomever uses this link

        $dataset = $user->datasets()->find($id); //find dataset for this user
        if (!$dataset || ($dataset->pivot->dsrole != 'ADMIN' && $dataset->pivot->dsrole != 'OWNER')) return redirect('myprofile/mydatasets'); //if DS id doesnt exist OR if user is not the owner, return to DS page

        if ($dsrole != 'VIEWER' && $dsrole != 'COLLABORATOR' && $dsrole != 'ADMIN')
            return response()->json(['error' => 'Invalid dsrole'], 400); //if someone tries to submit a false dsrole

        $sharelink = Str::random(25);
        $collablink = CollabLink::create(['dataset_id' => $id, 'link' => $sharelink, 'dsrole' => $dsrole]); //create a new collab link
        //Doesnt need attach() as it is one to many

        //Return to collab page
        return response()->json(['sharelink' => $sharelink]);
    }

    public function ajaxemailsharelink(Request $request)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //Get user

        Mail::to($request->collaboratoremail)
            ->send(new CollaboratorEmail($request->sharelink, $request->senderemail, $request->dsrole));

        $msg = 'success?';

        return response()->json(['msg' => $msg]);
    }

    public function ajaxjoindataset(Request $request, string $link = null)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //check authorization
        if (!$user) return redirect('login');
        $sharelink = ($link) ? $link : $request->sharelink; //the given share link (either by url or by textbox input)

        //Get collablink
        $collablink = CollabLink::whereRaw("BINARY link = ?", [$sharelink])->first(); //find CollabLink with this link
        if (!$collablink) return response()->json(['error' => 'Invalid share link!'], 400); //if doesnt exist return error

        //Pull info from collablink
        $dsrole = $collablink->dsrole;
        $dataset_id = $collablink->dataset_id;

        //get dataset
        $dataset = Dataset::where('id', $dataset_id)->first(); //find dataset
        if (!$dataset) return response()->json(['error' => 'Dataset no longer exists!'], 400); //if doesnt exists return error

        //if already attached, dont do anything
        if ($user->datasets()->find($dataset_id)) return response()->json(['error' => 'Already in this dataset!'], 400); //if doesnt exists return error

        //attach user to it
        $user->datasets()->attach($dataset, ['dsrole' => $dsrole]);

        //Delete that collab link
        $collablink->forceDelete();

        //Return redirect if entered via url param, response if entered via textbox
        return ($link) ? redirect('myprofile/mydatasets')
            : response()->json(['dataset' => $dataset, 'count' => $dataset->count(), 'owner' => $dataset->owner(), 'dsrole' => $dsrole, 'url' => url()->previous()]);
    }

    public function ajaxleavedataset(Request $request)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //check authorization
        $id = $request->id; //id of dataset to modify

        $dataset = $user->datasets()->find($id);
        if (!$dataset || $user->id == $dataset->owner()) return redirect('myprofile/mydatasets'); //canot leave own dataset

        //detach this user from this dataset
        $user->datasets()->wherePivot('dataset_id', '=', $id)->detach();

        //Return response
        return response()->json();
    }

    public function ajaxeditcollaborator(Request $request)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //check authorization

        $id = $request->id; //id of dataset to modify
        $collaborator_email = $request->collaborator_email; //user to edit
        $dsrole = $request->dsrole; //new dsrole to give to user for this dataset

        $dataset = $user->datasets()->find($id);
        if (!$dataset || ($dataset->pivot->dsrole != 'ADMIN' && $dataset->pivot->dsrole != 'OWNER')) return response()->json(['error' => 'You are not an admin on this dataset!'], 400); //if dataset doesnt exist or you are not OWNER

        if ($dsrole != 'VIEWER' && $dsrole != 'COLLABORATOR' && $dsrole != 'ADMIN')
            return response()->json(['error' => 'Invalid dsrole'], 400); //if someone tries to submit a false dsrole

        $user_to_edit = User::where('email', $collaborator_email)->first();
        if (!$user_to_edit) return response()->json(['error' => 'User does not exist!'], 400);

        if ($user_to_edit->id == $user->id) return response()->json(['error' => 'Cannot edit self!'], 400);
        if ($user_to_edit->id == $dataset->owner()) return response()->json(['error' => 'Cannot edit the owner of the dataset!'], 400);

        //edit the pivot data between the user_to_edit and the dataset
        $user_to_edit->datasets()->updateExistingPivot($id, ['dsrole' => $dsrole]);

        //Return response
        return response()->json(['newdsrole' => $dsrole]);
    }

    public function ajaxdeletecollaborator(Request $request)
    {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //check authorization

        $id = $request->id; //id of dataset to modify
        $collaborator_email = $request->collaborator_email; //user to edit

        $dataset = $user->datasets()->find($id);
        if (!$dataset || ($dataset->pivot->dsrole != 'ADMIN' && $dataset->pivot->dsrole != 'OWNER')) return response()->json(['error' => 'You are not an admin on this dataset!'], 400); //if dataset doesnt exist or you are not OWNER

        $user_to_delete = User::where('email', $collaborator_email)->first();
        if (!$user_to_delete) return response()->json(['error' => 'User does not exist!'], 400);

        if ($user_to_delete->id == $user->id) return response()->json(['error' => 'Cannot DELETE self!'], 400);
        if ($user_to_delete->id == $dataset->owner()) return response()->json(['error' => 'Cannot DELETE the owner of the dataset!'], 400);

        //detach the user from this dataset
        $user_to_delete->datasets()->wherePivot('dataset_id', '=', $id)->detach();

        //Return response
        return response()->json();
    }

    /**
     * NO LONGER USED, WAS WAY TOO NICHE
     */
    // public function throwErrorIfInvalidDate($datestart, $dateend) {
    //     $e1 = $e2 = true; //default to true
    //     if (isset($datestart)) $e1 = GeneralFunctions::dateMatchesRegexAndConvertString($datestart); //true if matches one of the accepted regexes, else false
    //     if (isset($dateend)) $e2 = GeneralFunctions::dateMatchesRegexAndConvertString($dateend);
    //     if (!$e1 || !$e2) return response()->json( ['error'=>'Your date values are in the incorrect format.', 'e1' => $e1, 'e2' => $e2], 422); //if either didnt match, send error
    //     return null;
    // }

    /**
     * Processes the DBSCAN clustering algorithm on a dataset.
     *
     * Validates input parameterss for distance and minPoints, then retrieve dataset by ID.
     * If the dataset is not found or parameters are invalid, return error response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function ajaxdbscan(Request $request)
    {
        $id = $request->id;

        //get datset
        $ds = Dataset::with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers');

        if ($request->distance == null || $request->distance < 0 || !is_numeric($request->distance)) {
            return response()->json(['error' => 'Invalid distance'], 400);
        }

        $clusterAnalysisResults = $ds->getClusterAnalysisDBScan($request->distance, $request->minPoints);

        return response()->json($clusterAnalysisResults);
    }

    /**
     * Processes the K-means clustering algorithm on a dataset.
     *
     * Retrieves the dataset by ID a
     * Redirects to 'layers' route if dataset not found.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function ajaxkmeans(Request $request)
    {
        $id = $request->id;

        $ds = Dataset::with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers');

        $clusterAnalysisResults = $ds->getClusterAnalysisKmeans($request->numClusters, $request->withinRadius);

        return response()->json($clusterAnalysisResults);
    }

    /**
     * Processes temporal clustering on a dataset.
     *
     * Retrieves the dataset by ID
     * Redirects to 'layers' route if dataset not found.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function ajaxtemporalclustering(Request $request)
    {
        $id = $request->id; //id of dataset

        //get datset
        $ds = Dataset::with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->where(['public' => 1, 'id' => $id])->first();
        if (!$ds) return redirect()->route('layers');

        $res = $ds->getTemporalClustering($request->totalInterval);

        return response()->json($res);
    }

    /**
     * Performs a closeness analysis between two datasets.
     *
     * Retrieves the source dataset by ID and performs a closeness analysis with a target dataset specified by the request.
     * Redirects to 'layers' route if dataset not found.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function ajaxclosenessanalysis(Request $request)
    {
        $id = $request->dataset_id; //id of dataset

        //get datset
        $ds = Dataset::with(['dataitems' => function ($query) {
            $query->orderBy('dataset_order');
        }])->where(['public' => 1, 'id' => $id])->first();

        if (!$ds) return redirect()->route('layers');

        $res = $ds->getClosenessAnalysis($request->targetDatasetId);

        return response()->json($res);
    }
}
