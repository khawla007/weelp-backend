<x-mail::message>
# Hi {{ $order->user->name }},

Unfortunately, your payment for order **#{{ $order->id }}** has **failed**.

Please try again or contact support if the issue persists.

<x-mail::button :url="url('/checkout')">
Retry Payment
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
