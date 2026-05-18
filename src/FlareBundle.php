<?php

declare(strict_types=1);

namespace Flawe\FlareBundle;

use Flawe\FlareBundle\DependencyInjection\Compiler\ConfigurationPass;
use Flawe\FlareBundle\DependencyInjection\FlareExtention;
use Psr\Log\LoggerInterface;
use Spatie\FlareClient\Flare;
use Spatie\FlareClient\FlareConfig;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class FlareBundle extends AbstractBundle
{
    #[\Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new FlareExtention();
    }

    /**
     * @param array{
     *   key: string
     * } $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        echo "loadExtension";
        // $container->parameters()->set('flare.key', $config['key']);

        // // $logger = $container->services()->get(LoggerInterface::class);
        // // echo $logger::class;

        // echo FlareFactory::isInitialized();

        // try {
        //     /** @var string $key */
        //     $key = $builder->resolveEnvPlaceholders($config['key'], true);
        //     FlareFactory::init($key, $config);
        //     echo FlareFactory::isInitialized();
        // }
        // catch (\RuntimeException $exception) {
        //     echo "catched";
        // }

        // echo FlareFactory::isInitialized();

        // // echo "<br>";
        // // $flareConfig = FlareConfig::make($builder->getParameterBag()->resolveValue($config['key']))->useDefaults();

        // // echo "loadExtension";
        // // print_r($config);
        // // // print_r($config);
        // // echo "<br>--<br>";
        // // // echo $builder->getParameter('flare.key');
        // // echo "<br>--<br>";

        // // FlareFactory::createFlare( $config);

        // $container->services()->set(Flare::class)->factory([FlareFactory::class, 'load']);
        // $container->services()->alias(Flare::class, 'flare');
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ConfigurationPass());
    }
}
