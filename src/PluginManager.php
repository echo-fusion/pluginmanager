<?php

declare(strict_types=1);

namespace EchoFusion\PluginManager;

use EchoFusion\PluginManager\Exceptions\PluginManagerException;
use function in_array;

class PluginManager
{
    private array $plugins = [];

    private array $registeredPlugins = [];

    public function __construct(array $plugins)
    {
        foreach ($plugins as $name => $plugin) {
            if (!($plugin instanceof PluginInterface)) {
                throw new PluginManagerException("Plugin $name does not implement PluginInterface.");
            }
            $this->plugins[$name] = $plugin;
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
        foreach ($this->plugins as $name => $plugin) {
            if ($this->isPluginForEnvironment($plugin, $environment)) {
                $plugin->register();
                $this->registeredPlugins[$name] = $plugin;
            }
        }
    }

    public function isRegistered(string $pluginName): bool
    {
        return isset($this->registeredPlugins[$pluginName]);
    }

    private function isPluginForEnvironment(PluginInterface $plugin, string $environment): bool
    {
        return in_array($environment, $plugin->getSupportedEnvironments(), true);
    }
}
