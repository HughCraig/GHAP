<?php

namespace TLCMap\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use TLCMap\Models\Role;
use TLCMap\Models\User;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create
                            {email : The account email.}
                            {--password= : The account password. If omitted, a random password will be generated.}
                            {--name= : The account display name. If not provided, "No Name" will be used.}
                            {--role= : The user role name. Default is to "REGULAR"}
                            {--active : Flag to determine whether the account should be active}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a GHAP user account';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $email = $this->argument('email');
        // Check whether email is used.
        $user = User::where('email', $email)->first();
        if ($user) {
            $this->error("Email {$email} is taken by another account. Please use a another email.");
        } else {
            $user = new User();
            $user->email = $email;
            $password = $this->option('password');
            // Generate a random password if not provided.
            if (empty($password)) {
                $password = substr(md5(mt_rand()), 0, 12);
            }
            $user->password = Hash::make($password);
            $user->name = $this->option('name') ?? 'No Name';
            if ($this->option('active')) {
                $user->is_active = true;
                $user->email_verified_at = date('Y-m-d');
            }
            $user->save();
            // Assign role.
            $role = null;
            if ($this->option('role')) {
                $role = Role::where('name', $this->option('role'))->first();
            }
            if (!$role) {
                $role = Role::where('name', 'REGULAR')->first();
            }
            $user->roles()->attach($role);
            $this->info("User account {$user->email}({$user->name}) with password {$password} has been created");
        }
    }
}
