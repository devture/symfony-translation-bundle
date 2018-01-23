<?php
namespace Devture\Bundle\TranslationBundle\Helper;

use Symfony\Component\HttpFoundation\Request;
use Devture\Bundle\TranslationBundle\Model\SearchRequest;

class SearchRequestBuilder {

	private $localeKeys;

	public function __construct(array $locales) {
		$this->localeKeys = array_map(function (array $localeData): string {
			return $localeData['key'];
		}, $locales);
	}

	public function buildFromHttpRequest(Request $httpRequest): SearchRequest {
		$searchRequest = new SearchRequest();

		$searchRequest->setKeywords($httpRequest->query->get('q', null));

		$localeKey = $httpRequest->query->get('localeKey', null);
		if (in_array($localeKey, $this->localeKeys)) {
			$searchRequest->setLocaleKey($localeKey);
		}

		$searchRequest->setSourceResourceId($httpRequest->query->get('sourceResourceId', null));

		return $searchRequest;
	}

}