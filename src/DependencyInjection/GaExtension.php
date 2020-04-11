<?php declare(strict_types=1);

namespace GrownApps\JqlBundle\DependencyInjection;

use GrownApps\JqlBundle\Controller\ApiController;
use GrownApps\JqlBundle\FieldDefinitions\FieldDefinitionsProvider;
use GrownApps\JqlBundle\Hooks\JqlHookInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class GaExtension
 *
 * @package GrownApps\JqlBundle\DependencyInjection
 */
class GaExtension extends Extension
{

	public function load(array $configs, ContainerBuilder $container)
	{
		$loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
		$loader->load('services.xml');

		$configuration = new Configuration($container->getParameter('kernel.root_dir') . '/../src/AppBundle/Entity');
		$config = $this->processConfiguration($configuration, $configs);

		$definition = $container->getDefinition(FieldDefinitionsProvider::class);
		$definition->setArgument('$entitiesDir', $config['entities_dir']);
		$definition->setArgument('$cacheProvider', new Reference($config['cache_provider']));
		$plugins = [];
		foreach ($config['plugins'] as $pluginclass) {
			$plugins[] = new Reference($pluginclass);
		}
		$definition->setArgument('$plugins', $plugins);

		$ctrlDefinition = $container->getDefinition(ApiController::class);
		$ctrlDefinition->setArgument('$securityProvider', new Reference($config['security_provider']));

		$container->registerForAutoconfiguration(JqlHookInterface::class)->addTag('ga.jql_hook');
	}
}
