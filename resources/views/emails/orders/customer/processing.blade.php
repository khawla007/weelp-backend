<x-mail::message>
# Hi {{ $order->user->name }},

Thank you for your order!

**Order ID:** {{ $order->id }}  
**Total Paid:** ₹{{ $order->payment->amount }}  
**Status:** Processing

We will notify you once your order is completed.

<x-mail::button :url="url('/orders/'.$order->id)">
View Your Order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

