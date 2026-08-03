<?php

namespace App\Jobs;

use App\Mail\SupportRequestAlertMail;
use App\Models\SupportRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendSupportRequestAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public int $supportRequestId) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /**
     * @return list<WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("support-request:{$this->supportRequestId}:alert"))
                ->releaseAfter(10)
                ->expireAfter(60)
                ->shared(),
        ];
    }

    public function handle(): void
    {
        $supportRequest = SupportRequest::query()->findOrFail($this->supportRequestId);

        if ($supportRequest->support_notified_at !== null) {
            return;
        }

        Mail::to((string) config('mail.support_address'))
            ->send(new SupportRequestAlertMail($supportRequest));

        $supportRequest->forceFill([
            'support_notified_at' => now(),
            'support_notification_failed_at' => null,
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        $supportRequest = SupportRequest::find($this->supportRequestId);

        if (
            $supportRequest === null
            || $supportRequest->support_notified_at !== null
        ) {
            return;
        }

        $markedFailed = SupportRequest::query()
            ->whereKey($supportRequest->id)
            ->whereNull('support_notified_at')
            ->update([
                'support_notification_failed_at' => now(),
            ]);

        if ($markedFailed === 0) {
            return;
        }

        Log::error('Support request inbox alert failed.', [
            'reference' => $supportRequest->reference,
            'exception' => $exception::class,
        ]);
    }
}
