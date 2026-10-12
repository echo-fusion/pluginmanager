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
use function sprintf;

class PluginManager implements PluginManagerInterface
{
    /**
     * @var list<PluginInterface>
     */
    private array $plugins = [];

    /**
     * @var array<class-string<PluginInterface>, PluginInterface> the plugins whose register() has run, by class
     */
    private array $registeredPlugins = [];

    /**
     * PluginManager constructor.
     *
     * @param ContainerInterface $container the container to resolve dependencies
     * @param array<PluginInterface> $plugins the list of plugins
     *
     * @throws PluginManagerException if a plugin doesn't implement PluginInterface
     */
    public function __construct(private ContainerInterface $container, array $plugins)
    {
        foreach ($plugins as $plugin) {
            if (!($plugin instanceof PluginInterface)) {
                throw new PluginManagerException(sprintf('Plugin %s does not implement PluginInterface.', get_debug_type($plugin)));
            }
            $this->plugins[] = $plugin;
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
        foreach ($this->plugins as $plugin) {
            if (isset($this->registeredPlugins[$plugin::class]) || !$this->isPluginForEnvironment($plugin, $environment)) {
                continue;
            }

            $plugin->register();
            $this->registeredPlugins[$plugin::class] = $plugin;
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

    private function isPluginForEnvironment(PluginInterface $plugin, string $environment): bool
    {
        return in_array($environment, $plugin->getSupportedEnvironments(), true);
    }
}
