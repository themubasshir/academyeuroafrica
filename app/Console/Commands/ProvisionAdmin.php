<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;

/**
 * One-time production administrator provisioning.
 *
 * Never shipped reachable from a public route — it is only invoked via the
 * IP+token-gated deploy runner (which self-disables) or a host CLI. No default
 * No password is generated or printed. The administrator chooses a password
 * through Laravel's expiring, one-time password-reset email.
 */
#[Signature('academy:provision-admin
    {--email= : Administrator email; defaults to storage/app/deploy.admin}
    {--name= : Display name}')]
#[Description('Provision a production administrator and send a one-time password-reset link.')]
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

        $user = User::where('email', $email)->first();
        if ($user !== null && $user->email_verified_at === null) {
            $this->error('Administrator email is not verified. Complete email verification before requesting a reset link.');

            return self::FAILURE;
        }

        $user ??= new User(['email' => $email]);
        $user->forceFill([
            'name' => $this->option('name') ?: 'Academy Administrator',
            'status' => 'active',
            'disabled_at' => null,
        ])->save();
        $user->assignRole(Role::ACADEMY_ADMIN);

        $status = Password::sendResetLink(['email' => $email]);
        if ($status !== Password::RESET_LINK_SENT) {
            $this->error('Password reset link could not be sent.');

            return self::FAILURE;
        }

        $this->info('Administrator provisioned. A one-time password reset link was sent to the configured email.');

        return self::SUCCESS;
    }
}
