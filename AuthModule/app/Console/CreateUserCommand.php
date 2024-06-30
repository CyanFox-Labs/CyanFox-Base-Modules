<?php

namespace Modules\AuthModule\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Modules\AuthModule\Models\User;
use Modules\AuthModule\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\text;
use function Laravel\Prompts\password;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'authmodule:create-user';

    /**
     * The console command description.
     */
    protected $description = 'Create a new user';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $firstName = text('First Name', required: true);
        $lastName = text('Last Name', required: true);
        $username = text('Username', required: true, validate: ['unique:users,username']);
        $email = text('Email', required: true, validate: ['email', 'unique:users,email']);
        $password = password('Password', required: true);
        $groups = multiselect(
            'Groups',
            Role::all()->pluck('name')->toArray(),
        );
        $permissions = multiselect(
            'Permissions',
            Permission::all()->pluck('name')->toArray(),
        );

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole($groups);
        $user->givePermissionTo($permissions);

        $this->info('User created successfully');
    }
}