<?php

declare(strict_types=1);

namespace EchoFusion\Tests\PluginManager;

use EchoFusion\Contracts\PluginManager\PluginInterface;
use EchoFusion\PluginManager\Exceptions\PluginManagerException;
use EchoFusion\PluginManager\Exceptions\PluginNotRegisteredException;
use EchoFusion\PluginManager\PluginManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
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
        $this->container->expects($this->never())->method('get');

        $this->pluginManager = new PluginManager($this->container, [$pluginMock]);
        $this->pluginManager->register('dev');

        $this->assertSame($pluginMock, $this->pluginManager->getPlugin(get_class($pluginMock)), 'the registered instance, not a new one');
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

    public function testAPluginIsRegisteredOnlyOnce(): void
    {
        $plugin = $this->createMock(PluginInterface::class);
        $plugin->method('getSupportedEnvironments')->willReturn(['dev', 'prod']);
        $plugin->expects($this->once())->method('register');

        $manager = new PluginManager($this->container, [$plugin]);
        $manager->register('dev');
        $manager->register('dev');
        $manager->register('prod');

        $this->assertTrue($manager->isRegistered(get_class($plugin)));
    }

    public function testThePluginRegistrationDoesNotTouchTheContainer(): void
    {
        $plugin = $this->createMock(PluginInterface::class);
        $plugin->method('getSupportedEnvironments')->willReturn(['dev']);
        $this->container->expects($this->never())->method('get');

        $manager = new PluginManager($this->container, [$plugin]);
        $manager->register('dev');
        $manager->getPlugin(get_class($plugin));
    }

    public function testInvalidPluginsAreNamedInTheMessage(): void
    {
        $this->expectException(PluginManagerException::class);
        $this->expectExceptionMessage('Plugin not-an-object does not implement PluginInterface.');

        new PluginManager($this->container, ['not-an-object']);
    }

    public function testPluginNotRegisteredMessageNamesTheClass(): void
    {
        $this->expectException(PluginNotRegisteredException::class);
        $this->expectExceptionMessage("Plugin 'Some\\Plugin' is not registered.");

        $this->pluginManager->getPlugin('Some\\Plugin');
    }

    public function testPluginsCanBeGivenByClassNameAndAreBuiltByTheContainer(): void
    {
        $plugin = new class() implements PluginInterface {
            public int $registered = 0;

            public function register(): void
            {
                ++$this->registered;
            }

            public function getSupportedEnvironments(): array
            {
                return ['dev'];
            }
        };
        $class = get_class($plugin);
        $this->container->expects($this->once())->method('get')->with($class)->willReturn($plugin);

        $manager = new PluginManager($this->container, [$class]);
        $manager->register('dev');
        $manager->register('dev');

        $this->assertSame(1, $plugin->registered);
        $this->assertTrue($manager->isRegistered($class));
        $this->assertSame($plugin, $manager->getPlugin($class));
    }

    public function testAPluginForAnotherEnvironmentIsBuiltOnlyOnce(): void
    {
        $plugin = new class() implements PluginInterface {
            public function register(): void
            {
            }

            public function getSupportedEnvironments(): array
            {
                return ['prod'];
            }
        };
        $class = get_class($plugin);
        $this->container->expects($this->once())->method('get')->willReturn($plugin);

        $manager = new PluginManager($this->container, [$class]);
        $manager->register('dev');
        $manager->register('dev');

        $this->assertFalse($manager->isRegistered($class));
    }

    public function testAClassNameThatIsNotAPluginIsRejected(): void
    {
        $this->expectException(PluginManagerException::class);
        $this->expectExceptionMessage('Plugin stdClass does not implement PluginInterface.');

        new PluginManager($this->container, [stdClass::class]);
    }

    public function testTheContainerMustReturnAPlugin(): void
    {
        $plugin = $this->createMock(PluginInterface::class);
        $class = get_class($plugin);
        $this->container->method('get')->willReturn(new stdClass());

        $manager = new PluginManager($this->container, [$class]);

        $this->expectException(PluginManagerException::class);
        $this->expectExceptionMessage('The container did not return a plugin for');
        $manager->register('dev');
    }
}
