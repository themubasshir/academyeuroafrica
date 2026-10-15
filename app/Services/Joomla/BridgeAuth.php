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

    public function consumeCode(string $code): User
    {
        $claims = $this->client->exchangeCode($code);

        return DB::transaction(function () use ($claims) {
            $identity = ExternalIdentity::where('provider', 'joomla')
                ->where('subject', $claims['sub'])
                ->first();

            if ($identity !== null) {
                $user = $identity->user;
                $this->syncUser($user, $claims);
                AuditLog::record('bridge.login', $user, ['provider' => 'joomla']);

                return $user;
            }

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
        $dirty = false;

        if ($user->email !== $claims['email']) {
            $user->email = $claims['email'];
            $dirty = true;
        }
        if ($user->name !== $claims['name']) {
            $user->name = $claims['name'];
            $dirty = true;
        }

        if ($dirty) {
            $user->save();
        }
    }
}
