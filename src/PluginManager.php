<?php

declare(strict_types=1);

namespace EchoFusion\PluginManager;

use EchoFusion\Contracts\PluginManager\PluginInterface;
use EchoFusion\Contracts\PluginManager\PluginManagerInterface;
use EchoFusion\PluginManager\Exceptions\PluginManagerException;
use EchoFusion\PluginManager\Exceptions\PluginNotRegisteredException;
use Psr\Container\ContainerInterface;
use function get_debug_type;
use function in_array;
use function is_string;
use function is_subclass_of;
use function sprintf;

class PluginManager implements PluginManagerInterface
{
    /**
     * Plugin objects, or the class names of plugins that are not built yet.
     *
     * @var list<PluginInterface|string>
     */
    private array $plugins = [];

    /**
     * The plugins whose register() has run, by class name.
     *
     * @var array<string, PluginInterface>
     */
    private array $registeredPlugins = [];

    /**
     * PluginManager constructor.
     *
     * A plugin is an object, or the class name of a plugin. A class name is built by the container (so its
     * constructor dependencies are auto-wired) the first time `register()` needs it. Because the plugin must
     * be asked which environments it supports, keep plugin constructors cheap.
     *
     * @param ContainerInterface $container the container that builds plugins given by class name
     * @param array<PluginInterface|class-string<PluginInterface>> $plugins the plugins
     *
     * @throws PluginManagerException if an entry is not a plugin object or the name of a plugin class
     */
    public function __construct(private ContainerInterface $container, array $plugins)
    {
        foreach ($plugins as $plugin) {
            if ($plugin instanceof PluginInterface) {
                $this->plugins[] = $plugin;

                continue;
            }

            if (is_string($plugin) && is_subclass_of($plugin, PluginInterface::class)) {
                $this->plugins[] = $plugin;

                continue;
            }

            throw new PluginManagerException(sprintf('Plugin %s does not implement PluginInterface.', is_string($plugin) ? $plugin : get_debug_type($plugin)));
        }
    }

    /**
     * Register the plugins that support the environment. A plugin is registered only once, even when this
     * method is called again (for the same or another environment).
     *
     * @param string $environment The current environment (e.g., 'dev', 'prod').
     */
    public function register(string $environment): void
    {
        foreach ($this->plugins as $index => $entry) {
            if (is_string($entry)) {
                if (isset($this->registeredPlugins[$entry])) {
                    continue;
                }

                $plugin = $this->build($entry);
            } else {
                $plugin = $entry;
            }

            if (isset($this->registeredPlugins[$plugin::class]) || !$this->isPluginForEnvironment($plugin, $environment)) {
                // keep the built object, so a plugin for another environment is not built again
                $this->plugins[$index] = $plugin;

                continue;
            }

            $plugin->register();
            $this->registeredPlugins[$plugin::class] = $plugin;
            $this->plugins[$index] = $plugin;
        }
    }

    public function isRegistered(string $pluginName): bool
    {
        return isset($this->registeredPlugins[$pluginName]);
    }

    /**
     * The very instance whose register() ran, not a new one from the container.
     *
     * @throws PluginNotRegisteredException
     */
    public function getPlugin(string $pluginClass): PluginInterface
    {
        return $this->registeredPlugins[$pluginClass]
            ?? throw new PluginNotRegisteredException(sprintf("Plugin '%s' is not registered.", $pluginClass));
    }

    /**
     * @throws PluginManagerException when the container does not return a plugin
     */
    private function build(string $class): PluginInterface
    {
        $plugin = $this->container->get($class);

        return $plugin instanceof PluginInterface
            ? $plugin
            : throw new PluginManagerException(sprintf('The container did not return a plugin for %s.', $class));
    }

    private function isPluginForEnvironment(PluginInterface $plugin, string $environment): bool
    {
        return in_array($environment, $plugin->getSupportedEnvironments(), true);
    }
}
