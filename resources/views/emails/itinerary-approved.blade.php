<x-emails.layouts.weelp>
    <x-slot:header>
        <x-emails.components.header />
    </x-slot>

    <h1 style="margin: 0 0 16px 0; color: #273F4E; font-size: 24px; font-weight: 600;">Congratulations!</h1>

    <p style="margin: 0 0 16px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Hello {{ $creator->name }},
    </p>

    <p style="margin: 0 0 24px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Great news! Your itinerary <strong style="color: #568f7c;">"{{ $itinerary->name }}"</strong> has been <strong style="color: #568f7c;">approved</strong> and is now visible on the Explore page.
    </p>

    <p style="margin: 0 0 24px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Travelers can now discover and book your itinerary. You can track all your created itineraries from the My Itineraries section in your dashboard.
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 24px 0 0 0; text-align: center;">
                <a href="{{ config('app.frontend_url') }}/dashboard/customer/my-itineraries" class="email-button" target="_blank">View My Itineraries</a>
            </td>
        </tr>
    </table>

    <p style="margin: 32px 0 0 0; color: #435A67; font-size: 14px;">
        Best regards,<br>
        <strong style="color: #568f7c;">The Weelp Team</strong>
    </p>
</x-emails.layouts.weelp>
