<?php

declare(strict_types=1);

namespace EchoFusion\PluginManager;

interface PluginInterface
{
    public function register(): void;

    /**
     * @return string[] List of supported environments
     */
    public function getSupportedEnvironments(): array;
}
