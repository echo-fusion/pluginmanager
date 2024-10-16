# PluginManager

The **PluginManager** is a versatile package for managing and integrating plugins into your PHP applications. It allows you to dynamically load and register plugins based on the environment, making your application modular and extensible.

## Installation

Install the package via Composer:

```bash
composer require echo-fusion/pluginmanager
```

## Requirements

The following versions of PHP are supported by this version.

* PHP 8.1
* PHP 8.2
* PHP 8.3

## Usage

Here’s how to use the PluginManager to set up and run:

1. Instantiate the PluginManager with an array of plugins, specifying the environments in which they should be loaded:

```php
use EchoFusion\PluginManager\PluginManager;

$plugins = [
    'MyPlugin' => ['dev' => true],
    'AnotherPlugin' => ['prod' => true],
];

$pluginManager = new PluginManager($plugins);
```

2. Register the plugins based on the current environment:

```php
$environment = 'dev'; // or 'prod', etc.
$pluginManager->register($environment);
```

3. Ensure that your plugins implement the PluginInterface and have a register() method to handle the registration logic.

## Testing

Testing includes PHPUnit and PHPStan (Level 7).

``` bash
$ composer test
```

## Credits
Developed and maintained by [Amir Shadanfar](https://github.com/amir-shadanfar).  
Connect on [LinkedIn](https://www.linkedin.com/in/amir-shadanfar).

## License

The MIT License (MIT). Please see [License File](https://github.com/echo-fusion/middlewaremanager/blob/main/LICENSE) for more information.

