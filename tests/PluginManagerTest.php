<?php

declare(strict_types=1);

use EchoFusion\PluginManager\Exceptions\PluginManagerException;
use EchoFusion\PluginManager\PluginInterface;
use EchoFusion\PluginManager\PluginManager;
use PHPUnit\Framework\TestCase;

class PluginManagerTest extends TestCase
{
    public function testRegisterPluginForSupportedEnvironment()
    {
        $mockPlugin = $this->createMock(PluginInterface::class);
        $mockPlugin->method('register');
        $mockPlugin->method('getSupportedEnvironments')->willReturn(['dev', 'test']);

        $plugins = [
            'MockPlugin' => $mockPlugin,
        ];

        $pluginManager = new PluginManager($plugins);

        $pluginManager->register('dev');
        $this->assertTrue($pluginManager->isRegistered('MockPlugin'), 'The plugin should be registered in the dev environment.');

        $pluginManager->register('test');
        $this->assertTrue($pluginManager->isRegistered('MockPlugin'), 'The plugin should be registered in the test environment.');
    }

    public function testDoNotRegisterPluginForUnsupportedEnvironment()
    {
        $mockPlugin = $this->createMock(PluginInterface::class);
        $mockPlugin->method('register');
        $mockPlugin->method('getSupportedEnvironments')->willReturn(['dev', 'test']);

        $plugins = [
            'MockPlugin' => $mockPlugin,
        ];

        $pluginManager = new PluginManager($plugins);

        $pluginManager->register('prod');
        $this->assertFalse($pluginManager->isRegistered('MockPlugin'), 'The plugin should not be registered in the prod environment.');
    }

    public function testThrowsExceptionForInvalidPlugin()
    {
        $this->expectException(PluginManagerException::class);

        $invalidPlugin = new stdClass();
        $plugins = [
            'InvalidPlugin' => $invalidPlugin,
        ];

        new PluginManager($plugins);
    }

    public function testRegisterMultiplePlugins()
    {
        $mockPluginA = $this->createMock(PluginInterface::class);
        $mockPluginA->method('register');
        $mockPluginA->method('getSupportedEnvironments')->willReturn(['dev']);

        $mockPluginB = $this->createMock(PluginInterface::class);
        $mockPluginB->method('register');
        $mockPluginB->method('getSupportedEnvironments')->willReturn(['test']);

        $plugins = [
            'PluginA' => $mockPluginA,
            'PluginB' => $mockPluginB,
        ];

        $pluginManager = new PluginManager($plugins);

        // Registering PluginA in 'dev' environment
        $pluginManager->register('dev');
        $this->assertTrue($pluginManager->isRegistered('PluginA'), 'PluginA should be registered in the dev environment.');
        $this->assertFalse($pluginManager->isRegistered('PluginB'), 'PluginB should not be registered in the dev environment.');

        // Registering PluginB in 'test' environment
        $pluginManager->register('test');
        $this->assertTrue($pluginManager->isRegistered('PluginB'), 'PluginB should be registered in the test environment.');
    }
}
