<x-mail::message>
# Cancellation refund needs attention

Cancellation request #{{ $cancellationRequest->id }} for booking #{{ $cancellationRequest->order_id }} could not be confirmed.

- Customer: {{ $customerName }} ({{ $customerEmail }})
- Item: {{ $itemName }}
- Travel date: {{ $travelDate }}
- Preferred time: {{ $preferredTime }}
- Failure code: {{ $failureCode }}
Summary: {{ $failureSummary }}

Review or retry this request in the admin dashboard. Provider credentials and diagnostic details are intentionally omitted.

<x-mail::button :url="$actionUrl">
Review cancellation request
</x-mail::button>
</x-mail::message>
