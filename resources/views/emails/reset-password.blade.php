<x-mail::message>
# Hello,

You are receiving this email because we received a password reset request for your account.

Click the link below to reset your password:

<x-mail::button :url="'https://weelp-frontend.vercel.app/user/reset-password?token=' . $token">
Reset Password
</x-mail::button>

If you did not request a password reset, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>