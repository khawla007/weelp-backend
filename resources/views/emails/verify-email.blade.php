<x-mail::message>
# Hello {{ $user->name }},

Thank you for registering on {{ config('app.name') }}.

Click the button below to verify your email:

<x-mail::button :url="config('app.frontend_url') . '/user/email/verify?token=' . $token">
Verify Email
</x-mail::button>

If you did not register, no action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
