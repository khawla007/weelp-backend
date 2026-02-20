<x-mail::message>
# New Order Received 🎉

Hello Admin,

A new order has been placed.

**Order ID:** {{ $order->id }}  
**Customer:** {{ $order->user->name }} ({{ $order->user->email }})  
**Total:** ₹{{ $order->payment->amount }}  
**Status:** {{ ucfirst($order->status) }}

<x-mail::button :url="url('/admin/orders/'.$order->id)">
View Order in Dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>