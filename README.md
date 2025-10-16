# Laravel Translation (nl-rdo-laravel-translation)

This package provides an easy command for checking for missing translations in your Laravel application. We recommend to only use this in development and not in production.

## Prerequisites

- PHP >= 8.2
- Composer
- Laravel >= 10.0

## Installation

### Composer

Install the package through composer:

```bash
composer require minvws/laravel-translation-check
```

## Usage

Basic usage:
```bash
$ php artisan translations:check
```

It will check for your language files in the default `resources/lang` directory. If your files are located in a different
directory, you can specify the path with the `langpath` parameter.
```bash
$ php artisan translations:check --langpath=resources/lang
```

There is also an optional `--update` flag for the command where you can update the language files with the missing 
translations:
```bash
$ php artisan translations:check --update
```

## Contributing
If you encounter any issues or have suggestions for improvements, please feel free to open an issue or submit a pull request on the GitHub repository of this package.

## License
This package is open-source and released under the European Union Public License version 1.2. You are free to use, modify, and distribute the package in accordance with the terms of the license.

## Part of iCore
This package is part of the iCore project.
