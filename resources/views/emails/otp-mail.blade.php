<x-mail::message>
# Hello{{ $name ? ', ' . $name : '' }}!

Your verification code is:

# <x-mail::button>{{ $otp }}</x-mail::button>

This code will expire in 10 minutes.

If you didn't request this, please ignore this email.

<x-mail::hr />

With regards,<br>
**Weelp Team**
</x-mail::message>
