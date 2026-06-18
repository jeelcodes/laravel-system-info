# Laravel System Info

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeelcodes/laravel-system-info.svg?style=flat-square)](https://packagist.org/packages/jeelcodes/laravel-system-info)
[![Total Downloads](https://img.shields.io/packagist/dt/jeelcodes/laravel-system-info.svg?style=flat-square&cache=clear)](https://packagist.org/packages/jeelcodes/laravel-system-info)
[![Run tests](https://github.com/jeelcodes/laravel-system-info/actions/workflows/run-tests.yml/badge.svg)](https://github.com/jeelcodes/laravel-system-info/actions/workflows/run-tests.yml)

A powerful and easy-to-use Laravel package to display your application's environment details, and composer package dependencies through a clean user interface.

## Features

- **System Environment Overview:** Quickly view PHP version, Laravel version, server software, OS, and database connection details.
- **Composer Packages:** See a complete list of installed composer packages along with their versions.
- **Customizable Routes:** Change the default URL path to whatever fits your application.
- **Zero Dependencies:** Built with native Laravel features, keeping your project lightweight.

## Requirements

- PHP 7.2.5 or higher (Fully compatible with PHP 8.x)
- Laravel 6.0 through 11.0+

## Installation

You can install the package via composer:

```bash
composer require jeelcodes/laravel-system-info
```

The package will automatically register its service provider in modern Laravel applications.

## Usage

Once installed, simply navigate to the following URL in your browser:

```
http://your-app-domain.test/system-info
```

Here you will see a beautifully formatted dashboard containing all your system information.

## Configuration

If you want to change the default route prefix (`/system-info`), you can publish the configuration file.

Run the following artisan command:

```bash
php artisan vendor:publish --tag="system-info-config"
```

This will create a `config/system-info.php` file in your application's config directory:

```php
<?php

return [
    // The route path to access the system info dashboard
    'route_prefix' => 'system-info',
];
```

Change the `route_prefix` value to your desired URL path (e.g., `'admin/system-status'`).

## Views

If you want to customize the appearance of the dashboard, you can publish the views:

```bash
php artisan vendor:publish --tag="system-info-views"
```
*(Note: View publishing requires adding the 'system-info-views' tag to the ServiceProvider, make sure it's published if needed).*

## Testing

```bash
vendor/bin/phpunit
```

## Security Vulnerabilities

If you discover a security vulnerability within this package, please send an e-mail to Jeel Sureja via [your email]. All security vulnerabilities will be promptly addressed.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
