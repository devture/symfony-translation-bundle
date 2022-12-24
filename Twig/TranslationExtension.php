<?php
namespace Devture\Bundle\TranslationBundle\Twig;

class TranslationExtension extends \Twig\Extension\AbstractExtension {

	public function __construct(private array $locales) {
	}

	public function getName(): string {
		return 'devture_translation_extension';
	}

	public function getFunctions(): array {
		return [
			new \Twig\TwigFunction('devture_translation_get_locale_name', [$this, 'getLocaleName']),
		];
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
