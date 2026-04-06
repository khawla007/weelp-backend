<x-emails.layouts.weelp>
    <x-slot:header>
        <x-emails.components.header />
    </x-slot>

    <h1 style="margin: 0 0 16px 0; color: #273F4E; font-size: 24px; font-weight: 600;">New Creator Application</h1>

    <p style="margin: 0 0 16px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Hello Admin,
    </p>

    <p style="margin: 0 0 24px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        A new creator application has been submitted on Weelp.
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f2f7f5; border-radius: 8px; margin: 24px 0;">
        <tr>
            <td style="padding: 20px;">
                <p style="margin: 0 0 8px 0; color: #273F4E; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Applicant Details</p>
                <p style="margin: 4px 0; color: #435A67; font-size: 14px;"><strong>Name:</strong> {{ $application->name }}</p>
                <p style="margin: 4px 0; color: #435A67; font-size: 14px;"><strong>Email:</strong> {{ $application->email }}</p>
                <p style="margin: 4px 0; color: #435A67; font-size: 14px;"><strong>Gender:</strong> {{ ucfirst($application->gender) }}</p>
                <p style="margin: 4px 0; color: #435A67; font-size: 14px;"><strong>Phone:</strong> {{ $application->phone }}</p>
                <p style="margin: 4px 0; color: #435A67; font-size: 14px;"><strong>Instagram:</strong> {{ $application->instagram }}</p>
                @if($application->youtube)
                <p style="margin: 4px 0; color: #435A67; font-size: 14px;"><strong>YouTube:</strong> {{ $application->youtube }}</p>
                @endif
                @if($application->facebook)
                <p style="margin: 4px 0; color: #435A67; font-size: 14px;"><strong>Facebook:</strong> {{ $application->facebook }}</p>
                @endif
            </td>
        </tr>
    </table>

    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 24px 0 0 0; text-align: center;">
                <a href="{{ config('app.frontend_url') }}/dashboard/admin/creator-applications" class="email-button" target="_blank">Review Application</a>
            </td>
        </tr>
    </table>

    <p style="margin: 32px 0 0 0; color: #435A67; font-size: 14px;">
        Best regards,<br>
        <strong style="color: #568f7c;">The Weelp Team</strong>
    </p>
</x-emails.layouts.weelp>
