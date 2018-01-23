<?php
namespace Devture\Bundle\TranslationBundle\Model;

class SearchRequest {

	private $keywords;
	private $localeKey;
	private $sourceResourceId;

	public function setKeywords(?string $value) {
		$this->keywords = trim($value);
	}

	public function getKeywords(): ?string {
		return $this->keywords;
	}

	public function setLocaleKey($value) {
		$this->localeKey = $value;
	}

	public function getLocaleKey(): ?string {
		return $this->localeKey;
	}

	public function setSourceResourceId($value) {
		$this->sourceResourceId = $value;
	}

	public function getSourceResourceId(): ?string {
		return $this->sourceResourceId;
	}

	public function isEmpty(): bool {
		return !$this->getKeywords();
	}

}