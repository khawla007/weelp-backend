<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $page) {
            Page::firstOrCreate(
                ['slug' => $page['slug']],
                array_merge($page, [
                    'status' => Page::STATUS_PUBLISHED,
                    'published_at' => now(),
                    'schema_type' => 'WebPage',
                    'schema_data' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebPage',
                        'name' => $page['title'],
                    ],
                ])
            );
        }
    }

    private function pages(): array
    {
        return [
            [
                'title' => 'Cancellation Policy',
                'slug' => 'cancellation',
                'excerpt' => 'Last updated: April 4, 2026',
                'meta_title' => 'Cancellation Policy - Weelp',
                'meta_description' => "Understand Weelp's cancellation and refund policies, including eligibility, timelines, and how to request a cancellation for your booking.",
                'content' => $this->document([
                    $this->heading('Overview'),
                    $this->paragraph('Plans can change. This policy outlines the cancellation terms, refund eligibility, timelines, and process for bookings made through the Weelp platform.'),
                    $this->heading('Customer-Initiated Cancellations'),
                    $this->paragraph('If you need to cancel a booking, the refund amount depends on how far in advance you cancel relative to the activity date. The following tiered refund schedule applies:'),
                    $this->table(['Cancellation Timing', 'Refund Amount'], [
                        ['7 or more days before the activity date', 'Full refund minus processing fee (2.9% + $0.30)'],
                        ['3 to 6 days before the activity date', '50% refund'],
                        ['Less than 3 days before the activity date', 'No refund'],
                        ['No-show', 'No refund'],
                    ]),
                    $this->paragraph('Some activities may have stricter cancellation policies set by the individual creator or vendor. These policies are displayed on the activity listing page and in your booking confirmation.'),
                    $this->heading('Creator/Vendor-Initiated Cancellations'),
                    $this->paragraph('If a creator or vendor cancels your booking, you are entitled to a full refund or the option to rebook to an alternative date or experience at no additional cost. We will notify you as soon as possible and assist you in finding suitable alternatives.'),
                    $this->heading('Force Majeure'),
                    $this->paragraph("Cancellations resulting from events beyond reasonable control are covered under our force majeure policy. This includes natural disasters, severe weather conditions, government restrictions, pandemics, civil unrest, and transport strikes. In such cases, you will receive a full refund or platform credit valid for 12 months from the date of issue. We understand these situations are stressful and we're committed to making the process as smooth as possible."),
                    $this->heading('Modification Policy'),
                    $this->paragraph('Modifications to date, time, or number of participants are subject to availability. All modification requests must be submitted at least 48 hours before the scheduled activity.'),
                    $this->bulletList([
                        'Date or time changes are free of charge if the new slot is available',
                        'Adding participants requires an additional payment at the current rate',
                        'Reducing the number of participants is treated as a partial cancellation and is subject to the refund schedule above',
                        'If the price difference requires a full cancel and rebook, the standard cancellation policy applies',
                    ]),
                    $this->heading('Refund Processing'),
                    $this->paragraph('Approved refunds are processed within 5 to 10 business days and returned to the original payment method used at the time of booking. The exact timing may vary depending on your bank or card issuer. You will receive an email confirmation once the refund has been initiated from our end.'),
                    $this->heading('Non-Refundable Items'),
                    $this->paragraph('The following items are non-refundable under all circumstances:'),
                    $this->bulletList([
                        'Payment processing fees',
                        'Travel insurance purchased through the Platform',
                        'Bookings explicitly marked as non-refundable at the time of purchase',
                        'Special, promotional, or discounted bookings where non-refundable terms were stated',
                        'Gift card or voucher purchases',
                    ]),
                    $this->heading('How to Request a Cancellation'),
                    $this->paragraph('To cancel a booking, log in to your account dashboard and use the Cancel Booking option on your booking details page, or email support@weelp.com with your cancellation request. Please include your booking reference number.'),
                    $this->heading('Contact Us'),
                    $this->paragraph('If you have any questions about this Cancellation Policy or need assistance with a booking, please reach out to us at support@weelp.com. We are here to help.'),
                ]),
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms',
                'excerpt' => 'Last updated: April 4, 2026',
                'meta_title' => 'Terms of Service - Weelp',
                'meta_description' => 'Read the Terms of Service for using the Weelp travel booking platform, including rules for bookings, payments, user responsibilities, and more.',
                'content' => $this->document([
                    $this->heading('Acceptance of Terms'),
                    $this->paragraph('By accessing or using Weelp ("Platform"), you agree to be bound by these Terms of Service. These Terms apply to all visitors, users, creators, and vendors who access or use the Platform. If you do not agree to these Terms, please do not use the Platform.'),
                    $this->heading('Definitions'),
                    $this->bulletList([
                        '"Platform" refers to the Weelp website, mobile applications, and all associated services',
                        '"User" refers to any individual who browses or makes bookings on the Platform',
                        '"Creator" refers to individuals or businesses that list activities and experiences on the Platform',
                        '"Vendor" refers to service providers including transfers, accommodation, and local operators',
                        '"Booking" refers to any reservation or purchase made through the Platform',
                        '"Content" refers to text, images, reviews, itineraries, and any other material submitted to the Platform',
                    ]),
                    $this->heading('Account Registration'),
                    $this->paragraph('To access certain features of the Platform, you must register for an account. You agree to provide accurate, current, and complete information during registration and to keep your account information up to date. You are responsible for maintaining the confidentiality of your account credentials and for all activity that occurs under your account. You must be at least 18 years of age to create an account and use the Platform.'),
                    $this->heading('Bookings and Payments'),
                    $this->paragraph('All prices displayed on the Platform are in the indicated currency. Applicable taxes are included in the displayed price unless otherwise stated. Payments are processed securely via Stripe.'),
                    $this->bulletList([
                        'A booking is confirmed only after successful payment and receipt of a confirmation email',
                        'Weelp acts as a marketplace facilitator and is not the direct provider of travel services',
                        'Pricing for activities and services is set by individual creators and vendors',
                        'Currency conversion, where applicable, is handled by your payment provider',
                    ]),
                    $this->heading('User Responsibilities'),
                    $this->bulletList([
                        'Provide accurate and complete information when making bookings',
                        'Ensure you hold valid travel documents required for your destination',
                        'Arrive on time for all booked activities and services',
                        'Comply with all applicable local laws and regulations',
                        'Obtain adequate travel insurance for your trip',
                        'Refrain from using the Platform for any unlawful or prohibited purpose',
                    ]),
                    $this->heading('Creator and Vendor Terms'),
                    $this->paragraph('Creators and vendors are solely responsible for the accuracy of their listings, including descriptions, pricing, availability, and photos. All creators and vendors must maintain valid licenses, insurance, and safety standards required by applicable laws and regulations. Weelp reserves the right to remove any listing that violates our guidelines or has received consistently poor reviews from users.'),
                    $this->heading('Cancellations and Refunds'),
                    $this->paragraph('Cancellation and refund eligibility is governed by our Cancellation Policy. By completing a booking on the Platform, you agree to the terms set out in that policy.'),
                    $this->heading('Intellectual Property'),
                    $this->paragraph('All content on the Platform created by Weelp, including but not limited to text, graphics, logos, and software, is the property of Weelp and is protected by applicable copyright and trademark laws. For content submitted by users, you retain ownership of your content but grant Weelp a non-exclusive, worldwide, royalty-free license to use, display, and distribute that content in connection with operating and improving the Platform.'),
                    $this->heading('Limitation of Liability'),
                    $this->paragraph("Weelp operates as a marketplace platform and is not the direct provider of any travel services, activities, or accommodations. Weelp is not liable for the actions, omissions, or negligence of creators or vendors. To the maximum extent permitted by applicable law, Weelp's total liability to you in connection with any claim arising from your use of the Platform shall not exceed the amount you paid for the specific booking giving rise to that claim."),
                    $this->heading('Dispute Resolution'),
                    $this->paragraph('If you have a dispute related to a booking or service, please contact our support team first. We will make reasonable efforts to resolve your concern within 14 business days. If a resolution cannot be reached through our support process, disputes shall be governed by applicable laws, and both parties agree to attempt mediation in good faith before initiating any formal legal action.'),
                    $this->heading('Modifications to Terms'),
                    $this->paragraph('Weelp reserves the right to modify these Terms of Service at any time. We will notify you of changes by updating this page with a revised Last updated date. Your continued use of the Platform following the posting of any changes constitutes your acceptance of the revised Terms.'),
                    $this->heading('Contact Information'),
                    $this->paragraph('If you have any questions about these Terms of Service, please contact our legal team at legal@weelp.com.'),
                ]),
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy',
                'excerpt' => 'Last updated: April 4, 2026',
                'meta_title' => 'Privacy Policy - Weelp',
                'meta_description' => 'Learn how Weelp collects, uses, and protects your personal information when you use our travel booking platform.',
                'content' => $this->document([
                    $this->heading('Introduction'),
                    $this->paragraph('Weelp operates a travel booking platform that connects travellers with experiences, activities, and holiday packages around the world. We are committed to protecting your privacy and handling your personal information with transparency and care.'),
                    $this->paragraph('By accessing or using our platform, you agree to the collection and use of information in accordance with this Privacy Policy. If you do not agree with any part of this policy, please do not use our services.'),
                    $this->heading('Information We Collect'),
                    $this->paragraph('We collect the following categories of information when you use Weelp:'),
                    $this->bulletList([
                        'Personal information: name, email address, phone number, date of birth, and billing address',
                        'Payment information: processed securely via Stripe — we do not store raw card details',
                        'Booking details: travel dates, destinations, activity selections, and passenger information',
                        'Account data: username, password (hashed), preferences, and saved itineraries',
                        'Device and usage data: IP address, browser type, operating system, and pages visited',
                        'Cookies and tracking data: session identifiers, analytics events, and referral sources',
                    ]),
                    $this->heading('How We Use Your Information'),
                    $this->paragraph('The information we collect is used for processing bookings and payments, providing customer support, sending transactional emails, improving and personalising the platform, detecting fraud, and complying with legal obligations.'),
                    $this->heading('Information Sharing'),
                    $this->paragraph('We do not sell, rent, or trade your personal data to third parties. We may share your information with travel partners, Stripe, analytics providers, legal authorities when required, or as part of a business transfer.'),
                    $this->heading('Data Security'),
                    $this->paragraph('We implement industry-standard security measures to protect your personal information, including SSL/TLS encryption, encrypted storage for sensitive data, PCI-compliant payment partners, and role-based access controls.'),
                    $this->paragraph('No method of transmission over the internet or electronic storage is 100% secure. In the event of a data breach, we will act promptly to assess and address the impact in accordance with applicable laws.'),
                    $this->heading('Your Rights'),
                    $this->paragraph('Depending on your location, you may have rights to access, correct, delete, port, or restrict use of your personal data, and to opt out of marketing or withdraw consent where processing is based on consent. To exercise these rights, contact privacy@weelp.com.'),
                    $this->heading('Cookies'),
                    $this->paragraph('We use essential, analytics, and marketing cookies. Essential cookies are required for authentication and platform security. Analytics cookies help us understand usage, and marketing cookies are enabled only with your consent.'),
                    $this->heading("Children's Privacy"),
                    $this->paragraph('Weelp is not intended for use by individuals under the age of 16. We do not knowingly collect personal information from children. If we become aware that a child under 16 has provided personal data, we will take steps to delete it.'),
                    $this->heading('Changes to This Policy'),
                    $this->paragraph('We may update this Privacy Policy from time to time to reflect changes in our practices, technology, or legal requirements. We encourage you to review this page periodically.'),
                    $this->heading('Contact Us'),
                    $this->paragraph('If you have any questions, concerns, or requests regarding this Privacy Policy or the way we handle your personal data, please reach out to us at privacy@weelp.com.'),
                ]),
            ],
        ];
    }

    private function document(array $content): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => $content,
        ], JSON_THROW_ON_ERROR);
    }

    private function heading(string $text): array
    {
        return [
            'type' => 'heading',
            'attrs' => ['level' => 2],
            'content' => [$this->text($text)],
        ];
    }

    private function paragraph(string $text): array
    {
        return [
            'type' => 'paragraph',
            'content' => [$this->text($text)],
        ];
    }

    private function bulletList(array $items): array
    {
        return [
            'type' => 'bulletList',
            'content' => array_map(fn (string $item): array => [
                'type' => 'listItem',
                'content' => [$this->paragraph($item)],
            ], $items),
        ];
    }

    private function table(array $headers, array $rows): array
    {
        return [
            'type' => 'table',
            'content' => [
                [
                    'type' => 'tableRow',
                    'content' => array_map(fn (string $header): array => $this->tableCell('tableHeader', $header), $headers),
                ],
                ...array_map(fn (array $row): array => [
                    'type' => 'tableRow',
                    'content' => array_map(fn (string $cell): array => $this->tableCell('tableCell', $cell), $row),
                ], $rows),
            ],
        ];
    }

    private function tableCell(string $type, string $text): array
    {
        return [
            'type' => $type,
            'content' => [$this->paragraph($text)],
        ];
    }

    private function text(string $text): array
    {
        return [
            'type' => 'text',
            'text' => $text,
        ];
    }
}
