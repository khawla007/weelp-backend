<x-mail::message>
# New Order Received

Hello Admin,

A new order has been placed.

**Order ID:** {{ $order->id }}
**Customer:** {{ $order->user->name }} ({{ $order->user->email }})
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
- **Total: {{ $currencySymbol }}{{ number_format($order->payment->amount, 2) }}**

**Status:** {{ ucfirst($order->status) }}

<x-mail::button :url="url('/admin/orders/'.$order->id)">
View Order in Dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
