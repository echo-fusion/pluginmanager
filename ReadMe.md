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

use EchoFusion\PluginManager\PluginInterface;
use EchoFusion\PluginManager\Environment;

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

