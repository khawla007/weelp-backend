<x-emails.layouts.weelp>
    <x-slot:header>
        <x-emails.components.header />
    </x-slot>

    <h1 style="margin: 0 0 16px 0; color: #273F4E; font-size: 24px; font-weight: 600;">Application Update</h1>

    <p style="margin: 0 0 16px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Hello {{ $application->name }},
    </p>

    <p style="margin: 0 0 24px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Thank you for your interest in becoming a creator on Weelp.
    </p>

    <p style="margin: 0 0 24px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        After reviewing your application, we were unable to approve it at this time.
    </p>

    @if($application->admin_notes)
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f2f7f5; border-radius: 8px; margin: 24px 0;">
        <tr>
            <td style="padding: 20px;">
                <p style="margin: 0 0 8px 0; color: #273F4E; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Reason:</p>
                <p style="margin: 0; color: #435A67; font-size: 14px; line-height: 1.6;">{{ $application->admin_notes }}</p>
            </td>
        </tr>
    </table>
    @endif

    <p style="margin: 24px 0 8px 0; color: #435A67; font-size: 14px;">
        You are welcome to re-apply in the future. If you have any questions, feel free to reach out to our support team.
    </p>

    <p style="margin: 0; color: #435A67; font-size: 14px;">
        Best regards,<br>
        <strong style="color: #568f7c;">The Weelp Team</strong>
    </p>
</x-emails.layouts.weelp>
