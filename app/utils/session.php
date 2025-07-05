<?php
if (session_status() === PHP_SESSION_NONE) {

    // force cookies to be Secure, HttpOnly, sameSite=Lax
    session_set_cookie_params([
    'lifetime' => 0, // 0 means until the browser is closed
    'path'     => '/', // available within the entire domain
    'domain'   => 'https://crumbly.mooo.com', // crumbly's domain name
    'secure'   => true,    // HTTPS only
    'httponly' => true,    // JS can’t read the cookie
    'samesite' => 'Lax' // CSRF protection
    ]);

    session_start();
}
?>