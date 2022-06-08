<?php

namespace TLCMap\Http\Controllers;

use Illuminate\Http\Request;
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

use TLCMap\Http\Helpers\GeneralFunctions;

class AjaxController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth'); //ONLY needs this if you want it to return an error for non logged in users
    }

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

        if (!isset($minlong) || !isset($minlat) || !isset($maxlong) || !isset($maxlat) ) return response()->json( "Please ensure all 4 bounding box inputs are filled", 401 );
        if (!is_numeric($minlong)|| !is_numeric($minlat) || !is_numeric($maxlong) || !is_numeric($maxlat)) return response()->json( "Please ensure all 4 bounding box inputs are numbers", 401 );
        
        if ($maxlong - $minlong >= 360 || $maxlong - $minlong <= -360) { //if we have wrapped over the entire world
            $minlong = -180;
            $maxlong = 180;
        }
        else { //wrap back into range
            while($minlong < -180) {$minlong += 360;}
            while($minlong > 180) {$minlong -= 360;}
            while($maxlong < -180) {$maxlong += 360;}
            while($maxlong > 180) {$maxlong -= 360;}
        }
        
        return response()->json(array('minlong' => $minlong, 'minlat' => $minlat, 'maxlong' => $maxlong, 'maxlat' => $maxlat));
    }

    /**
     * Get values from form and save to the users searches
     */
    public function ajaxsavesearch(Request $request) {
        $this->middleware('auth'); //Throw error if not logged in?
        $user_id = auth()->user()->id;
        $name = $request->name;
        $searchquery = $request->searchquery;
        $count = $request->count;

        $msg = "";
        if (!isset($user_id)) { $msg .= "user_id not set. "; }
        if (!isset($searchquery)) { $msg .= "Query not set. "; }
        if (!isset($count)) { $msg .= "Count not set. "; }

        if ($msg === "") {
            SavedSearch::create([
                'user_id' => $user_id,
                'name' => $name,
                'query' => $searchquery,
                'count' => $count,
            ]); //create the savedsearch db entry
            return response()->json();
        }
        else { return response()->json( $msg, 401 ); }
    }

    /**
     * Delete this users search
     */
    public function ajaxdeletesearch(Request $request) {
        $this->middleware('auth'); //Throw error if not logged in?
        $user_id = $request->user()->id;
        $delete_id = $request->delete_id;

        $msg = "";
        if (!isset($user_id)) { $msg .= "user_id not set. "; }
        if (!isset($delete_id)) { $msg .= "Delete_id not set. "; }

        
        if ($msg === "") {
            SavedSearch::where([['user_id',$user_id], ['id',$delete_id]])->delete(); //we ONLY delete if the user actually owns the row they are attempting to delete
            return response()->json();
        }
    }

    /**
     * Delete this dataitem
     */
    public function ajaxdeletedataitem(Request $request) {
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
     * Edit this dataitem
     */
    public function ajaxeditdataitem(Request $request) {
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

	// records must have title, may have placename, if no title, assume placename is title
	if ($title === NULL) {
		$title = $placename;
	}
	
        $recordtype_id = RecordType::where('type', $request->recordtype)->first()->id;
        if (!$recordtype_id) return redirect('myprofile/mydatasets'); //invalid record type (likely caused by manually editing the html of the page)

        //timestamp for updated_at is automatic!

        $dataset = $user->datasets()->find($ds_id);
        if (!$dataset || ($dataset->pivot->dsrole != 'OWNER' && $dataset->pivot->dsrole != 'ADMIN')) return redirect('myprofile/mydatasets'); //if dataset not found for this user OR not ADMIN, go back

        $dataitem = $dataset->dataitems()->find($id);
        if (!$dataitem) return redirect('myprofile/mydatasets'); //if dataitem not found for this dataset, go back

        //insert values into the dataset
        if ( !(isset($placename) || isset($title)) || !isset($latitude) || !isset($longitude) ) return redirect('myprofile/mydatasets');

        $e1 = $datestart; //copy pre conversion values
        $e2 = $dateend;
        if (isset($datestart)) $datestart = GeneralFunctions::dateMatchesRegexAndConvertString($datestart); //datestart and dateend will be NULL if not set, FALSE if wrong format, or a string representing the converted date
        if (isset($dateend)) $dateend = GeneralFunctions::dateMatchesRegexAndConvertString($dateend);
        if ($datestart === false || $dateend === false) return response()->json( ['error'=>'Your date values are in the incorrect format.', 'e1' => $e1, 'e2' => $e2], 422); //if either didnt match, send error

        $dataitem->fill([
            'title' => $title,
            'recordtype_id' => $recordtype_id,
            'description' => $request->description,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'datestart' => $datestart,
            'dateend' => $dateend,
            'state' => $request->state,
            'feature_term' => $request->featureterm,
            'lga' => $request->lga,
            'source' => $request->source,
	    'external_url' => $request->url,
	    'placename' => $request->placename
        ]);
        $dataitem->save();

        $dataset->updated_at = Carbon::now();
        $dataset->save();

        return response()->json(['time' => $dataitem->updated_at->toDateTimeString(), 'datestart' => $datestart, 'dateend' => $dateend]);
    }

    
    /**
     * Add a dataitem to this dataset
     * return data doesnt work as intended, so we set the ajax to just reload (dataitem.js::add data item)
     */
    public function ajaxadddataitem(Request $request) {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //currently logged in user
        $ds_id = $request->ds_id;
        $dataset = $user->datasets()->find($ds_id);

        if (!$dataset || ($dataset->pivot->dsrole != 'OWNER' && $dataset->pivot->dsrole != 'ADMIN' && $dataset->pivot->dsrole != 'COLLABORATOR')) 
            return redirect('myprofile/mydatasets'); //if dataset not found for this user or not ADMIN/COLLABORATOR, go back
        
        $title = $request->title;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $recordtype_id = RecordType::where('type', $request->recordtype)->first()->id;
        $description = $request->description;
        $datestart = $request->datestart;
        $dateend = $request->dateend;
        $state = $request->state;
        $feature_term = $request->featureterm;
        $lga = $request->lga;
        $source = $request->source;
	$external_url = $request->url;
	$placename = $request->placename;


	if ($title === NULL) {
                $title = $placename;
        }
	
	if ( !isset($title) || !isset($latitude) || !isset($longitude) || !isset($recordtype_id)) 
	return response()->json( ['error'=>'Requires Title, Latitude, Longitude and Record Type. '], 422);
	if (!is_numeric($latitude) || !is_numeric($longitude)) return response()->json( ['error'=>'Latitude and Longitude must be number only.'], 422);
	
	$e1 = $datestart; //copy pre conversion values
        $e2 = $dateend;
        if (isset($datestart)) $datestart = GeneralFunctions::dateMatchesRegexAndConvertString($datestart); //datestart and dateend will be NULL if not set, FALSE if wrong format, or a string representing the converted date
        if (isset($dateend)) $dateend = GeneralFunctions::dateMatchesRegexAndConvertString($dateend);
        if ($datestart === false || $dateend === false) return response()->json( ['error'=>'Your date values are in the incorrect format.'], 422); //if either didnt match, send error

        $dataitem = Dataitem::create([
            'dataset_id' => $ds_id,
            'title' => $title,
            'recordtype_id' => $recordtype_id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'description' => $description,
            'datestart' => $datestart,
            'dateend' => $dateend,
            'state' => $state,
            'feature_term' => $feature_term,
            'lga' => $lga,
            'source' => $source,
	    'external_url' => $external_url,
	    'placename' => $placename
	]);
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
    public function ajaxdeletedataset(Request $request) {
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

    public function ajaxdestroysharelinks(Request $request) {
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

    public function ajaxgeneratesharelink(Request $request) {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //check authorization
        $id = $request->id; //id of dataset to modify
        $dsrole = $request->dsrole; //role to give to whomever uses this link

        $dataset = $user->datasets()->find($id); //find dataset for this user
        if (!$dataset || ($dataset->pivot->dsrole != 'ADMIN' && $dataset->pivot->dsrole != 'OWNER')) return redirect('myprofile/mydatasets'); //if DS id doesnt exist OR if user is not the owner, return to DS page

        if ($dsrole != 'VIEWER' && $dsrole != 'COLLABORATOR' && $dsrole != 'ADMIN') 
            return response()->json(['error' => 'Invalid dsrole'], 400); //if someone tries to submit a false dsrole

        $sharelink = Str::random(25);
        $collablink = CollabLink::create([ 'dataset_id' => $id, 'link' => $sharelink, 'dsrole' => $dsrole]); //create a new collab link  
        //Doesnt need attach() as it is one to many

        //Return to collab page
        return response()->json(['sharelink' => $sharelink]);
    }

    public function ajaxemailsharelink(Request $request) {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //Get user

        Mail::to($request->collaboratoremail)
            ->send(new CollaboratorEmail($request->sharelink, $request->senderemail, $request->dsrole));

        $msg = 'success?';

        return response()->json(['msg' => $msg]);
    }

    public function ajaxjoindataset(Request $request, string $link=null) {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //check authorization
        if (!$user) return redirect('login');
        $sharelink = ($link) ? $link : $request->sharelink; //the given share link (either by url or by textbox input)

        //Get collablink
        $collablink = CollabLink::whereRaw("BINARY link = ?",[$sharelink])->first(); //find CollabLink with this link
        if (!$collablink) return response()->json(['error' => 'Invalid share link!'], 400); //if doesnt exist return error

        //Pull info from collablink
        $dsrole = $collablink->dsrole;
        $dataset_id = $collablink->dataset_id;
        
        //get dataset
        $dataset = Dataset::where('id', $dataset_id)->first(); //find dataset
        if (!$dataset) return response()->json(['error' => 'Dataset no longer exists!'], 400); //if doesnt exists return error

        //if already attached, dont do anything
        if ( $user->datasets()->find($dataset_id) ) return response()->json(['error' => 'Already in this dataset!'], 400); //if doesnt exists return error

        //attach user to it
        $user->datasets()->attach($dataset, ['dsrole' =>  $dsrole]);

        //Delete that collab link
        $collablink->forceDelete();

        //Return redirect if entered via url param, response if entered via textbox
        return ($link) ?  redirect('myprofile/mydatasets')
                 : response()->json(['dataset' => $dataset, 'count' => $dataset->count(), 'owner' => $dataset->owner(), 'dsrole' => $dsrole, 'url' => url()->previous()]);
    }

    public function ajaxleavedataset(Request $request) {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //check authorization
        $id = $request->id; //id of dataset to modify

        $dataset = $user->datasets()->find($id);
        if(!$dataset || $user->id == $dataset->owner()) return redirect('myprofile/mydatasets'); //canot leave own dataset

        //detach this user from this dataset
        $user->datasets()->wherePivot('dataset_id', '=', $id)->detach();

        //Return response
        return response()->json();
    }

    public function ajaxeditcollaborator(Request $request) {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //check authorization

        $id = $request->id; //id of dataset to modify
        $collaborator_email = $request->collaborator_email; //user to edit
        $dsrole = $request->dsrole; //new dsrole to give to user for this dataset

        $dataset = $user->datasets()->find($id);
        if(!$dataset || ($dataset->pivot->dsrole != 'ADMIN' && $dataset->pivot->dsrole != 'OWNER')) return response()->json(['error' => 'You are not an admin on this dataset!'], 400); //if dataset doesnt exist or you are not OWNER

        if ($dsrole != 'VIEWER' && $dsrole != 'COLLABORATOR' && $dsrole != 'ADMIN') 
            return response()->json(['error' => 'Invalid dsrole'], 400); //if someone tries to submit a false dsrole

        $user_to_edit = User::where('email',$collaborator_email)->first();
        if (!$user_to_edit) return response()->json(['error' => 'User does not exist!'], 400);

        if ($user_to_edit->id == $user->id)  return response()->json(['error' => 'Cannot edit self!'], 400);
        if ($user_to_edit->id == $dataset->owner())  return response()->json(['error' => 'Cannot edit the owner of the dataset!'], 400);
        
        //edit the pivot data between the user_to_edit and the dataset
        $user_to_edit->datasets()->updateExistingPivot($id, ['dsrole' => $dsrole]);

        //Return response
        return response()->json(['newdsrole' => $dsrole]);
    }

    public function ajaxdeletecollaborator(Request $request) {
        $this->middleware('auth'); //Throw error if not logged in?
        $user = auth()->user(); //check authorization

        $id = $request->id; //id of dataset to modify
        $collaborator_email = $request->collaborator_email; //user to edit

        $dataset = $user->datasets()->find($id);
        if(!$dataset || ($dataset->pivot->dsrole != 'ADMIN' && $dataset->pivot->dsrole != 'OWNER')) return response()->json(['error' => 'You are not an admin on this dataset!'], 400); //if dataset doesnt exist or you are not OWNER

        $user_to_delete = User::where('email',$collaborator_email)->first();
        if (!$user_to_delete) return response()->json(['error' => 'User does not exist!'], 400);

        if ($user_to_delete->id == $user->id)  return response()->json(['error' => 'Cannot DELETE self!'], 400);
        if ($user_to_delete->id == $dataset->owner())  return response()->json(['error' => 'Cannot DELETE the owner of the dataset!'], 400);

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

}
