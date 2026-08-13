<x-mail::message>
# Cancellation request awaiting review

Cancellation request #{{ $cancellationRequest->id }} for booking #{{ $cancellationRequest->order_id }} is ready for review.

- Customer: {{ $customerName }} ({{ $customerEmail }})
- Item: {{ $itemName }}
- Travel date: {{ $travelDate }}
- Preferred time: {{ $preferredTime }}
- Reason: {{ $reason }}
- Paid: {{ strtoupper($cancellationRequest->currency) }} {{ $cancellationRequest->paid_amount }}
Suggested refund: {{ strtoupper($cancellationRequest->currency) }} {{ $cancellationRequest->suggested_refund_amount }}

Review the request in the admin dashboard before changing the booking status.

<x-mail::button :url="$actionUrl">
Review cancellation request
</x-mail::button>
</x-mail::message>
