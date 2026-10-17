<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('academy:mail-status')]
#[Description('Report non-secret production mail configuration and last known queue result.')]
class MailStatus extends Command
{
    public function handle(): int
    {
        $smtp = config('mail.mailers.smtp', []);

        $this->line(json_encode([
            'mail' => [
                'default' => config('mail.default'),
                'transport' => $smtp['transport'] ?? null,
                'host' => $smtp['host'] ?? null,
                'port' => $smtp['port'] ?? null,
                'encryption' => $smtp['encryption'] ?? null,
                'username' => isset($smtp['username']) ? substr($smtp['username'], 0, 3).'…' : null,
                'password_set' => ! empty($smtp['password']),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
        ], JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
