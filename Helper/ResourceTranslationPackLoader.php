<?php
namespace Devture\Bundle\TranslationBundle\Helper;

use Devture\Bundle\TranslationBundle\Model\ResourceInterface;
use Devture\Bundle\TranslationBundle\Model\TranslationPack;
use Devture\Bundle\TranslationBundle\Model\TranslationString;

class ResourceTranslationPackLoader {

	public function load(ResourceInterface $resource): TranslationPack {
		$pack = new TranslationPack();

		if (!file_exists($resource->getPath())) {
			return $pack;
		}

		$contents = file_get_contents($resource->getPath());
		$json = json_decode($contents, 1);
		if (!is_array($json)) {
			throw new \LogicException('Bad resource: ' . $resource->getPath());
		}
		$flattened = $this->flatten($json);

		$hashes = array();
		if (!$resource->isSource()) {
			$hashesFilePath = $resource->getHashPath();
			if (file_exists($hashesFilePath)) {
				$hashesContents = file_get_contents($hashesFilePath);
				$hashes = json_decode($hashesContents, 1);
			}
		}

		foreach ($flattened as $key => $value) {
			if ($resource->isSource()) {
				$sourceValueHash = TranslationString::calculateSourceValueHash($value);
			} else {
				$sourceValueHash = (isset($hashes[$key]) ? $hashes[$key] : null);
			}

			$pack->add(new TranslationString($key, $value, $sourceValueHash));
		}

		return $pack;
	}

	private function flatten(array $array): array {
		$doFlatten = function (array $array, array $keyStack, &$result) use (&$doFlatten) {
			foreach ($array as $key => $value) {
				$currentKeyStack = array_merge($keyStack, array($key));
				if (is_array($value)) {
					$doFlatten($value, $currentKeyStack, $result);
				} else {
					$result[implode('.', $currentKeyStack)] = $value;
				}
			}
		};

		$result = array();
		$doFlatten($array, array(), $result);
		return $result;
	}

}