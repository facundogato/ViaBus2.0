# Laravel Secure Headers

 A lightweight Laravel package that automatically adds essential **HTTP Security Headers** to enhance your application's **security** and **performance**.

---

##  Installation

Require the package via Composer:

```bash
composer require amreljako/laravel-secure-headers
```

---

##  Publish Config

Publish the configuration file to your application:

```bash
php artisan vendor:publish --provider="Amreljako\SecureHeaders\SecureHeadersServiceProvider" --tag=config
```

This will create the following file in your project:

```
config/secure-headers.php
```

---

##  Usage

Register the middleware in `app/Http/Kernel.php`:

```php
protected $middleware = [
    \Amreljako\SecureHeaders\Middleware\SecureHeadersMiddleware::class,
];
```

Once registered, the middleware will automatically apply security headers to **all HTTP responses**.

---

##  Configuration

You can customize the headers in `config/secure-headers.php`:

```php
return [
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'no-referrer-when-downgrade',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
    'Content-Security-Policy' => "default-src 'self'; img-src 'self' https://trusted-cdn.com; script-src 'self' https://apis.google.com",
];
```

---

##  Example Response Headers

After installation, each Laravel response will include headers such as:

```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: no-referrer-when-downgrade
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
Content-Security-Policy: default-src 'self'
```

---

##  Why Use Security Headers?

- **X-Frame-Options** → Prevents Clickjacking attacks  
- **X-Content-Type-Options** → Blocks MIME type sniffing  
- **X-XSS-Protection** → Adds basic browser XSS protection (legacy)  
- **Referrer-Policy** → Controls what referrer information is sent with requests  
- **Strict-Transport-Security (HSTS)** → Enforces secure HTTPS connections  
- **Content-Security-Policy (CSP)** → Strong protection against XSS and data injection attacks  

---

##  License

This package is open-sourced software licensed under the [MIT license](LICENSE).
