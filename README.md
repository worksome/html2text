# Html2Text

[![Latest Version on Packagist](https://img.shields.io/packagist/v/worksome/laravel-mfa.svg?style=flat-square)](https://packagist.org/packages/worksome/laravel-mfa)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/worksome/laravel-mfa/Tests?label=tests)](https://github.com/worksome/laravel-mfa/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/worksome/laravel-mfa/Static%20Analysis?label=code%20style)](https://github.com/worksome/laravel-mfa/actions?query=workflow%3A"Static+Analysis"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/worksome/laravel-mfa.svg?style=flat-square)](https://packagist.org/packages/worksome/laravel-mfa)

A simple converter from HTML to Plaintext

## Installation

You can install the package via composer:

```bash
composer require worksome/html2text
```

## Usage

```php
$text = \Worksome\Html2Text\Html2Text::convert($html);
```

See the [original repository](https://github.com/soundasleep/html2text) for more information.

### Options

| Option        | Default | Description                                                                             |
|---------------|---------|-----------------------------------------------------------------------------------------|
| **dropLinks** | `false` | Set to `true` to not render links as `My Link` instead of `[https://foo.com](My Link)`. |

Pass along a configuration class as a second argument to `convert`, for example:

```php
$options = new \Worksome\Html2Text\Config(
    dropLinks: true
);

$text = \Worksome\Html2Text\Html2Text::convert($html, $options);
```

## Testing

```bash
composer test
```
## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Owen Voke](https://github.com/owenvoke)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
