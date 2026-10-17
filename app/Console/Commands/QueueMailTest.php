<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\StudentAccountNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('academy:queue-mail-test')]
#[Description('Queue one controlled test notification to the authorized deploy recipient in storage/app/private/deploy.admin.')]
class QueueMailTest extends Command
{
    public function handle(): int
    {
        $email = trim((string) Storage::disk('local')->get('deploy.admin'));
        $user = $email !== '' ? User::where('email', $email)->first() : null;

        if (! $user) {
            $this->error('Authorized deploy recipient not found.');

            return self::FAILURE;
        }

        $user->notify(new StudentAccountNotification(
            'EAMN queue mail test',
            'Controlled production queue test. Reply received if this reached the authorized mailbox.',
            route('student.notifications'),
            'system',
        ));

        $this->info("Queued test notification for {$email}");

        return self::SUCCESS;
    }
}
