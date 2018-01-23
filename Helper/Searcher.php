<?php
namespace Devture\Bundle\TranslationBundle\Helper;

use Devture\Bundle\TranslationBundle\Model\SearchRequest;
use Devture\Bundle\TranslationBundle\Model\TranslationString;
use Devture\Bundle\TranslationBundle\Model\ResourceInterface;
use Devture\Bundle\TranslationBundle\Model\SearchResult;
use Devture\Bundle\TranslationBundle\Model\SourceResource;

class Searcher {

	private $resourceFinder;

	public function __construct(ResourceFinder $resourceFinder) {
		$this->resourceFinder = $resourceFinder;
	}

	/**
	 * @return SearchResult[]
	 */
	public function search(SearchRequest $searchRequest): array {
		$searchResults = array();

		foreach ($this->resourceFinder->findAll() as $sourceResource) {
			if (!$this->isSourceResourceMatching($sourceResource, $searchRequest)) {
				continue;
			}

			$resourcesToSearch = array_merge(array($sourceResource), $sourceResource->getLocalizedResources());

			/* @var $resourceCandidate \Devture\Bundle\TranslationBundle\Model\ResourceInterface */
			foreach ($resourcesToSearch as $resourceCandidate) {
				if (!$this->isResourceMatching($resourceCandidate, $searchRequest)) {
					continue;
				}

				$localizedResource = ($resourceCandidate === $sourceResource ? null : $resourceCandidate);

				foreach ($resourceCandidate->getTranslationPack() as $translationString) {
					if (!$this->isTranslationStringMatching($translationString, $searchRequest)) {
						continue;
					}

					$searchResults[] = new SearchResult($sourceResource, $localizedResource, $translationString);
				}
			}
		}

		return $searchResults;
	}

	private function isSourceResourceMatching(SourceResource $sourceResource, SearchRequest $searchRequest): bool {
		if (!$searchRequest->getSourceResourceId()) {
			return true;
		}
		return ($sourceResource->getId() === $searchRequest->getSourceResourceId());
	}

	private function isResourceMatching(ResourceInterface $resource, SearchRequest $searchRequest): bool {
		if (!$searchRequest->getLocaleKey()) {
			return true;
		}
		return ($resource->getLocaleKey() === $searchRequest->getLocaleKey());
	}

	private function isTranslationStringMatching(TranslationString $translationString, SearchRequest $searchRequest): bool {
		return (stripos($translationString->getValue(), $searchRequest->getKeywords()) !== false);
	}

}