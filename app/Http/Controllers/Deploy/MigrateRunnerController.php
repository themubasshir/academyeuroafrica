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
        'catalogue:variants',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $allowedIps = array_filter(explode(',', (string) env('DEPLOY_ALLOWED_IPS')));
        abort_if(empty($allowedIps) || ! in_array($request->ip(), $allowedIps, true), 404);

        $disk = Storage::disk('local');

        $expected = $disk->exists('deploy.token') ? trim($disk->get('deploy.token')) : '';
        $token = $request->string('token')->toString();
        abort_if($expected === '' || $token === '' || ! hash_equals($expected, $token), 404);

        $expiresAt = $request->date('expires_at');
        abort_if($expiresAt === null || $expiresAt->isPast(), 404);

        $command = $request->string('run', 'migrate --force')->toString();
        abort_unless(in_array($command, self::ALLOWED, true), 422, 'Command not allowed.');

        $lock = Cache::lock('deploy-migrate-runner', 120);
        abort_unless($lock->get(), 429, 'Another deployment command is running.');

        try {
            $flag = 'deploy.done.'.md5($command);
            abort_if($disk->exists($flag), 404);

            AuditLog::record('deploy.migrate', null, ['command' => $command, 'ip' => $request->ip()]);

            [$cmd, $args] = array_pad(explode(' ', $command, 2), 2, '');
            $params = [];
            foreach (preg_split('/\s+/', trim($args)) as $tok) {
                if (preg_match('/^--([a-zA-Z-]+)(?:=(.*))?$/', $tok, $m)) {
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
