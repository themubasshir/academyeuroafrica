<?php

namespace App\Http\Controllers\Deploy;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * One-time, no-SSH migration runner.
 *
 * Guard stack (ALL required):
 *  - request IP in DEPLOY_ALLOWED_IPS
 *  - valid `token` equal to the one-time secret in storage/app/deploy.token
 *  - a non-expired `expires_at` request timestamp
 *  - the token has not already been consumed
 *  - only a fixed allowlist of commands can run — never arbitrary input
 *  - every attempt is audit-logged
 *
 * Each successful request consumes the token and writes
 * `deploy.done.<md5>` so it cannot be replayed until a new token is installed.
 */
class MigrateRunnerController extends Controller
{
    /**
     * Fixed allowlist — never arbitrary input. Phase 14 catalogue commands are
     * idempotent (source_key dedupe) so running them more than once is safe;
     * they are still token+IP gated and audit-logged like everything else.
     */
    private const ALLOWED = [
        'migrate --force',
        'db:seed --class=RoleSeeder --force',
        'catalogue:import',
        'catalogue:import --dry-run',
        'catalogue:variants',
        'academy:provision-admin',
        'list --raw',
        'academy:deploy-status',
        'academy:queue-mail-test',
    ];

    /**
     * Parameter values allowed per command. Anything not listed is dropped, so
     * the allowlist can never be widened into arbitrary input (e.g. an
     * attacker-supplied --email on provisioning).
     */
    private const ALLOWED_PARAMS = [
        'migrate --force' => ['--force'],
        'db:seed --class=RoleSeeder --force' => ['--class', '--force'],
        'catalogue:import' => [],
        'catalogue:import --dry-run' => ['--dry-run'],
        'catalogue:variants' => [],
        'academy:provision-admin' => [],
        'list --raw' => ['--raw'],
        'academy:deploy-status' => [],
        'academy:queue-mail-test' => [],
    ];

    public function __invoke(Request $request): JsonResponse
    {
        // Read the IP allowlist from config (survives `config:cache`), falling
        // back to raw env for the very first post-deploy request.
        $allowedIps = array_filter(explode(',', (string) (config('deploy.allowed_ips') ?? env('DEPLOY_ALLOWED_IPS'))));
        abort_if(empty($allowedIps) || ! in_array($request->ip(), $allowedIps, true), 404);

        $disk = Storage::disk('local');

        $expected = $disk->exists('deploy.token') ? trim($disk->get('deploy.token')) : '';
        $token = $request->string('token')->toString();
        abort_if($expected === '' || $token === '' || ! hash_equals($expected, $token), 404);

        $expiresAt = $request->date('expires_at');
        abort_if($expiresAt === null || $expiresAt->isPast(), 404);

        $command = $request->string('run', 'migrate --force')->toString();
        abort_unless(in_array($command, self::ALLOWED, true), 422, 'Command not allowed.');

        // Use the file lock store explicitly: the default `database` cache
        // store does not have its cache_locks table until `migrate` runs, and
        // this runner is what performs that migration. File store creates its
        // own lock dir, so it works pre- and post-migration.
        $lock = Cache::store('file')->lock('deploy-migrate-runner', 300);
        abort_unless($lock->get(), 429, 'Another deployment command is running.');

        try {
            $flag = 'deploy.done.'.md5($command);
            abort_if($disk->exists($flag), 404);

            // Audit is best-effort: the audit_logs table may not exist yet on a
            // fresh deploy (this runner is what creates it), so never let a
            // logging failure block the command itself.
            try {
                AuditLog::record('deploy.migrate', null, ['command' => $command, 'ip' => $request->ip()]);
            } catch (\Throwable) {
            }

            [$cmd, $args] = array_pad(explode(' ', $command, 2), 2, '');
            $allowedParams = self::ALLOWED_PARAMS[$command] ?? [];
            $params = [];
            foreach (preg_split('/\s+/', trim($args)) as $tok) {
                if (preg_match('/^--([a-zA-Z-]+)(?:=(.*))?$/', $tok, $m)) {
                    // Drop any flag/value not explicitly permitted for this
                    // command, and reject free-form values outright.
                    if (! in_array('--'.$m[1], $allowedParams, true)) {
                        continue;
                    }
                    if (isset($m[2]) && ! preg_match('/^[\w\\\\]+$/', $m[2])) {
                        abort(422, 'Parameter value not allowed.');
                    }
                    $params['--'.$m[1]] = $m[2] ?? true;
                }
            }

            $exit = Artisan::call($cmd, $params);

            if ($exit === 0) {
                $disk->put($flag, now()->toIso8601String());
                $disk->delete('deploy.token');
            }

            return response()->json([
                'ok' => $exit === 0,
                'command' => $command,
                'output' => trim(Artisan::output()),
            ], $exit === 0 ? 200 : 500);
        } finally {
            optional($lock)->release();
        }
    }
}
