<x-mail::message>
# We received your cancellation request

Hi {{ $customerName }},

Your request for booking #{{ $cancellationRequest->order_id }} is waiting for an administrator to review it. Your estimated refund under policy {{ $cancellationRequest->policy_version }} is {{ strtoupper($cancellationRequest->currency) }} {{ $cancellationRequest->suggested_refund_amount }}.

This estimate is not a final refund decision. We will email you after the review is complete.

<x-mail::button :url="$actionUrl">
View booking
</x-mail::button>

Thanks,<br>
The Weelp Team
</x-mail::message>
