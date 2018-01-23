<?php
namespace Devture\Bundle\TranslationBundle\Helper;

use Devture\Bundle\TranslationBundle\Model\SourceResource;
use Devture\Bundle\TranslationBundle\Model\LocalizedResource;

class ResourceFinder {

	private $pathsToTranslate;
	private $sourceLocaleKey;
	private $locales;
	private $translationPackLoader;

	public function __construct(
		array $pathsToTranslate,
		string $sourceLocaleKey,
		array $locales,
		ResourceTranslationPackLoader $translationPackLoader
	) {
		$this->pathsToTranslate = $pathsToTranslate;
		$this->sourceLocaleKey = $sourceLocaleKey;
		$this->locales = $locales;
		$this->translationPackLoader = $translationPackLoader;
	}

	/**
	 * @return \Devture\Bundle\TranslationBundle\Model\SourceResource[]
	 */
	public function findAll(): array {
		$sourceResources = [];
		foreach ($this->pathsToTranslate as $path) {
			foreach ($this->findAllByBasePath($path) as $sourceResource) {
				$sourceResources[] = $sourceResource;
			}
		}
		return $sourceResources;
	}

	/**
	 * @return \Devture\Bundle\TranslationBundle\Model\SourceResource[]
	 */
	private function findAllByBasePath(string $basePath): array {
		$iterator = new \RecursiveDirectoryIterator($basePath);
		$iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);

		$resources = array();

		/** @var \SplFileInfo $file **/
		foreach ($iterator as $file) {
			if (!preg_match('/\/translations\/(.+?)\.' . preg_quote($this->sourceLocaleKey) . '\.json$/', $file->getPathname())) {
				continue;
			}

			$filePath = $file->getPathname();

			$humanFriendlyName = $this->getHumanFriendlyNameByPath($filePath, $basePath);

			$sourceResource = new SourceResource($humanFriendlyName, $filePath, $this->sourceLocaleKey);
			$sourceResource->setTranslationPack($this->translationPackLoader->load($sourceResource));

			foreach ($this->locales as $localeData) {
				$localeKey = $localeData['key'];

				if ($localeKey === $this->sourceLocaleKey) {
					continue;
				}

				$localizedResourcePath = str_replace($this->sourceLocaleKey . '.json', $localeKey . '.json', $filePath);
				$localizedResource = new LocalizedResource($humanFriendlyName, $localizedResourcePath, $localeKey);
				$localizedResource->setTranslationPack($this->translationPackLoader->load($localizedResource));

				$sourceResource->addLocalizedResource($localizedResource);
			}

			$resources[] = $sourceResource;
		}

		return $resources;
	}

	public function findOneById($id): ?\Devture\Bundle\TranslationBundle\Model\SourceResource {
		foreach ($this->findAll() as $sourceResource) {
			if ($sourceResource->getId() === $id) {
				return $sourceResource;
			}
		}
		return null;
	}

	private function getHumanFriendlyNameByPath(string $filePath, string $basePath): string {
		//Special friendly name generation for bundle translation files.
		//Examples: `messages.en.json`, `another-domain.en.json`.
		if (preg_match('/\/([^\/]+)Bundle\/Resources\/translations\/([^\/\.]+)\.(?:[^\/\.]+)\.json$/', $filePath, $matches)) {
			$bundleName = $matches[1] . 'Bundle';
			$translationDomain = $matches[2];
			return sprintf('%s-%s', $bundleName, $translationDomain);
		}

		//This could happen if the $basePath that we were asked to search
		//is a PSR-4 bundle directory, so it has `Resources/translations` right inside.
		//Let's use the last part of the base directory (the container directory, so to speak).
		if (preg_match('/([^\/]+)\/Resources\/translations\/([^\/\.]+)\.(?:[^\/\.]+)\.json$/', $filePath, $matches)) {
			$basePathParts = explode('/', $basePath);
			return array_pop($basePathParts);
		}

		//Special friendly name generation for files in `<project>/translations`
		if (dirname($filePath) === $basePath) {
			$parts = explode('/', $filePath);
			$fileName = array_pop($parts);
			return preg_replace('/([^\.]+)\.([^\.]+)\.json$/', '$1', $fileName);
		}

		//In all other cases, take the full path (removing the locale key and file type) and slugify it.
		$cleanedPath = preg_replace('/(.+?)\/([^\/\.]+)\.(?:[^\/\.]+)\.json$/', '$1/$2', $filePath);
		return ltrim(str_replace('/', '-', $cleanedPath), '-');
	}

}