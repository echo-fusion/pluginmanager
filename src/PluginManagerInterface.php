<?php

declare(strict_types=1);

namespace EchoFusion\PluginManager;

interface PluginManagerInterface
{
    public function register(string $environment): void;

    public function isRegistered(string $pluginName): bool;

    public function getPlugin(string $pluginClass): PluginInterface;
}
