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

## What a plugin is for

A **plugin** is an optional feature that is switched on for certain environments (`dev`, `test`, `prod` ...) and is given the container so it can wire itself in. Typical plugins: a debug bar or profiler only in `dev`, a fake mailer in `test`, an error tracker only in `prod`, a feature flag, or an add-on that attaches listeners and adds services.

It is not a service container (that is `servicemanager`, which decides *how objects are built*), and it differs from a module (`modulemanager`), which *declares data*: config, providers, routes, templates. A plugin *runs code* at start-up.

## Usage

```php
use EchoFusion\Contracts\PluginManager\PluginInterface;
use EchoFusion\Contracts\ServiceManager\SettableContainerInterface;
use EchoFusion\PluginManager\PluginManager;

final class DebugBarPlugin implements PluginInterface
{
    public function register(SettableContainerInterface $container): void
    {
        // use anything the container knows: attach a listener, add a service, replace an implementation
        $container->get(EventManagerInterface::class)->attach('response.ready', new DebugBarListener());
        $container->set(MailerInterface::class, fn () => new LogMailer());
    }

    public function getSupportedEnvironments(): array
    {
        return ['dev', 'test'];
    }
}

$pluginManager = new PluginManager($container, [DebugBarPlugin::class, new ErrorTrackerPlugin($sentry)]);
$pluginManager->register('dev');            // DebugBarPlugin::register() runs; plugins for other environments do not

$pluginManager->isRegistered(DebugBarPlugin::class);   // true
$pluginManager->getPlugin(DebugBarPlugin::class);      // the registered instance
```

`$container` is a `SettableContainerInterface` (a PSR-11 container that also has `set()`); `servicemanager` provides one.

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
