<?php

namespace App\Services;

use App\Mail\CancellationRefundFailedAdminMail;
use App\Mail\CancellationRequestAdminMail;
use App\Mail\CancellationRequestApprovedMail;
use App\Mail\CancellationRequestReceivedMail;
use App\Mail\CancellationRequestRejectedMail;
use App\Models\CancellationRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CancellationNotificationService
{
    public function recordRequested(CancellationRequest $request): void
    {
        $request->loadMissing(['order', 'customer']);

        $customer = $request->customer;
        $customerRecipient = $this->record(
            $request,
            $customer,
            'requested',
            'awaiting_review',
            'Cancellation request received',
            "Your cancellation request for booking #{$request->order_id} was sent to customer care and is awaiting review.",
            "/dashboard/customer?order={$request->order_id}",
        );
        $staffRecipients = $this->recordForStaff(
            $request,
            'requested',
            'awaiting_review',
            'Cancellation request needs review',
            "Cancellation request #{$request->id} for booking #{$request->order_id} needs review.",
        );

        $deliveries = collect([[
            ...$customerRecipient,
            'mailable' => fn (): Mailable => $this->makeRequestedCustomerMail($request),
        ]]);
        $deliveries->push(...$staffRecipients->map(fn (array $recipient): array => [
            ...$recipient,
            'mailable' => fn (): Mailable => new CancellationRequestAdminMail($request),
        ]));

        $this->queueDeliveriesAfterCommit(
            $request,
            'requested',
            $deliveries,
        );
    }

    public function recordApproved(CancellationRequest $request): void
    {
        $request->loadMissing('customer');
        $recipient = $this->record(
            $request,
            $request->customer,
            'approved',
            'approved',
            'Cancellation request approved',
            "Your cancellation request for booking #{$request->order_id} was approved.",
            "/dashboard/customer?order={$request->order_id}",
        );

        $this->queueDeliveriesAfterCommit(
            $request,
            'approved',
            collect([[
                ...$recipient,
                'mailable' => fn (): Mailable => new CancellationRequestApprovedMail($request),
            ]]),
        );
    }

    public function recordRejected(CancellationRequest $request): void
    {
        $request->loadMissing('customer');
        $recipient = $this->record(
            $request,
            $request->customer,
            'rejected',
            'rejected',
            'Cancellation request declined',
            "Your cancellation request for booking #{$request->order_id} was declined.",
            "/dashboard/customer?order={$request->order_id}",
        );

        $this->queueDeliveriesAfterCommit(
            $request,
            'rejected',
            collect([[
                ...$recipient,
                'mailable' => fn (): Mailable => new CancellationRequestRejectedMail($request),
            ]]),
        );
    }

    public function recordRefundFailed(CancellationRequest $request): void
    {
        $recipients = $this->recordForStaff(
            $request,
            'refund_failed',
            'needs_attention',
            'Cancellation refund needs attention',
            "The refund for cancellation request #{$request->id}, booking #{$request->order_id}, needs attention.",
        );

        $this->queueDeliveriesAfterCommit(
            $request,
            'refund_failed',
            $recipients->map(fn (array $recipient): array => [
                ...$recipient,
                'mailable' => fn (): Mailable => new CancellationRefundFailedAdminMail($request),
            ]),
        );
    }

    public function recordRefundConfirmationFailed(CancellationRequest $request): void
    {
        $recipients = $this->recordForStaff(
            $request,
            'refund_confirmation_failed',
            'refund_processing',
            'Cancellation refund confirmation needs attention',
            "The refund for cancellation request #{$request->id}, booking #{$request->order_id}, could not be confirmed.",
        );

        $this->queueDeliveriesAfterCommit(
            $request,
            'refund_confirmation_failed',
            $recipients->map(fn (array $recipient): array => [
                ...$recipient,
                'mailable' => fn (): Mailable => new CancellationRefundFailedAdminMail($request),
            ]),
        );
    }

    /** @return Collection<int, array{recipient: User, newly_created: bool}> */
    private function recordForStaff(
        CancellationRequest $request,
        string $event,
        string $safeStatus,
        string $title,
        string $message,
    ): Collection {
        return $this->activeStaff()
            ->map(fn (User $user): array => $this->record(
                $request,
                $user,
                $event,
                $safeStatus,
                $title,
                $message,
                "/dashboard/admin/orders?order={$request->order_id}",
            ))
            ->values();
    }

    /** @return array{recipient: User, newly_created: bool} */
    private function record(
        CancellationRequest $request,
        User $recipient,
        string $event,
        string $safeStatus,
        string $title,
        string $message,
        string $actionUrl,
    ): array {
        $notification = Notification::query()->firstOrCreate(
            [
                'deduplication_key' => "cancellation:{$request->id}:{$event}:user:{$recipient->id}",
            ],
            [
                'user_id' => $recipient->id,
                'type' => 'custom',
                'title' => $title,
                'message' => $message,
                'data' => [
                    'event' => $event,
                    'order_id' => $request->order_id,
                    'cancellation_request_id' => $request->id,
                    'safe_status' => $safeStatus,
                ],
                'action_url' => $actionUrl,
                'display_style' => 'inline',
            ],
        );

        return [
            'recipient' => $recipient,
            'newly_created' => $notification->wasRecentlyCreated,
        ];
    }

    /** @return Collection<int, User> */
    private function activeStaff(): Collection
    {
        return User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
            ->where('status', User::STATUS_ACTIVE)
            ->whereNotNull('email')
            ->whereRaw("TRIM(email) <> ''")
            ->orderBy('id')
            ->get(['id', 'email']);
    }

    /** @param  Collection<int, array{recipient: User, newly_created: bool, mailable: callable(): Mailable}>  $deliveries */
    private function queueDeliveriesAfterCommit(
        CancellationRequest $request,
        string $event,
        Collection $deliveries,
    ): void {
        $uniqueDeliveries = $deliveries
            ->filter(fn (array $delivery): bool => trim((string) $delivery['recipient']->email) !== '')
            ->unique(fn (array $delivery): string => strtolower(trim($delivery['recipient']->email)))
            ->filter(fn (array $delivery): bool => $delivery['newly_created'])
            ->values();

        foreach ($uniqueDeliveries as $delivery) {
            $recipient = $delivery['recipient'];
            $email = strtolower(trim($recipient->email));
            $mailable = $delivery['mailable'];

            DB::afterCommit(function () use ($request, $event, $recipient, $email, $mailable): void {
                try {
                    $mail = $mailable();
                    Mail::to($email)->queue($mail);
                } catch (Throwable $exception) {
                    Log::warning('Cancellation notification mail queue failed.', [
                        'cancellation_request_id' => $request->id,
                        'order_id' => $request->order_id,
                        'event' => $event,
                        'recipient_user_id' => $recipient->id,
                        'exception_class' => $exception::class,
                    ]);
                }
            });
        }
    }

    protected function makeRequestedCustomerMail(CancellationRequest $request): Mailable
    {
        return new CancellationRequestReceivedMail($request);
    }
}
