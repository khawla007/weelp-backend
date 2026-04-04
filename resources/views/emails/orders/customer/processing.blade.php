<x-mail::message>
# Hi {{ $order->user->name }},

Thank you for your order!

**Order ID:** {{ $order->id }}
**Item:** {{ $itemName }}

@if(count($addons) > 0)
**Add-ons:**
@foreach($addons as $addon)
- {{ $addon['addon_name'] }} ({{ $currencySymbol }}{{ number_format($addon['price'], 2) }})
@endforeach
@endif

**Price Breakdown:**
- Item Price: {{ $currencySymbol }}{{ number_format($baseAmount, 2) }}
@if(count($addons) > 0)
- Add-ons: {{ $currencySymbol }}{{ number_format($addonsAmount, 2) }}
@endif
- **Total Paid: {{ $currencySymbol }}{{ number_format($order->payment->amount, 2) }}**

**Status:** Processing

We will notify you once your order is completed.

<x-mail::button :url="url('/orders/'.$order->id)">
View Your Order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
