<?php

namespace App\Jobs;

use App\Mail\SupportRequestReceiptMail;
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

class SendSupportRequestReceipt implements ShouldQueue
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
            (new WithoutOverlapping("support-request:{$this->supportRequestId}:receipt"))
                ->releaseAfter(10)
                ->expireAfter(60)
                ->shared(),
        ];
    }

    public function handle(): void
    {
        $supportRequest = SupportRequest::query()->findOrFail($this->supportRequestId);

        if ($supportRequest->traveler_notified_at !== null) {
            return;
        }

        Mail::to($supportRequest->email)
            ->send(new SupportRequestReceiptMail($supportRequest));

        $supportRequest->forceFill([
            'traveler_notified_at' => now(),
            'traveler_notification_failed_at' => null,
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        $supportRequest = SupportRequest::find($this->supportRequestId);

        if (
            $supportRequest === null
            || $supportRequest->traveler_notified_at !== null
        ) {
            return;
        }

        $markedFailed = SupportRequest::query()
            ->whereKey($supportRequest->id)
            ->whereNull('traveler_notified_at')
            ->update([
                'traveler_notification_failed_at' => now(),
            ]);

        if ($markedFailed === 0) {
            return;
        }

        Log::error('Support request traveler receipt failed.', [
            'reference' => $supportRequest->reference,
            'exception' => $exception::class,
        ]);
    }
}
