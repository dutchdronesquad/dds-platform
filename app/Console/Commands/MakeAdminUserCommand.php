<?php

namespace App\Console\Commands;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\PermissionRegistrar;

class MakeAdminUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dds:make-admin
        {email? : Email address of the admin user}
        {--name= : Name to use when creating a new user}
        {--password= : Password to set for the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or promote a user to the DDS admin role.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        app(RolesAndPermissionsSeeder::class)->run();

        $email = $this->argument('email') ?: $this->ask('Email address');
        $existingUser = is_string($email) ? User::query()->where('email', $email)->first() : null;
        $name = $this->option('name') ?: $existingUser?->name ?: $this->ask('Name');
        $password = $this->option('password');
        $generatedPassword = null;

        if (! $existingUser && ! is_string($password)) {
            $password = $generatedPassword = Str::password(24);
        }

        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'password' => [$existingUser ? 'nullable' : 'required', 'string', Password::default()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $validated = $validator->validated();
        $user = $existingUser ?: new User(['email' => $validated['email']]);

        $user->forceFill([
            'name' => $validated['name'],
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        if (is_string($validated['password'] ?? null) && $validated['password'] !== '') {
            $user->password = $validated['password'];
        }

        $user->save();
        $user->assignRole(RoleEnum::Admin->value);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info(sprintf('Admin access granted to %s.', $user->email));

        if (is_string($generatedPassword)) {
            $this->newLine();
            $this->warn('Generated password: '.$generatedPassword);
            $this->warn('Store this password now. It will not be shown again.');
        }

        return self::SUCCESS;
    }
}
