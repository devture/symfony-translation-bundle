<?php
namespace Devture\Bundle\TranslationBundle\Model;

class LocalizedResource implements ResourceInterface {

	private $name;
	private $path;
	private $localeKey;
	private $pack;

	public function __construct(string $name, string $path, string $localeKey) {
		$this->name = $name;
		$this->path = $path;
		$this->localeKey = $localeKey;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getPath(): string {
		return $this->path;
	}

	public function getHashPath(): ?string {
		return preg_replace('/\.([^\.]+)$/', '.hash.$1', $this->getPath());
	}

	public function getLocaleKey(): string {
		return $this->localeKey;
	}

	public function isSource(): bool {
		return false;
	}

	public function setTranslationPack(TranslationPack $pack) {
		$this->pack = $pack;
	}

	public function getTranslationPack(): TranslationPack {
		return $this->pack;
	}

	public function determineTranslationStatsAgainst(ResourceInterface $sourceResource): TranslationStats {
		$totalCount = 0;
		$translatedCount = 0;

		/* @var $sourceTranslationString TranslationString */
		foreach ($sourceResource->getTranslationPack() as $sourceTranslationString) {
			$totalCount += 1;

			/* @var $translationstring TranslationString|NULL */
			$translationString = $this->pack->getByKey($sourceTranslationString->getKey());
			if ($translationString === null) {
				//We don't have this one at all.
				continue;
			}

			$translatedCount += ($translationString->isTranslatedVersionOf($sourceTranslationString));
		}

		return new TranslationStats($translatedCount, $totalCount);
	}

}