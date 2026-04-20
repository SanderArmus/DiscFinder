<?php

namespace App\Console\Commands;

use App\Models\Disc;
use App\Models\User;
use App\Notifications\DiscExpiredNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ExpireDiscsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discs:expire {--dry-run : Show how many discs would be expired without updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark expired discs inactive based on expires_at timestamp.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now();

        $query = Disc::query()
            ->with('user')
            ->where('active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now);

        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("Would expire {$count} discs.");

            return self::SUCCESS;
        }

        /** @var \Illuminate\Support\Collection<int, \App\Models\Disc> $expiredDiscs */
        $expiredDiscs = (clone $query)->get();

        $updated = Disc::query()
            ->whereKey($expiredDiscs->pluck('id')->all())
            ->update(['active' => false]);

        $notified = 0;
        foreach ($expiredDiscs as $disc) {
            if (! $disc->user instanceof User) {
                continue;
            }

            if (! $disc->user->email_notify_disc_expired) {
                continue;
            }

            $disc->user->notify(new DiscExpiredNotification($disc));
            $notified++;
        }

        $this->info("Expired {$updated} discs. Notified {$notified} users.");

        return self::SUCCESS;
    }
}
