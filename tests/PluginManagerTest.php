<?php

declare(strict_types=1);

namespace EchoFusion\Tests\PluginManager;

use EchoFusion\PluginManager\Exceptions\PluginManagerException;
use EchoFusion\PluginManager\Exceptions\PluginNotRegisteredException;
use EchoFusion\PluginManager\PluginInterface;
use EchoFusion\PluginManager\PluginManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use function get_class;

class PluginManagerTest extends TestCase
{
    private PluginManager $pluginManager;

    private ContainerInterface $container;

    protected function setUp(): void
    {
        // Mock the container interface
        $this->container = $this->createMock(ContainerInterface::class);

        // Mock valid plugins
        $pluginMock1 = $this->createMock(PluginInterface::class);
        $pluginMock1->method('getSupportedEnvironments')
            ->willReturn(['dev', 'prod']);

        $pluginMock2 = $this->createMock(PluginInterface::class);
        $pluginMock2->method('getSupportedEnvironments')
            ->willReturn(['prod']);

        // Instantiate PluginManager with valid plugins (but don't call register yet)
        $this->pluginManager = new PluginManager(
            $this->container,
            [$pluginMock1, $pluginMock2]
        );
    }

    public function testRegisterPluginsForDevelopmentEnvironment(): void
    {
        $pluginMock = $this->createMock(PluginInterface::class);
        $pluginMock->method('getSupportedEnvironments')->willReturn(['dev']);
        $pluginMock->expects($this->once())->method('register');

        $this->pluginManager = new PluginManager($this->container, [$pluginMock]);
        $this->pluginManager->register('dev');

        $this->assertTrue($this->pluginManager->isRegistered(get_class($pluginMock)));
    }

    public function testRegisterPluginsForProductionEnvironment(): void
    {
        $pluginMock = $this->createMock(PluginInterface::class);
        $pluginMock->method('getSupportedEnvironments')->willReturn(['prod']);
        $pluginMock->expects($this->once())->method('register');

        $this->pluginManager = new PluginManager($this->container, [$pluginMock]);
        $this->pluginManager->register('prod');

        $this->assertTrue($this->pluginManager->isRegistered(get_class($pluginMock)));
    }

    public function testIsRegisteredReturnsFalseIfPluginIsNotRegistered(): void
    {
        $this->assertFalse($this->pluginManager->isRegistered('NonExistentPlugin'));
    }

    public function testGetPluginThrowsExceptionIfPluginIsNotRegistered(): void
    {
        $this->expectException(PluginNotRegisteredException::class);
        $this->pluginManager->getPlugin('NonExistentPlugin');
    }

    public function testGetPluginReturnsPluginInstanceIfRegistered(): void
    {
        $pluginMock = $this->createMock(PluginInterface::class);
        $pluginMock->method('getSupportedEnvironments')->willReturn(['dev']);
        $this->container->expects($this->once())->method('get')->willReturn($pluginMock);

        $this->pluginManager = new PluginManager($this->container, [$pluginMock]);
        $this->pluginManager->register('dev');

        $pluginInstance = $this->pluginManager->getPlugin(get_class($pluginMock));
        $this->assertInstanceOf(PluginInterface::class, $pluginInstance);
    }

    public function testExceptionIsThrownForInvalidPlugin(): void
    {
        $this->expectException(PluginManagerException::class);

        // Invalid plugin (not implementing PluginInterface)
        $invalidPlugin = new class() {
        };

        new PluginManager($this->container, [$invalidPlugin]);
    }

    public function testDoesNotRegisterPluginsForOtherEnvironments(): void
    {
        $pluginMock = $this->createMock(PluginInterface::class);
        $pluginMock->method('getSupportedEnvironments')->willReturn(['prod']);

        $this->pluginManager = new PluginManager($this->container, [$pluginMock]);
        $this->pluginManager->register('dev');

        $this->assertFalse($this->pluginManager->isRegistered(get_class($pluginMock)));
    }
}
