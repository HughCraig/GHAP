<?php

namespace TLCMap\Http\Controllers\Auth;

use TLCMap\Models\User;
use TLCMap\Models\Role;
use TLCMap\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    protected function redirectTo()
    {
        // if (session('url.intended')) {
        //     return session('url.intended'); //page we tried to load in on whle not logged in or previous page
        // }
        return 'verify'; //verification page
    }

    public function showRegistrationForm()
    {
        if (!session()->has('url.intended')) {
            session(['url.intended' => url()->previous()]);
        }
        return view('auth.register');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $notin = array_merge(explode(' ', strtolower($data['name'])), explode('@', strtolower($data['email']))); //cannot match username, or any part of the email address
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:pgsql2.tlcmap.user'],
            'password' => [
                'required', 'string', 'min:8', 'max:16', 'confirmed', //8+ chars, must match the password-confirm box
                'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^A-Za-z0-9]/', //must contain 1 of each: lowercase uppercase number and special character
                'not_regex:/(.)\1{4,}/', //must not contain any repeating char 4 or more times
                function ($attribute, $value, $fail) use ($notin) {
                    $v = strtolower($value);
                    foreach ($notin as $n) {
                        if (strpos($v, $n) !== false) $fail('Password cannot contain any part of your name or email!');
                    }
                }
            ],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return \TLCMap\User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->roles()->attach(Role::where('name', 'REGULAR')->first());
        return $user;
    }


}
