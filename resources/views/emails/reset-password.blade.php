<x-emails.layouts.weelp>
    <x-slot:header>
        <x-emails.components.header />
    </x-slot>

    <h1 style="margin: 0 0 16px 0; color: #273F4E; font-size: 24px; font-weight: 600;">Reset Your Password</h1>

    <p style="margin: 0 0 16px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Hello,
    </p>

    <p style="margin: 0 0 24px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        You are receiving this email because we received a password reset request for your Weelp account.
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 24px 0 0 0; text-align: center;">
                <a href="{{ config('app.frontend_url') }}/user/reset-password?token={{ $token }}" class="email-button" target="_blank">Reset Password</a>
            </td>
        </tr>
    </table>

    <p style="margin: 32px 0 16px 0; color: #999999; font-size: 13px; line-height: 1.6;">
        If you did not request a password reset, no further action is required.
    </p>

    <p style="margin: 0; color: #435A67; font-size: 14px;">
        Best regards,<br>
        <strong style="color: #568f7c;">The Weelp Team</strong>
    </p>
</x-emails.layouts.weelp>
