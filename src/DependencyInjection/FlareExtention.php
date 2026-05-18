<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\DependencyInjection;

use Flawe\FlareBundle\FlareBundle;
use Flawe\FlareBundle\FlareFactory;
use Flawe\FlareBundle\Service\LoggerService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class FlareExtention extends Extension
{
    #[\Override]
    public function getAlias(): string
    {
        return 'flare';
    }

    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        echo "load";
        /**
         * @var array{
         *   key: string
         * } $config
         */
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('flare.key', $config['key']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        try {
            /** @var string $key */
            $key = $container->resolveEnvPlaceholders($config['key'], true);
            FlareFactory::init($key, $config);
        }
        catch (\RuntimeException $exception) {
            echo "catched";
        }
    }
}
