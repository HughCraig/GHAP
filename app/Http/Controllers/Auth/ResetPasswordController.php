<?php

namespace TLCMap\Http\Controllers\Auth;

use TLCMap\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Validator;

use TLCMap\Models\User;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/';
    protected $request;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->middleware('guest');
        $this->request = $request;
    }

    /**
     * Rules that gets passed through the trait to the built-in validator
     */
    protected function rules()
    {
        $email = $this->request->input('email'); //get email from form
        $user = User::where('email', $email)->first(); //get user from email

        if (!$user) {
            return [function ($attribute, $value, $fail) {
                $fail("Email doesn't exist!");
            }];
        } //no user by this email, return a failure

        $notin = array_merge(explode(' ', strtolower($user->name)), explode('@', strtolower($user->email))); //cannot match username, or any part of the email address

        return [
            'email' => ['required', 'exists:pgsql.tlcmap.user'],
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
    }
}
