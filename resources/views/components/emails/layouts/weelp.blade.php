<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? 'Email from Weelp' }}</title>
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #F8F9F9; }
        .email-container { max-width: 600px; margin: 0 auto; }
        .email-button { display: inline-block; padding: 12px 32px; background-color: #568f7c; color: #ffffff !important; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; }
        .email-button:hover { background-color: #457a64; }
        a { color: #568f7c; text-decoration: none; }
        a:hover { text-decoration: underline; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .email-button { display: block !important; width: 100% !important; box-sizing: border-box; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #F8F9F9;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8F9F9;">
        <tr>
            <td style="padding: 40px 20px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">

                    <!-- Header -->
                    {{ $header ?? '' }}

                    <!-- Content -->
                    <tr>
                        <td style="padding: 32px 40px 40px 40px; background-color: #ffffff;">
                            {{ $slot }}
                        </td>
                    </tr>

                    <!-- Footer -->
                    @include('emails.components.footer')

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
