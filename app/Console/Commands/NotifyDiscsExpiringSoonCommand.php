<?php

namespace App\Console\Commands;

use App\Models\Disc;
use App\Models\User;
use App\Notifications\DiscExpiringSoonNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NotifyDiscsExpiringSoonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discs:notify-expiring-soon {--days=7 : How many days ahead to notify} {--dry-run : Show how many notifications would be sent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email notifications for discs that are expiring soon.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now();
        $days = (int) $this->option('days');
        $until = $now->copy()->addDays(max(1, $days));

        $query = Disc::query()
            ->with('user')
            ->where('active', true)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$now, $until])
            ->whereNull('expiring_soon_notified_at');

        $count = (clone $query)->count();
        if ($this->option('dry-run')) {
            $this->info("Would notify {$count} discs.");

            return self::SUCCESS;
        }

        $sent = 0;
        $query->orderBy('id')->chunkById(200, function (Collection $discs) use (&$sent, $now): void {
            /** @var Disc $disc */
            foreach ($discs as $disc) {
                if (! $disc->user instanceof User) {
                    continue;
                }

                if (! $disc->user->email_notify_disc_expiring) {
                    $disc->update(['expiring_soon_notified_at' => $now]);

                    continue;
                }

                $disc->user->notify(new DiscExpiringSoonNotification($disc));
                $disc->update(['expiring_soon_notified_at' => $now]);
                $sent++;
            }
        });

        $this->info("Notified {$sent} discs.");

        return self::SUCCESS;
    }
}
