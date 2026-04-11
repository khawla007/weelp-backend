<x-emails.layouts.weelp>
    <x-slot:header>
        <x-emails.components.header />
    </x-slot>

    <h1 style="margin: 0 0 16px 0; color: #273F4E; font-size: 24px; font-weight: 600;">Customized Itinerary Submitted for Approval</h1>

    <p style="margin: 0 0 16px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Hello {{ $creator->name }},
    </p>

    <p style="margin: 0 0 24px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Your customized itinerary <strong style="color: #273F4E;">"{{ $itinerary->name }}"</strong> has been successfully submitted for approval.
    </p>

    <p style="margin: 0 0 24px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Please wait while our team reviews your submission. You will receive an email notification once your itinerary has been approved or if any changes are requested.
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f2f7f5; border-radius: 8px; margin: 24px 0;">
        <tr>
            <td style="padding: 20px;">
                <p style="margin: 0 0 8px 0; color: #273F4E; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Itinerary Details</p>
                <p style="margin: 4px 0; color: #435A67; font-size: 14px;"><strong>Name:</strong> {{ $itinerary->name }}</p>
                <p style="margin: 4px 0; color: #435A67; font-size: 14px;"><strong>Status:</strong> Pending Approval</p>
                <p style="margin: 4px 0; color: #435A67; font-size: 14px;"><strong>Days:</strong> {{ $itinerary->schedules->count() }}</p>
            </td>
        </tr>
    </table>

    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 24px 0 0 0; text-align: center;">
                <a href="{{ config('app.frontend_url') }}/dashboard/creator" class="email-button" target="_blank">Go to Dashboard</a>
            </td>
        </tr>
    </table>

    <p style="margin: 32px 0 0 0; color: #435A67; font-size: 14px;">
        Best regards,<br>
        <strong style="color: #568f7c;">The Weelp Team</strong>
    </p>
</x-emails.layouts.weelp>
