<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Signature('academy:backup-db')]
#[Description('Create a private mysqldump backup of the configured production database.')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        abort_unless(DB::connection()->getDriverName() === 'mysql', 1, 'MySQL connection required.');

        $bin = trim((string) shell_exec('command -v mysqldump 2>/dev/null'));
        if ($bin === '') {
            $this->error('mysqldump not found. Create the database export through cPanel/phpMyAdmin.');

            return self::FAILURE;
        }

        $name = 'academy-'.now()->format('Ymd-His').'.sql';
        $relative = 'backups/'.$name;
        Storage::disk('local')->makeDirectory('backups');
        $path = Storage::disk('local')->path($relative);

        $db = config('database.connections.mysql');
        $command = [
            $bin,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--events',
            '--hex-blob',
            '--default-character-set=utf8mb4',
            '--host='.($db['host'] ?? 'localhost'),
            '--port='.(string) ($db['port'] ?? 3306),
            '--user='.($db['username'] ?? ''),
            $db['database'],
        ];

        $out = fopen($path, 'wb');
        $env = ['MYSQL_PWD' => (string) ($db['password'] ?? '')] + $_SERVER;
        $process = proc_open($command, [['pipe', 'r'], $out, ['pipe', 'w']], $pipes, null, $env);
        if (! is_resource($process) || ! is_resource($out)) {
            $this->error('Could not start mysqldump.');

            return self::FAILURE;
        }

        fclose($pipes[0]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        fclose($out);

        if ($exit !== 0 || filesize($path) === 0 || str_contains($error, 'error')) {
            @unlink($path);
            $this->error('Database backup failed.');

            return self::FAILURE;
        }

        $this->info($relative.' ('.filesize($path).' bytes)');

        return self::SUCCESS;
    }
}
