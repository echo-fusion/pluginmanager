# PluginManager

[![CI](https://github.com/echo-fusion/pluginmanager/actions/workflows/ci.yml/badge.svg)](https://github.com/echo-fusion/pluginmanager/actions/workflows/ci.yml) [![Packagist](https://img.shields.io/packagist/v/echo-fusion/pluginmanager.svg)](https://packagist.org/packages/echo-fusion/pluginmanager) [![PHP](https://img.shields.io/badge/php-8.1%20%7C%208.2%20%7C%208.3-777bb4.svg)](https://www.php.net) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

The **PluginManager** is a versatile package for managing and integrating plugins into your PHP applications. It allows you to dynamically load and register plugins based on the environment, making your application modular and extensible.

> Part of [EchoFusion](https://github.com/echo-fusion), a modular PHP framework built from small packages.

## Installation

```bash
composer require echo-fusion/pluginmanager
```

## Requirements

* PHP 8.1, 8.2 or 8.3

## Usage

Here’s how to use the PluginManager to set up and run:

1. Instantiate the PluginManager with an array of plugins, specifying the environments in which they should be loaded:

```php
use EchoFusion\PluginManager\PluginManager;
use Psr\Container\ContainerInterface;

$createPluginManager = function(ContainerInterface $container) {

    $plugins = [
        MyPlugin::class,     
        AnotherPlugin::class, 
    ];
    
    return new PluginManager($container, $plugins);
}
```

2. Register the plugins based on the current environment:

```php
// Usage
$container = // Your container implementation here
$pluginManager = $createPluginManager($container);

try {
    // Register plugins for a specific environment (e.g., 'dev')
    $pluginManager->register('dev');
} catch (PluginManagerException $e) {
    // Handle any exceptions related to plugin management
    echo 'Error registering plugins: ' . $e->getMessage();
} catch (Throwable $e) {
    // General fallback for other types of exceptions
    echo 'An unexpected error occurred: ' . $e->getMessage();
}
```

3. Ensure that your plugins implement the PluginInterface:

```php
<?php

declare(strict_types=1);

namespace YourNamespace\Plugins;

use EchoFusion\Contracts\PluginManager\PluginInterface;

class ExamplePlugin implements PluginInterface
{
    private $service; 
    
    public function register(): void
    {
        // Initialize the service
        $this->service = $this->initializeService();

        // Set up configurations
        $this->configureSettings();

        // Register event listeners
        $this->registerEventListeners();

        echo "ExamplePlugin has been registered successfully.\n";
    }

    public function getSupportedEnvironments(): array
    {
        return ['dev','prod']; 
    }   
    //...
}

```

## Plugins by class name

A plugin can be an object or the **class name** of a plugin. A class name is built by the container (constructor dependencies are auto-wired) the first time `register()` needs it:

```php
$pluginManager = new PluginManager($container, [
    DebugBarPlugin::class,          // built by the container
    new ErrorTrackerPlugin($sentry), // or an object you already have
]);
$pluginManager->register('dev');
```

The manager has to ask a plugin which environments it supports before it can skip it, so every plugin is built once even when it is not for the current environment. Keep plugin constructors cheap.

## Behaviour worth knowing

* `register($environment)` runs a plugin's `register()` **once**, only for plugins whose `getSupportedEnvironments()` contains the environment. Calling it again does not run them again.
* `getPlugin(Foo::class)` returns the very instance that was registered (it does not ask the container for a new one), and throws `PluginNotRegisteredException` when that plugin was not registered for the current environment.

## Testing

```bash
composer test
```

## Credits

- [Amir Shadanfar](https://github.com/amir-shadanfar)

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
