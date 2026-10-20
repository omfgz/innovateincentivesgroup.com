<?php
/**
 * Partnership enquiries (POST from /partnerships/).
 *
 * Replaces the WordPress Elementor "Partnerships Form" popup: saves the
 * enquiry to private/partner-leads/leads.csv and emails it to the team with
 * Reply-To set to the partner, the same as the Elementor form did.
 */
declare(strict_types=1);
require __DIR__ . '/_lib.php';

const PARTNER_CATEGORIES = ['Accounting / CPA Firm', 'Tech Incubator / Accelerator', 'Law Firm / Legal Advisor', 'Financial Consultant', 'Other'];

start_request();

$lead = [
    'name'     => field('name'),
    'email'    => field('email'),
    'phone'    => field('phone', 30),
    'company'  => field('company'),
    'category' => field('category', 40),
    'message'  => text_field('message'),
];
require_fields($lead, ['email', 'phone', 'company', 'category']);
if (!in_array($lead['category'], PARTNER_CATEGORIES, true)) {
    respond(422, ['ok' => false, 'error' => 'Invalid partner category']);
}

$dir = data_dir('partner-leads');
rate_limit($dir);
save_lead($dir, $lead);

$L = array_map('e', $lead);
$message = $L['message'] !== '' ? nl2br($L['message']) : '<em>(none)</em>';

$html = "<h2>New Partnership Request</h2>
    <p><strong>Name:</strong> {$L['name']}</p>
    <p><strong>Email:</strong> {$L['email']}</p>
    <p><strong>Phone:</strong> {$L['phone']}</p>
    <p><strong>Company:</strong> {$L['company']}</p>
    <p><strong>Partner Category:</strong> {$L['category']}</p>
    <p><strong>Message:</strong><br>{$message}</p>
    <p style=\"color:#888;font-size:12px\">Sent from " . SITE_URL . "/partnerships/</p>";

$subject = 'Partnership Request - ' . ($lead['name'] !== '' ? $lead['name'] : $lead['company']);
if (!send_mail(ADMIN_EMAIL, $subject, $html, $lead['email'])) {
    error_log('partner-lead: mail() failed');
}

respond(200, ['ok' => true]);
