<?php


namespace GrownApps\JqlBundle\DependencyInjection;


use GrownApps\JqlBundle\FieldDefinitions\InMemoryCacheProvider;
use GrownApps\JqlBundle\Security\DummySecurityProvider;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
	private $entitiesDir;


	/**
	 * Configuration constructor.
	 *
	 * @param $entitiesDir
	 */
	public function __construct($entitiesDir)
	{
		$this->entitiesDir = $entitiesDir;
	}


	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root('ga');

		$rootNode->children()
			->variableNode('entities_dir')->defaultValue($this->entitiesDir)->end()
			->variableNode('security_provider')->defaultValue(DummySecurityProvider::class)->end()
			->variableNode('cache_provider')->defaultValue(InMemoryCacheProvider::class)->end()
			->variableNode('plugins')->defaultValue([])->end()
			->end();

		return $treeBuilder;
	}

}
