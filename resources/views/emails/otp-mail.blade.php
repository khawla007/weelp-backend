<x-emails.layouts.weelp>
    <x-slot:header>
        <x-emails.components.header />
    </x-slot>

    <h1 style="margin: 0 0 16px 0; color: #273F4E; font-size: 24px; font-weight: 600;">Your Verification Code</h1>

    <p style="margin: 0 0 16px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Hello{{ $name ? ', ' . $name : '' }}!
    </p>

    <p style="margin: 0 0 24px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Use the verification code below to complete your request:
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 24px 0;">
        <tr>
            <td style="background-color: #f2f7f5; border: 2px dashed #568f7c; border-radius: 8px; padding: 24px; text-align: center;">
                <span style="color: #568f7c; font-size: 32px; font-weight: 700; letter-spacing: 4px;">{{ $otp }}</span>
            </td>
        </tr>
    </table>

    <p style="margin: 24px 0 8px 0; color: #999999; font-size: 13px; line-height: 1.6;">
        This code will expire in <strong>10 minutes</strong>.
    </p>

    <p style="margin: 8px 0 24px 0; color: #999999; font-size: 13px; line-height: 1.6;">
        If you didn't request this code, please ignore this email.
    </p>

    <p style="margin: 0; color: #435A67; font-size: 14px;">
        Best regards,<br>
        <strong style="color: #568f7c;">The Weelp Team</strong>
    </p>
</x-emails.layouts.weelp>
