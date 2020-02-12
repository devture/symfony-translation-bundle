<?php
namespace Devture\Bundle\TranslationBundle\Twig;

class TranslationExtension extends \Twig\Extension\AbstractExtension {

	private $locales;

	public function __construct(array $locales) {
		$this->locales = $locales;
	}

	public function getName() {
		return 'devture_translation_extension';
	}

	public function getFunctions() {
		return array(
			new \Twig\TwigFunction('devture_translation_get_locale_name', [$this, 'getLocaleName']),
		);
	}

	public function getLocaleName(string $localeKey): ?string {
		foreach ($this->locales as $localeData) {
			if ($localeData['key'] === $localeKey) {
				return $localeData['name'];
			}
		}
		return null;
	}

}
