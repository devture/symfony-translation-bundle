<?php
namespace Devture\Bundle\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {

	public function getConfigTreeBuilder(): TreeBuilder {
		$treeBuilder = new TreeBuilder('devture_translation');

		$rootNode = $treeBuilder->getRootNode();

		$rootNode
			->children()
				->scalarNode('source_language_locale_key')->end()
				->arrayNode('paths_to_translate')
					->scalarPrototype()->end()
				->end()
				->arrayNode('locales')
					->arrayPrototype()
						->children()
							->scalarNode('key')->end()
							->scalarNode('name')->end()
						->end()
					->end()
				->end()
				->scalarNode('twig_layout_path')->end()
			->end()
		;

		return $treeBuilder;
	}

}
