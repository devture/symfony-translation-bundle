<?php
namespace Devture\Bundle\TranslationBundle\Model;

class SourceResource implements ResourceInterface {

	private $name;
	private $path;
	private $localeKey;
	private $localizedResources = array();
	private $pack;

	public function __construct($name, $path, $localeKey) {
		$this->name = $name;
		$this->path = $path;
		$this->localeKey = $localeKey;
	}

	public function getId(): string {
		return $this->getName();
	}

	public function getName(): string {
		return $this->name;
	}

	public function getPath(): string {
		return $this->path;
	}

	public function getHashPath(): ?string {
		throw new \LogicException('Source resources do not have a hash');
	}

	public function getLocaleKey(): string {
		return $this->localeKey;
	}

	public function addLocalizedResource(LocalizedResource $localizedResource) {
		$this->localizedResources[] = $localizedResource;
	}

	/**
	 * @return LocalizedResource[]
	 */
	public function getLocalizedResources(): array {
		return $this->localizedResources;
	}

	public function getLocalizedResourceByLocaleKey(string $localeKey): ?LocalizedResource {
		foreach ($this->getLocalizedResources() as $localizedResource) {
			if ($localizedResource->getLocaleKey() === $localeKey) {
				return $localizedResource;
			}
		}
		return null;
	}

	public function isSource(): bool {
		return true;
	}

	public function setTranslationPack(TranslationPack $pack) {
		$this->pack = $pack;
	}

	public function getTranslationPack(): TranslationPack {
		return $this->pack;
	}

}