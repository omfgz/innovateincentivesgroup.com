<?php
/**
 * SR&ED calculator lead capture (POST from /sred-calculator/).
 *
 * Replaces the WordPress "IIG SR&ED Calculator" WPCode snippet:
 *  1. saves the lead to private/sred-leads/leads.csv,
 *  2. emails the lead to the team,
 *  3. sends the visitor the thank-you auto-responder.
 * Mail goes through PHP mail() from noreply@, exactly as WordPress did.
 */
declare(strict_types=1);
require __DIR__ . '/_lib.php';

start_request();

$lead = [
    'name'         => field('name'),
    'company'      => field('company'),
    'industry'     => field('industry'),
    'phone'        => field('phone', 30),
    'email'        => field('email'),
    'salaries'     => field('salaries', 20),
    'contractors'  => field('contractors', 20),
    'materials'    => field('materials', 20),
    'capital'      => field('capital', 20),
    'employees'    => field('employees', 10),
    'sred_pct'     => field('sred_pct', 3),
    'ccpc'         => field('ccpc', 10),
    'province'     => field('prov', 20),
    'total_return' => field('total_return', 30),
];
require_fields($lead, ['name', 'company', 'industry', 'phone', 'email', 'sred_pct']);

$dir = data_dir('sred-leads');
rate_limit($dir);
save_lead($dir, $lead);

// ---------------------------------------------------------------- emails

$L = array_map('e', $lead);

$adminHtml = "<h2>New SR&amp;ED Lead Received</h2>
    <p><strong>Name:</strong> {$L['name']}</p>
    <p><strong>Company:</strong> {$L['company']} ({$L['industry']})</p>
    <p><strong>Email:</strong> {$L['email']}</p>
    <p><strong>Phone:</strong> {$L['phone']}</p>
    <p><strong>% of time spent on SR&amp;ED work:</strong> {$L['sred_pct']}%</p>
    <h3>Calculator Data:</h3>
    <ul>
        <li><strong>Province:</strong> {$L['province']} ({$L['ccpc']})</li>
        <li><strong>Salaries:</strong> \${$L['salaries']}</li>
        <li><strong>Contractors:</strong> \${$L['contractors']}</li>
        <li><strong>Materials:</strong> \${$L['materials']}</li>
        <li><strong>Capital Equipment:</strong> \${$L['capital']}</li>
        <li><strong>R&amp;D Employees:</strong> {$L['employees']}</li>
        <li><strong>Calculated Return:</strong> {$L['total_return']}</li>
    </ul>";

$adminOk = send_mail(ADMIN_EMAIL, 'New SR&ED Calculator Lead - ' . $lead['company'], $adminHtml, $lead['email']);

$site = SITE_URL;
$thankYou = <<<HTML
<div style="background-color: #f0f4f8; padding: 50px 20px;">
  <div style="font-family: 'Manrope', Arial, sans-serif; color: #232D56; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden;">
    <div style="text-align: center; padding: 35px 20px; background-color: #232D56;">
      <img src="{$site}/assets/email/iig-logo-white.png" alt="Innovate Incentives Group" style="max-height: 55px; height: auto; display: block; margin: 0 auto;">
    </div>
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #1a2241; color: #ffffff; text-align: center; font-weight: bold; font-size: 13px;">
      <tr>
        <td style="padding: 16px 2px; width: 33%;"><a href="{$site}/services/government-grants/" style="color: #ffffff; text-decoration: none;">GRANTS</a></td>
        <td style="padding: 16px 2px; width: 34%; border-left: 1px solid #3A4676; border-right: 1px solid #3A4676;"><a href="{$site}/services/investment-tax-credits-ommitc/" style="color: #ffffff; text-decoration: none;">TAX CREDITS</a></td>
        <td style="padding: 16px 2px; width: 33%;"><a href="{$site}/services/government-loans-strategic-financing/" style="color: #ffffff; text-decoration: none;">GOVERNMENT LOANS</a></td>
      </tr>
    </table>
    <div style="background-color: #f8fbff; padding: 45px 20px; text-align: center; border-bottom: 4px solid #F3BE1C;">
      <h1 style="color: #232D56; margin: 0; font-size: 32px; font-weight: 900; text-transform: uppercase;">Thank you</h1>
      <h2 style="color: #F3BE1C; margin: 5px 0 0 0; font-size: 18px; font-weight: 800; letter-spacing: 2px;">FOR YOUR INQUIRY!</h2>
    </div>
    <div style="padding: 40px 40px 50px 40px; background-color: #ffffff;">
      <h2 style="color: #232D56; text-align: center; font-size: 24px; margin-top: 0;">Good news, {$L['name']}!</h2>
      <p style="font-size: 16px; text-align: center; line-height: 1.6; margin-bottom: 30px; color: #4a5568;">
        We've received your inquiry and one of our experts will reach out to you shortly!<br><br>We're excited to learn more about <strong>{$L['company']}</strong> and help optimize your innovation funding strategy.
      </p>
      <hr style="border: 0; border-top: 1px dashed #b6d0ef; margin: 35px 0;">
      <h3 style="text-align: center; color: #232D56; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Ready to explore more?</h3>
      <div style="text-align: center; margin-top: 25px;">
        <a href="{$site}/services/" style="background-color: #F3BE1C; color: #232D56; padding: 16px 35px; text-decoration: none; border-radius: 6px; font-weight: 800; font-size: 15px; display: inline-block;">Discover Our Services</a>
      </div>
    </div>
  </div>
</div>
HTML;

$userOk = send_mail($lead['email'], 'Thank you for your SR&ED Inquiry!', $thankYou, ADMIN_EMAIL);

if (!$adminOk || !$userOk) {
    error_log('sred-lead: mail() failed (admin=' . (int)$adminOk . ', user=' . (int)$userOk . ')');
}

respond(200, ['ok' => true]);
