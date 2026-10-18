<?php

namespace App\Services\Joomla;

use App\Models\AuditLog;
use App\Models\ExternalIdentity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Consumes a bridge authorisation code and resolves the local user.
 *
 * Rules:
 *  - Existing external identity (provider=joomla, subject match) → its user.
 *  - No identity but email matches → do NOT auto-merge; record an
 *    identity_conflicts row for an admin to resolve.
 *  - Otherwise → provision a new active student user.
 */
class BridgeAuth
{
    public function __construct(private BridgeClient $client) {}

    public function consumeCode(string $code, string $state): User
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $code) || ! preg_match('/^[a-f0-9]{64}$/', $state)) {
            throw new RuntimeException('invalid-bridge-code');
        }

        $claims = $this->client->exchangeCode($code, $state);

        // Collision check BEFORE the transaction so the conflict row persists
        // even though we throw afterwards.
        $mapped = ExternalIdentity::where('provider', 'joomla')->where('subject', $claims['sub'])->exists();
        if (! $mapped) {
            $existing = User::where('email', $claims['email'])->first();
            if ($existing !== null) {
                DB::table('identity_conflicts')->insert([
                    'provider' => 'joomla',
                    'subject' => $claims['sub'],
                    'email' => $claims['email'],
                    'existing_user_id' => $existing->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                AuditLog::record('bridge.identity_conflict', $existing, ['subject' => $claims['sub']]);

                throw new RuntimeException('identity-conflict');
            }
        }

        return DB::transaction(function () use ($claims) {
            $identity = ExternalIdentity::where('provider', 'joomla')
                ->where('subject', $claims['sub'])
                ->first();

            if ($identity !== null) {
                $user = $identity->user;
                if ($user->isAdmin()) {
                    throw new RuntimeException('joomla-admin-not-permitted');
                }

                $this->syncUser($user, $claims);
                AuditLog::record('bridge.login', $user, ['provider' => 'joomla']);

                return $user;
            }

            $user = User::create([
                'name' => $claims['name'],
                'email' => $claims['email'],
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(40)),
                'status' => 'active',
            ]);
            $user->assignRole(Role::STUDENT);

            ExternalIdentity::create([
                'user_id' => $user->id,
                'provider' => 'joomla',
                'subject' => $claims['sub'],
                'email' => $claims['email'],
                'claims' => $claims,
                'linked_at' => now(),
            ]);

            AuditLog::record('bridge.provisioned', $user, ['provider' => 'joomla']);

            return $user;
        });
    }

    private function syncUser(User $user, array $claims): void
    {
        if ($user->name === $claims['name']) {
            return;
        }

        $user->update(['name' => $claims['name']]);
    }
}
