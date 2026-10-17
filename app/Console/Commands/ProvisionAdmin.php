<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One-time production administrator provisioning.
 *
 * Never shipped reachable from a public route — it is only invoked via the
 * IP+token-gated deploy runner (which self-disables) or a host CLI. No default
 * or predictable password: a random one is generated, printed once to the
 * runner's response, and must be changed at first login.
 */
#[Signature('academy:provision-admin
    {--email= : Administrator email; defaults to storage/app/deploy.admin}
    {--name= : Display name}
    {--password= : Explicit password; omit to auto-generate a random one}
    {--rotate : Reset password on an existing admin account and return a new one}')]
#[Description('Provision (or rotate) a production administrator. One-time, no default password, safe to re-run.')]
class ProvisionAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->option('email')
            ?? trim((string) (Storage::disk('local')->exists('deploy.admin')
                ? Storage::disk('local')->get('deploy.admin')
                : ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('No valid email — pass --email= or write the admin email to storage/app/deploy.admin.');

            return self::FAILURE;
        }

        $generated = ! $this->option('password');
        $password = $this->option('password') ?: Str::password(20);
        if (strlen($password) < 12) {
            $this->error('Password must be at least 12 characters.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        if ($user && ! $this->option('rotate')) {
            $this->warn("{$email} already exists — nothing changed. Re-run with --rotate to reset the password.");

            return self::SUCCESS;
        }

        $user ??= new User(['email' => $email]);
        $user->forceFill([
            'name' => $this->option('name') ?: 'Academy Administrator',
            'password' => Hash::make($password),
            'status' => 'active',
            'disabled_at' => null,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
        $user->assignRole(Role::ACADEMY_ADMIN);

        $this->info(sprintf('Admin %s: %s', $user->wasRecentlyCreated ? 'created' : 'rotated', $email));
        if ($generated) {
            // Printed once to the gated response/log — never stored or e-mailed.
            $this->warn('One-time password (change at first login): '.$password);
        }

        return self::SUCCESS;
    }
}
