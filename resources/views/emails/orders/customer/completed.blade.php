<x-emails.layouts.weelp>
    <x-slot:header>
        <x-emails.components.header />
    </x-slot>

    <h1 style="margin: 0 0 16px 0; color: #273F4E; font-size: 24px; font-weight: 600;">Order Completed!</h1>

    <p style="margin: 0 0 16px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Hi {{ $order->user->name }},
    </p>

    <p style="margin: 0 0 24px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Great news! Your order <strong>#{{ $order->id }}</strong> has been completed successfully.
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f2f7f5; border-radius: 8px; margin: 24px 0;">
        <tr>
            <td style="padding: 20px;">
                <p style="margin: 0; color: #273F4E; font-size: 14px;"><strong>Total Paid:</strong> <span style="color: #568f7c; font-size: 18px; font-weight: 600;">₹{{ $order->payment->amount }}</span></p>
            </td>
        </tr>
    </table>

    <p style="margin: 24px 0 8px 0; color: #435A67; font-size: 14px;">
        We hope you enjoy your purchase!
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 24px 0 0 0; text-align: center;">
                <a href="{{ url('/orders/'.$order->id) }}" class="email-button" target="_blank">View Order Details</a>
            </td>
        </tr>
    </table>

    <p style="margin: 32px 0 0 0; color: #435A67; font-size: 14px;">
        Best regards,<br>
        <strong style="color: #568f7c;">The Weelp Team</strong>
    </p>
</x-emails.layouts.weelp>
