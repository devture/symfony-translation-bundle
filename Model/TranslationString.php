<?php
namespace Devture\Bundle\TranslationBundle\Model;

class TranslationString {

	private $key;
	private $value;
	private $sourceValueHash;

	public function __construct(string $key, ?string $value, ?string $sourceValueHash) {
		$this->key = $key;
		$this->value = $value;
		$this->sourceValueHash = $sourceValueHash;
	}

	public function getKey(): string {
		return $this->key;
	}

	public function getValue(): string {
		return $this->value;
	}

	public function setValue(?string $value) {
		$this->value = (is_string($value) ? trim($value) : null);
	}

	public function getSourceValueHash(): ?string {
		return $this->sourceValueHash;
	}

	public function setSourceValueHash(string $hash) {
		$this->sourceValueHash = $hash;
	}

	public function isTranslatedVersionOf(TranslationString $other): bool {
		if (!$this->getValue()) {
			//Not translated at all.
			return false;
		}
		return ($this->getSourceValueHash() === $other->getSourceValueHash());
	}

	static public function calculateSourceValueHash(string $message): string {
		return hash('sha256', $message);
	}

}