<x-mail::message>
# Hello{{ $name ? ', ' . $name : '' }}!

Your verification code is:

# {{ $otp }}

This code will expire in 10 minutes.

If you didn't request this, please ignore this email.

Thanks,<br>
**Weelp Team**
</x-mail::message>
