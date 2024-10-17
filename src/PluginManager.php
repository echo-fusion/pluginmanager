<?php

declare(strict_types=1);

namespace EchoFusion\PluginManager;

use EchoFusion\PluginManager\Exceptions\PluginManagerException;
use EchoFusion\PluginManager\Exceptions\PluginNotRegisteredException;
use Psr\Container\ContainerInterface;
use function get_class;
use function in_array;
use function sprintf;

class PluginManager implements PluginManagerInterface
{
    private array $plugins = [];

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
                throw new PluginManagerException(sprintf('Plugin %s does not implement PluginInterface.', get_class($plugin)));
            }
            $this->plugins[] = $plugin;
        }
    }

    /**
     * Register plugins based on the current environment.
     *
     * @param string $environment The current environment (e.g., 'dev', 'prod').
     * @throws PluginManagerException if a plugin doesn't implement PluginInterface
     */
    public function register(string $environment): void
    {
        foreach ($this->plugins as $plugin) {
            if ($this->isPluginForEnvironment($plugin, $environment)) {
                $plugin->register();
                $this->registeredPlugins[] = $plugin;
            }
        }
    }

    public function isRegistered(string $pluginName): bool
    {
        $registeredClassNames = array_map('get_class', $this->registeredPlugins);

        return in_array($pluginName, $registeredClassNames, true);
    }

    public function getPlugin(string $pluginClass): PluginInterface
    {
        if (!$this->isRegistered($pluginClass)) {
            throw new PluginNotRegisteredException("Plugin '{$pluginClass}' is not registered.");
        }

        return $this->container->get($pluginClass);
    }

    private function isPluginForEnvironment(PluginInterface $plugin, string $environment): bool
    {
        return in_array($environment, $plugin->getSupportedEnvironments(), true);
    }
}
