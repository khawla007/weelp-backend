<x-mail::message>
# Hi {{ $order->user->name }},

Your order **#{{ $order->id }}** has been **refunded**.

**Refund Amount:** ₹{{ $order->payment->amount }}  

If you have any questions, please contact support.

<x-mail::button :url="url('/orders/'.$order->id)">
View Order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
