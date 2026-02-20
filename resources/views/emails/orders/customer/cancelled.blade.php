<x-mail::message>
# Hi {{ $order->user->name }},

Your order **#{{ $order->id }}** has been **cancelled**.

**Total Paid:** ₹{{ $order->payment->amount }}  

If you did not cancel this order, please contact us immediately.

<x-mail::button :url="url('/orders/'.$order->id)">
View Order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
