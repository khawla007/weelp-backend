<x-emails.layouts.weelp>
    <x-slot:header>
        <x-emails.components.header />
    </x-slot>

    <h1 style="margin: 0 0 16px 0; color: #273F4E; font-size: 24px; font-weight: 600;">Order Confirmation</h1>

    <p style="margin: 0 0 16px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Hi {{ $order->user->name }},
    </p>

    <p style="margin: 0 0 24px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        Thank you for your order! We're processing it now.
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f2f7f5; border-radius: 8px; margin: 24px 0;">
        <tr>
            <td style="padding: 20px;">
                <p style="margin: 0 0 8px 0; color: #273F4E; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Order Details</p>
                <p style="margin: 4px 0; color: #435A67; font-size: 14px;"><strong>Order ID:</strong> #{{ $order->id }}</p>
                <p style="margin: 4px 0; color: #435A67; font-size: 14px;"><strong>Item:</strong> {{ $itemName }}</p>

                @if(count($addons) > 0)
                <p style="margin: 12px 0 4px 0; color: #435A67; font-size: 14px;"><strong>Add-ons:</strong></p>
                @foreach($addons as $addon)
                <p style="margin: 2px 0 2px 16px; color: #435A67; font-size: 14px;">• {{ $addon['addon_name'] }} — {{ $currencySymbol }}{{ number_format($addon['price'], 2) }}</p>
                @endforeach
                @endif

                <p style="margin: 16px 0 4px 0; color: #435A67; font-size: 14px;"><strong>Price Breakdown:</strong></p>
                <p style="margin: 2px 0 2px 16px; color: #435A67; font-size: 14px;">Item: {{ $currencySymbol }}{{ number_format($baseAmount, 2) }}</p>
                @if(count($addons) > 0)
                <p style="margin: 2px 0 2px 16px; color: #435A67; font-size: 14px;">Add-ons: {{ $currencySymbol }}{{ number_format($addonsAmount, 2) }}</p>
                @endif
                <p style="margin: 12px 0 0 16px; color: #568f7c; font-size: 16px; font-weight: 600;"><strong>Total: {{ $currencySymbol }}{{ number_format($order->payment->amount, 2) }}</strong></p>
            </td>
        </tr>
    </table>

    <p style="margin: 24px 0 8px 0; color: #435A67; font-size: 15px; line-height: 1.6;">
        <strong>Status:</strong> <span style="color: #568f7c; background-color: #f2f7f5; padding: 4px 12px; border-radius: 4px; font-size: 13px;">Processing</span>
    </p>

    <p style="margin: 8px 0 24px 0; color: #435A67; font-size: 14px;">
        We'll notify you once your order is completed.
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 24px 0 0 0; text-align: center;">
                <a href="{{ url('/orders/'.$order->id) }}" class="email-button" target="_blank">View Your Order</a>
            </td>
        </tr>
    </table>

    <p style="margin: 32px 0 0 0; color: #435A67; font-size: 14px;">
        Best regards,<br>
        <strong style="color: #568f7c;">The Weelp Team</strong>
    </p>
</x-emails.layouts.weelp>
