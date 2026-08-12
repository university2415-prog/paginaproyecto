<?php
// Simple Supabase helper for PHP
// Usage: require_once __DIR__ . '/lib/supabase.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    // Prefer vlucas/phpdotenv if installed
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
    }
}

// Fallback to getenv if phpdotenv not present
function supabase_env($key, $default = null) {
    $v = getenv($key);
    if ($v === false) return $default;
    return $v;
}

function supabase_request($method, $path, $body = null, $use_service_key = true) {
    $base = rtrim(supabase_env('SUPABASE_URL', ''), '/');
    if (empty($base)) throw new Exception('SUPABASE_URL not configured');

    $url = $base . '/' . ltrim($path, '/');

    $headers = [
        'Content-Type: application/json',
    ];

    $serviceKey = supabase_env('SUPABASE_SECRET_KEY', '');
    $anonKey = supabase_env('SUPABASE_PUBLISHABLE_KEY', '');

    if ($use_service_key && $serviceKey) {
        $headers[] = 'Authorization: Bearer ' . $serviceKey;
        $headers[] = 'apikey: ' . $serviceKey;
    } elseif ($anonKey) {
        $headers[] = 'apikey: ' . $anonKey;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Optional: disable SSL verification for local testing when SUPABASE_DISABLE_SSL_VERIFY=1
    $disableSsl = supabase_env('SUPABASE_DISABLE_SSL_VERIFY', '0') === '1';
    if ($disableSsl) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }

    if ($body !== null) {
        $payload = is_string($body) ? $body : json_encode($body);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('cURL error: ' . $err);
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($resp, true);
    return [
        'status' => $code,
        'body' => $decoded === null ? $resp : $decoded,
        'raw' => $resp,
    ];
}

// Helper to call Supabase REST (table) endpoints, e.g. 'rest/v1/<table>'
function supabase_table($method, $table, $body = null, $qs = '') {
    $path = 'rest/v1/' . $table;
    if ($qs) $path .= '?' . ltrim($qs, '?');
    return supabase_request($method, $path, $body, true);
}

?>
