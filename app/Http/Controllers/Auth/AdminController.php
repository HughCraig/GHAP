<?php

namespace TLCMap\Http\Controllers\Auth;

use TLCMap\Models\User;
use TLCMap\Models\Role;
use TLCMap\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use TLCMap\Mail\PasswordChanged;

class AdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Admin Controller
    |--------------------------------------------------------------------------
    |
    | This controller will check if users have the correct Role before letting them access certain pages
    |
    */

    public function adminHome(Request $request)
    {
        $request->user()->authorizeRoles(['ADMIN', 'SUPER_ADMIN']); //Current logged in user is admin/super admin
        return view('admin.admin');
    }

    public function userManagement(Request $request)
    {
        $request->user()->authorizeRoles(['SUPER_ADMIN']);
        $users = User::all();
        return view('admin.usermanagement', ['users' => $users]);
    }

    public function viewUser(Request $request, string $id = null)
    {
        $request->user()->authorizeRoles(['SUPER_ADMIN']);
        $user = User::find($id);
        return view('admin.viewuser', ['user' => $user]);
    }

    public function activateDeactivateUser(Request $request, string $id = null)
    {
        $request->user()->authorizeRoles(['SUPER_ADMIN']);
        $user = User::find($id); //get the user object from the email PK

        //TODO If user is a super admin, cancel the role change

        $user->is_active = ($user->is_active) ? false : true; //swap the boolean for is_active
        $user->save(); //save Model to DB
        return redirect('admin/users/' . $user->id . ''); //redirect to the page we were just on
    }

    public function setEmailAsVerified(Request $request, string $id = null)
    {
        $request->user()->authorizeRoles(['SUPER_ADMIN']);
        $user = User::find($id); 
        if (!$user) {
            return response()->json('User not found.', 404); 
        }

        if ($user->email_verified_at === null) {
            $user->email_verified_at = now();
            $user->save(); //save Model to DB
        }

        return redirect('admin/users/' . $user->id . ''); 
    }

    public function updateUserRole(Request $request, string $id = null)
    {
        $request->user()->authorizeRoles(['SUPER_ADMIN']);
        $newRoleName = $request->input('selectRole'); //get from new role POST
        $newRoleId = Role::where('name', $newRoleName)->first()->id; //get the role id from the name
        $user = User::find($id); //get the user object from the email PK

        //TODO If user is a super admin, cancel the role change

        //https://stackoverflow.com/questions/36694081/updateexistingpivot-for-multiple-ids
        $ids = $user->roles()->allRelatedIds(); //Not sure why but it works
        foreach ($ids as $id) {
            $user->roles()->updateExistingPivot($id, ['role_id' => $newRoleId]);
        }
        //Dont save user table, as we actually edited the user_roles pivot table
        return redirect('admin/users/' . $user->id . ''); //redirect to the page we were just on
    }

    public function resetUserPassword(Request $request)
    {

        $request->user()->authorizeRoles(['SUPER_ADMIN']);

        $id = $request->input('id');
        $user = User::find($id);

        if (!$user) {
            return response()->json('User not found.', 404); 
        }

        $notin = array_merge(explode(' ', strtolower($user->name)), explode('@', strtolower($user->email))); //cannot match username, or any part of the email address
        $rules = [ //rules for the validator
            'password' => [
                'required', 'string', 'min:8', 'max:16', 'confirmed', //10+ chars, must match the password-confirm box
                'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^A-Za-z0-9]/', //must contain 1 of each: lowercase uppercase number and special character
                'not_regex:/(.)\1{4,}/', //must not contain any repeating char 4 or more times
                function ($attribute, $value, $fail) use ($notin) {
                    $v = strtolower($value);
                    foreach ($notin as $n) {
                        if (strpos($v, $n) !== false) $fail('Password cannot contain any part of user name or email!');
                    }
                }
            ],
        ];

        $validator = Validator::make($request->all(), $rules); 

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); 
        }
        
        $user->update(['password' => Hash::make($request->input('password'))]);

        Mail::to($user->email)->send(new PasswordChanged($user->name));

        return response()->json('Password updated',200);
    }

    public function deleteUser(Request $request)
    {
        $request->user()->authorizeRoles(['SUPER_ADMIN']);
        $userId = $request->input('id');

        $user = User::find($userId);

        if (!$user) {
            return redirect('admin/users'); 
        }

        // Delete datasets and dataitems
        foreach ($user->datasets as $ds) {
            $ds->users()->detach();
            $ds->delete();
        }

        // Delete collections
        foreach ($user->collections as $col) {
            $col->subjectKeywords()->detach();
            $col->datasets()->detach();
            $col->delete();
        }

        // Delete role
        $user->roles()->detach(); 

        // Delete user
        $user->delete();
        return response()->json();
    }
}
