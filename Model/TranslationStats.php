<?php
namespace Devture\Bundle\TranslationBundle\Model;

class TranslationStats {

	private $translatedCount;
	private $totalCount;

	public function __construct(int $translatedCount, int $totalCount) {
		$this->translatedCount = $translatedCount;
		$this->totalCount = $totalCount;
	}

	public function getTranslatedCount(): int {
		return $this->translatedCount;
	}

	public function getTotalCount(): int {
		return $this->totalCount;
	}

	public function getTranslatedPercentage(): float {
		if ($this->totalCount === 0) {
			return 100;
		}
		return floor(($this->translatedCount / $this->totalCount) * 100);
	}

}