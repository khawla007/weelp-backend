<x-emails.layouts.weelp>
    <x-slot:header>
        <x-emails.components.header />
    </x-slot>

    <h1 style="margin: 0 0 16px 0; color: #273F4E; font-size: 24px; font-weight: 600;">New support request</h1>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 24px 0;">
        <tr>
            <td style="padding: 8px 0; color: #435A67; font-size: 14px;"><strong>Reference</strong></td>
            <td style="padding: 8px 0; color: #273F4E; font-size: 14px; text-align: right;">{{ $supportRequest->reference }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #435A67; font-size: 14px;"><strong>Traveler</strong></td>
            <td style="padding: 8px 0; color: #273F4E; font-size: 14px; text-align: right;">{{ $supportRequest->name }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #435A67; font-size: 14px;"><strong>Item</strong></td>
            <td style="padding: 8px 0; color: #273F4E; font-size: 14px; text-align: right;">{{ $supportRequest->item_title }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #435A67; font-size: 14px;"><strong>Topic</strong></td>
            <td style="padding: 8px 0; color: #273F4E; font-size: 14px; text-align: right;">{{ $topicLabel }}</td>
        </tr>
    </table>

    <p style="margin: 0 0 8px 0; color: #435A67; font-size: 14px;"><strong>Message</strong></p>
    <p style="margin: 0 0 24px 0; padding: 16px; background-color: #F8F9F9; color: #273F4E; font-size: 15px; line-height: 1.6; border-radius: 6px;">
        {{ $supportRequest->message }}
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 8px 0 0 0; text-align: center;">
                <a href="{{ $supportRequest->page_url }}" class="email-button" target="_blank">View public item</a>
            </td>
        </tr>
    </table>
</x-emails.layouts.weelp>
