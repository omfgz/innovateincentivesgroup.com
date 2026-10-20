<?php
/**
 * Shared helpers for the site's form endpoints (sred-lead.php, partner-lead.php).
 * Not web-accessible (see api/.htaccess).
 *
 * Leads are stored in ~/www/innovateincentivesgroup.com/private/<name>/leads.csv,
 * outside public_html, so they are never web-accessible and deploys never touch them.
 */
declare(strict_types=1);

const ADMIN_EMAIL     = 'info@innovateincentivesgroup.com';
const FROM_EMAIL      = 'noreply@innovateincentivesgroup.com';
const FROM_NAME       = 'Innovate Incentives Group';
const SITE_URL        = 'https://innovateincentivesgroup.com';
const ALLOWED_ORIGINS = ['https://innovateincentivesgroup.com', 'https://www.innovateincentivesgroup.com'];
const RATE_LIMIT      = 5;    // submissions per IP per form...
const RATE_WINDOW     = 3600; // ...per hour

function respond(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body);
    exit;
}

/** Single-line, tag-free, length-capped value from POST. */
function field(string $key, int $max = 100): string
{
    $v = (string)($_POST[$key] ?? '');
    $v = strip_tags($v);
    $v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v) ?? '';
    return mb_substr(trim($v), 0, $max);
}

/** Multi-line, tag-free, length-capped value from POST (for message fields). */
function text_field(string $key, int $max = 3000): string
{
    $v = str_replace(["\r\n", "\r"], "\n", (string)($_POST[$key] ?? ''));
    $v = strip_tags($v);
    $v = preg_replace('/[\x00-\x09\x0B-\x1F\x7F]+/u', ' ', $v) ?? '';
    return mb_substr(trim($v), 0, $max);
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Stop spreadsheet apps from treating a value as a formula. */
function csv_safe(string $s): string
{
    return preg_match('/^[=+\-@\t]/', $s) ? "'" . $s : $s;
}

function send_mail(string $to, string $subject, string $html, string $replyTo): bool
{
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . $replyTo,
    ]);
    $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    return mail($to, $subject, $html, $headers, '-f' . FROM_EMAIL);
}

/** Headers, POST-only, same-origin and honeypot checks shared by every endpoint. */
function start_request(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        respond(405, ['ok' => false, 'error' => 'Method not allowed']);
    }
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '' && !in_array($origin, ALLOWED_ORIGINS, true)) {
        respond(403, ['ok' => false, 'error' => 'Forbidden']);
    }
    // Honeypot: real visitors never see or fill this field.
    if (field('website') !== '') {
        respond(200, ['ok' => true]);
    }
}

/** Respond 422 unless every listed key is non-empty and the email is valid. */
function require_fields(array $data, array $required, string $emailKey = 'email'): void
{
    foreach ($required as $key) {
        if ($data[$key] === '') {
            respond(422, ['ok' => false, 'error' => "Missing field: $key"]);
        }
    }
    if (!filter_var($data[$emailKey], FILTER_VALIDATE_EMAIL)) {
        respond(422, ['ok' => false, 'error' => 'Invalid email']);
    }
}

/** Private data dir for one form, created on first use. */
function data_dir(string $name): string
{
    $dir = dirname(__DIR__, 2) . '/private/' . $name;
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        error_log("forms: cannot create $dir");
        respond(500, ['ok' => false, 'error' => 'Server error']);
    }
    return $dir;
}

/** Per-IP rate limit (IPs are stored hashed). Responds 429 when exceeded. */
function rate_limit(string $dir): void
{
    $rl = fopen($dir . '/ratelimit.json', 'c+');
    if (!$rl || !flock($rl, LOCK_EX)) return;
    $now  = time();
    $ipId = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|iig');
    $hits = json_decode(stream_get_contents($rl) ?: '{}', true) ?: [];
    foreach ($hits as $k => $times) {
        $hits[$k] = array_values(array_filter($times, fn($t) => $t > $now - RATE_WINDOW));
        if (!$hits[$k]) unset($hits[$k]);
    }
    $limited = count($hits[$ipId] ?? []) >= RATE_LIMIT;
    if (!$limited) $hits[$ipId][] = $now;
    ftruncate($rl, 0);
    rewind($rl);
    fwrite($rl, json_encode($hits));
    flock($rl, LOCK_UN);
    fclose($rl);
    if ($limited) respond(429, ['ok' => false, 'error' => 'Too many submissions, please try later']);
}

/** Append one lead to <dir>/leads.csv, writing the header row on first use. */
function save_lead(string $dir, array $data): void
{
    $csv   = $dir . '/leads.csv';
    $isNew = !file_exists($csv);
    $fh    = fopen($csv, 'a');
    if (!$fh || !flock($fh, LOCK_EX)) {
        error_log("forms: cannot write $csv");
        return;
    }
    if ($isNew) fputcsv($fh, array_merge(['time'], array_keys($data)));
    fputcsv($fh, array_map('csv_safe', array_merge([date('Y-m-d H:i:s')], array_values($data))));
    flock($fh, LOCK_UN);
    fclose($fh);
}
