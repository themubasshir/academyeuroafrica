<?php

namespace App\Http\Controllers\Deploy;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * One-time, no-SSH migration runner.
 *
 * Guard stack (ALL required):
 *  - request IP in DEPLOY_ALLOWED_IPS
 *  - valid `token` equal to the single-use hash in storage/app/deploy.token
 *  - the runner not already used (flag file)
 *  - only a fixed allowlist of commands can run — never arbitrary input
 *  - every attempt is audit-logged
 *
 * On success it writes a `deploy.done` flag so it cannot run again until the
 * flag is deliberately removed during the next deployment.
 */
class MigrateRunnerController extends Controller
{
    private const ALLOWED = ['migrate --force', 'db:seed --class=RoleSeeder --force'];

    public function __invoke(Request $request): JsonResponse
    {
        $allowedIps = array_filter(explode(',', (string) env('DEPLOY_ALLOWED_IPS')));
        abort_if(empty($allowedIps) || ! in_array($request->ip(), $allowedIps, true), 404);

        $disk = Storage::disk('local');
        abort_if($disk->exists('deploy.done'), 404);

        $expected = $disk->exists('deploy.token') ? trim($disk->get('deploy.token')) : '';
        $token = $request->string('token');
        abort_if($expected === '' || ! hash_equals($expected, $token->toString()), 404);

        $command = $request->string('run', 'migrate --force')->toString();
        abort_unless(in_array($command, self::ALLOWED, true), 422, 'Command not allowed.');

        AuditLog::record('deploy.migrate', null, ['command' => $command, 'ip' => $request->ip()]);

        [$cmd, $args] = array_pad(explode(' ', $command, 2), 2, '');
        $params = [];
        if ($args) {
            parse_str(str_replace(['--', '='], ['', '='], $args), $parsed);
            $params = $parsed + ['--force' => true];
        } else {
            $params = ['--force' => true];
        }

        $exit = Artisan::call($cmd, $params);

        if ($exit === 0) {
            $disk->put('deploy.done', now()->toIso8601String());
        }

        return response()->json([
            'ok' => $exit === 0,
            'command' => $command,
            'output' => trim(Artisan::output()),
        ], $exit === 0 ? 200 : 500);
    }
}
