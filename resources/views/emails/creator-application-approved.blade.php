<x-emails.layouts.weelp>
    <x-slot:header>
        <x-emails.components.header />
    </x-slot>

    <h1 style="margin: 0 0 16px 0; color: #273F4E; font-size: 24px; font-weight: 600;">Congratulations! 🎉</h1>

    <p style="margin: 0 0 16px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Hello {{ $application->name }},
    </p>

    <p style="margin: 0 0 24px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Your creator application on Weelp has been <strong style="color: #568f7c;">approved</strong>!
    </p>

    <p style="margin: 0 0 24px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        You can now create itineraries and share your travel experiences with the community.
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f2f7f5; border-radius: 8px; margin: 24px 0;">
        <tr>
            <td style="padding: 20px;">
                <p style="margin: 0 0 8px 0; color: #273F4E; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">How to create your first itinerary:</p>
                <p style="margin: 8px 0 4px 0; color: #435A67; font-size: 14px;">1. Go to the <strong>Explore</strong> page and open any <strong>single itinerary page</strong> that inspires you</p>
                <p style="margin: 4px 0 4px 0; color: #435A67; font-size: 14px;">2. <strong>Add or edit the schedule</strong> — customize it with your own activities, timings, and travel tips</p>
                <p style="margin: 4px 0 4px 0; color: #435A67; font-size: 14px;">3. <strong>Submit for approval</strong> — our team will review and publish it on the Explore page</p>
                <p style="margin: 4px 0 0 0; color: #435A67; font-size: 14px;">4. Track all your created itineraries from the <strong>My Itineraries</strong> section in your dashboard</p>
            </td>
        </tr>
    </table>

    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 24px 0 0 0; text-align: center;">
                <a href="{{ config('app.frontend_url') }}/explore" class="email-button" target="_blank">Go to Explore Page</a>
            </td>
        </tr>
    </table>

    <p style="margin: 24px 0 0 0; color: #435A67; font-size: 14px;">
        We're excited to have you on board!
    </p>

    <p style="margin: 8px 0 0 0; color: #435A67; font-size: 14px;">
        Best regards,<br>
        <strong style="color: #568f7c;">The Weelp Team</strong>
    </p>
</x-emails.layouts.weelp>
