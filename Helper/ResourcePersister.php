<?php
namespace Devture\Bundle\TranslationBundle\Helper;

use Devture\Bundle\TranslationBundle\Model\SourceResource;
use Devture\Bundle\TranslationBundle\Model\ResourceInterface;

class ResourcePersister {

	public function persist(SourceResource $sourceResource, ResourceInterface $translatedResource): bool {
		$translations = array();

		/** @var \Devture\Bundle\TranslationBundle\Model\TranslationString $translationString **/
		foreach ($translatedResource->getTranslationPack() as $translationString) {
			if ($sourceResource !== $translatedResource) {
				//Only skip empty translations for the non-source locale.
				//This is so that we can support partial translations for non-source languages.
				//An empty translation means "not translated yet (use some other default translation)".
				//
				//If this is the source source, however, we don't want to allow skipping,
				//because skipping unsets (deletes) keys from the source resource.
				//We don't want to allow deletes to happen through here (only manually).
				if (!$translationString->getValue()) {
					continue;
				}
			}
			$translations[$translationString->getKey()] = (string) $translationString->getValue();
			$hashes[$translationString->getKey()] = $translationString->getSourceValueHash();
		}

		//Only sort the translations if we're dealing with a non-source resource.
		//This is because source translations are most often edited manually,
		//so their keys order is random (programmers don't usually alphabetically sort their translations).
		//If we force-sort alphabetically the source resource, we'll pretty much overwrite the whole file
		//(at least the first time we do this). We don't want to overwrite it, because that can very easily
		//introduce merge conflicts for people that actively develop on the system (adding new translation strings)
		//while someone else is using this tool to fix-up some translation.
		if ($sourceResource !== $translatedResource) {
			ksort($translations);
		}
		$translations = $this->unflatten($translations);
		$result = @file_put_contents($translatedResource->getPath(), $this->jsonEncode($translations));

		//Only save hashes for comparing with the source resource when persisting localized resources,
		//not when editing the translations of the original source resource.
		if ($sourceResource !== $translatedResource) {
			ksort($hashes);
			$result = @file_put_contents($translatedResource->getHashPath(), $this->jsonEncode($hashes));
		}

		return (bool) $result;
	}

	private function unflatten(array $array): array {
		$result = array();

		foreach ($array as $key => $value) {
			$keyParts = explode('.', $key);
			$target = &$result;
			foreach ($keyParts as $nestingKey) {
				if (!isset($target[$nestingKey])) {
					$target[$nestingKey] = array();
				}
				$target = &$target[$nestingKey];
			}
			$target = $value;
		}

		return $result;
	}

	private function jsonEncode(array $array): string {
		$text = json_encode($array, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

		//JSON_PRETTY_PRINT makes json_encode() indent.
		//However, it uses spaces, instead of tabs. We don't like that.
		$text = str_replace('    ', "\t", $text);

		return $text;
	}

}