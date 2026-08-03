<?php

namespace Tests\Feature;

use App\Jobs\SendSupportRequestAlert;
use App\Jobs\SendSupportRequestReceipt;
use App\Mail\SupportRequestAlertMail;
use App\Mail\SupportRequestReceiptMail;
use App\Models\Activity;
use App\Models\ActivityLocation;
use App\Models\City;
use App\Models\SupportRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\PendingMail;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SupportRequestNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'app.frontend_url' => 'http://localhost:3000/',
            'mail.support_address' => 'support@example.com',
        ]);
    }

    public function test_new_ticket_dispatches_both_jobs_after_commit_once_and_retry_dispatches_neither(): void
    {
        Bus::fake();
        $activity = $this->activityInDubai();
        $payload = $this->validPayload($activity);

        $this->postJson('/api/support-requests', $payload)->assertCreated();
        $ticket = SupportRequest::sole();

        Bus::assertDispatched(SendSupportRequestReceipt::class, function (
            SendSupportRequestReceipt $job,
        ) use ($ticket): bool {
            return $job->supportRequestId === $ticket->id
                && $job->afterCommit === true;
        });
        Bus::assertDispatched(SendSupportRequestAlert::class, function (
            SendSupportRequestAlert $job,
        ) use ($ticket): bool {
            return $job->supportRequestId === $ticket->id
                && $job->afterCommit === true;
        });

        $this->postJson('/api/support-requests', $payload)->assertOk();

        Bus::assertDispatchedTimes(SendSupportRequestReceipt::class, 1);
        Bus::assertDispatchedTimes(SendSupportRequestAlert::class, 1);
    }

    public function test_receipt_job_sends_traveler_mail_and_records_delivery(): void
    {
        Mail::fake();
        $ticket = $this->ticket([
            'traveler_notification_failed_at' => now()->subMinute(),
        ]);

        $job = new SendSupportRequestReceipt($ticket->id);
        $job->handle();
        $job->handle();

        Mail::assertSentTimes(SupportRequestReceiptMail::class, 1);
        Mail::assertSent(
            SupportRequestReceiptMail::class,
            function (SupportRequestReceiptMail $mail) use ($ticket): bool {
                $mail->assertTo($ticket->email);
                $mail->assertHasSubject("We received your support request {$ticket->reference}");
                $mail->assertHasReplyTo('support@example.com');
                $mail->assertSeeInHtml($ticket->reference);
                $mail->assertSeeInHtml($ticket->item_title);
                $mail->assertSeeInHtml('Before you book');
                $mail->assertSeeInHtml($ticket->page_url);
                $mail->assertDontSeeInHtml($ticket->client_request_id);
                $mail->assertDontSeeInHtml($ticket->message);

                return true;
            },
        );

        $ticket->refresh();
        $this->assertNotNull($ticket->traveler_notified_at);
        $this->assertNull($ticket->traveler_notification_failed_at);
        $this->assertNull($ticket->support_notified_at);
        $this->assertSame('open', $ticket->status);
    }

    public function test_alert_job_sends_support_mail_and_records_delivery(): void
    {
        Mail::fake();
        $ticket = $this->ticket([
            'support_notification_failed_at' => now()->subMinute(),
        ]);

        $job = new SendSupportRequestAlert($ticket->id);
        $job->handle();
        $job->handle();

        Mail::assertSentTimes(SupportRequestAlertMail::class, 1);
        Mail::assertSent(
            SupportRequestAlertMail::class,
            function (SupportRequestAlertMail $mail) use ($ticket): bool {
                $mail->assertTo('support@example.com');
                $mail->assertHasSubject(
                    "[{$ticket->reference}] New {$ticket->topic} support request",
                );
                $mail->assertHasReplyTo($ticket->email, $ticket->name);
                $mail->assertSeeInHtml($ticket->reference);
                $mail->assertSeeInHtml($ticket->item_title);
                $mail->assertSeeInHtml('Before you book');
                $mail->assertSeeInHtml($ticket->page_url);
                $mail->assertSeeInHtml($ticket->message);

                return true;
            },
        );

        $ticket->refresh();
        $this->assertNotNull($ticket->support_notified_at);
        $this->assertNull($ticket->support_notification_failed_at);
        $this->assertNull($ticket->traveler_notified_at);
        $this->assertSame('open', $ticket->status);
    }

    public function test_receipt_failure_marks_only_traveler_failure_and_logs_no_private_data(): void
    {
        $ticket = $this->ticket([
            'support_notified_at' => now()->subMinute(),
        ]);
        $supportNotifiedAt = $ticket->support_notified_at;
        $exception = new RuntimeException('mail down');
        $pendingMail = Mockery::mock(PendingMail::class);
        Mail::shouldReceive('to')->once()->with($ticket->email)->andReturn($pendingMail);
        /** @var \Mockery\Expectation $sendExpectation */
        $sendExpectation = $pendingMail->shouldReceive('send');
        $sendExpectation->once()->andThrow($exception);
        $log = Log::spy();
        /** @var \Mockery\Expectation $logExpectation */
        $logExpectation = $log->shouldReceive('error');
        $logExpectation->once()
            ->withArgs(function (string $message, array $context) use ($ticket): bool {
                $encoded = json_encode([$message, $context]);

                return $context === [
                    'reference' => $ticket->reference,
                    'exception' => RuntimeException::class,
                ]
                    && is_string($encoded)
                    && ! str_contains($encoded, $ticket->email)
                    && ! str_contains($encoded, $ticket->message);
            });
        $job = new SendSupportRequestReceipt($ticket->id);

        try {
            $job->handle();
            $this->fail('The receipt delivery exception was not rethrown.');
        } catch (RuntimeException $thrown) {
            $this->assertSame($exception, $thrown);
        }

        $job->failed($exception);

        $ticket->refresh();
        $this->assertNotNull($ticket->traveler_notification_failed_at);
        $this->assertNull($ticket->traveler_notified_at);
        $this->assertTrue($ticket->support_notified_at?->equalTo($supportNotifiedAt));
        $this->assertNull($ticket->support_notification_failed_at);
        $this->assertSame('open', $ticket->status);
    }

    public function test_alert_failure_marks_only_support_failure_and_logs_no_private_data(): void
    {
        $ticket = $this->ticket([
            'traveler_notified_at' => now()->subMinute(),
        ]);
        $travelerNotifiedAt = $ticket->traveler_notified_at;
        $exception = new RuntimeException('mail down');
        $pendingMail = Mockery::mock(PendingMail::class);
        Mail::shouldReceive('to')->once()->with('support@example.com')->andReturn($pendingMail);
        /** @var \Mockery\Expectation $sendExpectation */
        $sendExpectation = $pendingMail->shouldReceive('send');
        $sendExpectation->once()->andThrow($exception);
        $log = Log::spy();
        /** @var \Mockery\Expectation $logExpectation */
        $logExpectation = $log->shouldReceive('error');
        $logExpectation->once()
            ->withArgs(function (string $message, array $context) use ($ticket): bool {
                $encoded = json_encode([$message, $context]);

                return $context === [
                    'reference' => $ticket->reference,
                    'exception' => RuntimeException::class,
                ]
                    && is_string($encoded)
                    && ! str_contains($encoded, $ticket->email)
                    && ! str_contains($encoded, $ticket->message);
            });
        $job = new SendSupportRequestAlert($ticket->id);

        try {
            $job->handle();
            $this->fail('The support alert delivery exception was not rethrown.');
        } catch (RuntimeException $thrown) {
            $this->assertSame($exception, $thrown);
        }

        $job->failed($exception);

        $ticket->refresh();
        $this->assertNotNull($ticket->support_notification_failed_at);
        $this->assertNull($ticket->support_notified_at);
        $this->assertTrue($ticket->traveler_notified_at?->equalTo($travelerNotifiedAt));
        $this->assertNull($ticket->traveler_notification_failed_at);
        $this->assertSame('open', $ticket->status);
    }

    public function test_failed_callbacks_safely_ignore_a_missing_ticket(): void
    {
        $log = Log::spy();
        $exception = new RuntimeException('mail down');

        (new SendSupportRequestReceipt(999999))->failed($exception);
        (new SendSupportRequestAlert(999999))->failed($exception);

        $log->shouldNotHaveReceived('error');
        $this->addToAssertionCount(1);
    }

    public function test_failed_callbacks_ignore_an_audience_that_was_already_notified(): void
    {
        $notifiedAt = now()->subMinute();
        $receiptTicket = $this->ticket([
            'traveler_notified_at' => $notifiedAt,
        ]);
        $alertTicket = $this->ticket([
            'client_request_id' => (string) Str::uuid(),
            'reference' => 'WLP-260731-DEF456',
            'support_notified_at' => $notifiedAt,
        ]);
        $log = Log::spy();
        $exception = new RuntimeException('worker reported a late failure');

        (new SendSupportRequestReceipt($receiptTicket->id))->failed($exception);
        (new SendSupportRequestAlert($alertTicket->id))->failed($exception);

        $receiptTicket->refresh();
        $alertTicket->refresh();
        $this->assertNull($receiptTicket->traveler_notification_failed_at);
        $this->assertNull($alertTicket->support_notification_failed_at);
        $this->assertSame('open', $receiptTicket->status);
        $this->assertSame('open', $alertTicket->status);
        $log->shouldNotHaveReceived('error');
    }

    public function test_jobs_use_distinct_per_audience_overlap_locks_with_safe_timing(): void
    {
        $receiptJob = new SendSupportRequestReceipt(42);
        $alertJob = new SendSupportRequestAlert(42);

        $receiptMiddleware = $receiptJob->middleware();
        $alertMiddleware = $alertJob->middleware();

        $this->assertCount(1, $receiptMiddleware);
        $this->assertCount(1, $alertMiddleware);
        $this->assertInstanceOf(WithoutOverlapping::class, $receiptMiddleware[0]);
        $this->assertInstanceOf(WithoutOverlapping::class, $alertMiddleware[0]);
        $this->assertSame(
            'laravel-queue-overlap:support-request:42:receipt',
            $receiptMiddleware[0]->getLockKey($receiptJob),
        );
        $this->assertSame(
            'laravel-queue-overlap:support-request:42:alert',
            $alertMiddleware[0]->getLockKey($alertJob),
        );
        $this->assertSame(10, $receiptMiddleware[0]->releaseAfter);
        $this->assertSame(10, $alertMiddleware[0]->releaseAfter);
        $this->assertSame(60, $receiptMiddleware[0]->expiresAfter);
        $this->assertSame(60, $alertMiddleware[0]->expiresAfter);
        $this->assertTrue($receiptMiddleware[0]->shareKey);
        $this->assertTrue($alertMiddleware[0]->shareKey);
    }

    private function activityInDubai(): Activity
    {
        /** @var City $city */
        $city = City::factory()->create(['slug' => 'dubai']);
        /** @var Activity $activity */
        $activity = Activity::factory()->create([
            'name' => 'Dubai Desert Safari',
            'slug' => 'dubai-desert-safari',
        ]);
        ActivityLocation::query()->create([
            'activity_id' => $activity->id,
            'city_id' => $city->id,
        ]);

        return $activity;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(Activity $activity, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Asha Traveler',
            'email' => 'asha@example.com',
            'topic' => 'before_booking',
            'message' => 'Can you help me understand the booking details?',
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'item_title' => $activity->name,
            'city_slug' => 'dubai',
            'item_slug' => $activity->slug,
            'page_url' => "http://localhost:3000/cities/dubai/activities/{$activity->slug}",
            'client_request_id' => (string) Str::uuid(),
            'website' => null,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function ticket(array $overrides = []): SupportRequest
    {
        return SupportRequest::query()->create(array_merge([
            'client_request_id' => (string) Str::uuid(),
            'reference' => 'WLP-260731-ABC123',
            'user_id' => null,
            'name' => 'Asha Traveler',
            'email' => 'asha@example.com',
            'topic' => 'before_booking',
            'message' => 'Please explain what is included before I make this booking.',
            'item_type' => 'activity',
            'item_id' => 42,
            'item_title' => 'Dubai Desert Safari',
            'city_slug' => 'dubai',
            'item_slug' => 'dubai-desert-safari',
            'page_url' => 'http://localhost:3000/cities/dubai/activities/dubai-desert-safari',
            'status' => 'open',
        ], $overrides));
    }
}
