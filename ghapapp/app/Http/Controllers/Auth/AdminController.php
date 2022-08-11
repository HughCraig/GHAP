<?php

namespace TLCMap\Http\Controllers\Auth;

use TLCMap\Models\User;
use TLCMap\Models\Role;
use TLCMap\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}
