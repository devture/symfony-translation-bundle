<?php
namespace Devture\Bundle\TranslationBundle\Model;

class TranslationPack implements \IteratorAggregate, \Countable {

	private $list = array();

	public function add(TranslationString $translationString) {
		$this->list[$translationString->getKey()] = $translationString;
	}

	public function syncWithSource(TranslationPack $sourcePack) {
		$newList = array();

		//Prune old translations that are not part of the source-pack anymore.
		foreach ($this->list as $translationString) {
			if (!$sourcePack->hasByKey($translationString->getKey())) {
				continue;
			}

			$sourceTranslationString = $sourcePack->getByKey($translationString->getKey());
			if ($sourceTranslationString->getSourceValueHash() !== $translationString->getSourceValueHash()) {
				//Outdated translation. Just ignore it and consider this untranslated.
				//Maybe we can do something better today.
				continue;
			}

			$newList[$translationString->getKey()] = $translationString;
		}

		//Add new translations that are part of the source pack, but not part of this pack yet.
		/* @var $translationString TranslationString */
		foreach ($sourcePack as $translationString) {
			$key = $translationString->getKey();
			if (isset($newList[$key])) {
				continue;
			}
			$newList[$key] = new TranslationString($translationString->getKey(), '', $translationString->getSourceValueHash());
		}

		$this->list = $newList;
	}

	public function hasByKey(string $key): bool {
		return (isset($this->list[$key]));
	}

	public function getByKey(string $key): ?TranslationString {
		return (isset($this->list[$key]) ? $this->list[$key] : null);
	}

	public function getIterator() {
		return new \ArrayIterator(array_values($this->list));
	}

	public function count(): int {
		return count($this->list);
	}

}