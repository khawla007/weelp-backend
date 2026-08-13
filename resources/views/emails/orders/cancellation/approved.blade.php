<x-mail::message>
# Your cancellation request was approved

Hi {{ $customerName }},

Booking #{{ $cancellationRequest->order_id }} has been cancelled. The outcome is {{ str_replace('_', ' ', $cancellationRequest->refund_outcome) }}.

Final refund: {{ strtoupper($cancellationRequest->currency) }} {{ $cancellationRequest->final_refund_amount }}

Final deduction: {{ strtoupper($cancellationRequest->currency) }} {{ $cancellationRequest->final_deduction_amount }}

@if($cancellationRequest->decision_explanation)
Decision note: {{ $decisionExplanation }}
@endif

<x-mail::button :url="$actionUrl">
View booking
</x-mail::button>

Thanks,<br>
The Weelp Team
</x-mail::message>
