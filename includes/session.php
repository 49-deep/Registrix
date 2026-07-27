<?php
/**
 * includes/session.php
 * Starts the session, injects security headers, and provides CSRF & base_url helpers.
 * Include this at the top of every page BEFORE any output.
 */

// ── Security Headers ──────────────────────────────────────────────────────────
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// ── Session Initialization & Cookie Hardening ────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $session_dir = sys_get_temp_dir() . '/php_sessions';
    if (!is_dir($session_dir)) {
        @mkdir($session_dir, 0777, true);
    }
    @chmod($session_dir, 0777);
    @session_save_path($session_dir);

    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
             || (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $is_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Return absolute URL for a given relative path.
 * Works seamlessly on XAMPP subdirectories (/registrix/) and Railway root domain (/).
 */
if (!function_exists('base_url')) {
    function base_url(string $path = ''): string {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir    = str_replace('\\', '/', dirname($script));
        
        $base = ($dir === '/' || $dir === '.') ? '' : $dir;

        $system_dirs = ['/admin', '/student', '/api', '/config', '/includes', '/assets'];
        foreach ($system_dirs as $sys_dir) {
            if (str_ends_with($base, $sys_dir)) {
                $base = substr($base, 0, -strlen($sys_dir));
                break;
            }
        }

        $clean_path = $path ? '/' . ltrim($path, '/') : '';
        return $base . ($clean_path ?: '/');
    }
}

/**
 * Return the current CSRF token, generating one if needed.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF input field.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate the CSRF token submitted in a POST request.
 * Dies with 403 if invalid.
 */
function csrf_verify(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $submitted)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

/**
 * Store a one-time flash message in the session.
 */
function flash_set(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear the flash message. Returns null if none.
 *
 * @return array{type: string, message: string}|null
 */
function flash_get(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Escape a value for safe HTML output.
 */
function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}
