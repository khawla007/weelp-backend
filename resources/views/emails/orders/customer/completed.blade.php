<x-mail::message>
# Hi {{ $order->user->name }},

Great news! Your order **#{{ $order->id }}** has been **completed**.

**Total Paid:** ₹{{ $order->payment->amount }}  

We hope you enjoy your purchase.

<x-mail::button :url="url('/orders/'.$order->id)">
View Order Details
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
