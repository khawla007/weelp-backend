<x-mail::message>
# Update on your cancellation request

Hi {{ $customerName }},

Your cancellation request for booking #{{ $cancellationRequest->order_id }} was not approved.

Reason: {{ $decisionExplanation }}

Your booking remains active. Contact support if you need help.

<x-mail::button :url="$actionUrl">
View booking
</x-mail::button>

Thanks,<br>
The Weelp Team
</x-mail::message>
