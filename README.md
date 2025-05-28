# An ergonomic interface for producing and consuming Kafka messages.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aryeohq/kafkaesque.svg?style=flat-square)](https://packagist.org/packages/aryeohq/kafkaesque)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/aryeohq/kafkaesque/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/aryeohq/kafkaesque/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/aryeohq/kafkaesque/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/aryeohq/kafkaesque/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/aryeohq/kafkaesque.svg?style=flat-square)](https://packagist.org/packages/aryeohq/kafkaesque)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require aryeohq/kafkaesque
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="kafkaesque-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="kafkaesque-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="kafkaesque-views"
```

## Usage

```php
$kafkaesque = new Aryeo\Kafkaesque();
echo $kafkaesque->echoPhrase('Hello, Aryeo!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Deven Jahnke](https://github.com/devenjahnke)
- [Cory Rosenwald](https://github.com/coryrose1)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
