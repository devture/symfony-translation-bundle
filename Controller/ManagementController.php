<?php
namespace Devture\Bundle\TranslationBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Devture\Bundle\TranslationBundle\Model\SourceResource;
use Devture\Bundle\TranslationBundle\Model\ResourceInterface;
use Devture\Bundle\TranslationBundle\Model\TranslationString;
use Devture\Bundle\TranslationBundle\Helper\ResourceFinder;
use Devture\Bundle\TranslationBundle\Helper\ResourcePersister;
use Devture\Bundle\TranslationBundle\Helper\SearchRequestBuilder;
use Devture\Bundle\TranslationBundle\Helper\Searcher;

class ManagementController extends AbstractController {

	private $locales;
	private $twigLayoutPath;

	public function __construct(array $locales, string $twigLayoutPath) {
		$this->locales = $locales;
		$this->twigLayoutPath = $twigLayoutPath;
	}

	/**
	 * @Route("/manage", name="devture_translation.manage", methods={"GET"})
	 */
	public function index(Request $request, ResourceFinder $resourceFinder, TranslatorInterface $translator) {
		$resources = $resourceFinder->findAll();

		return $this->render('@DevtureTranslation/index.html.twig', array(
			'twigLayoutPath' => $this->twigLayoutPath,
			'sourceResources' => $resources,
		));
	}

	/**
	 * @Route("/search", name="devture_translation.search", methods={"GET"})
	 */
	public function searchAction(
		Request $request,
		SearchRequestBuilder $searchRequestBuilder,
		Searcher $searcher,
		ResourceFinder $resourceFinder
	) {
		$searchRequest = $searchRequestBuilder->buildFromHttpRequest($request);

		if ($searchRequest->isEmpty()) {
			$searchResults = null;
		} else {
			$searchResults = $searcher->search($searchRequest);
		}

		return $this->render('@DevtureTranslation/search.html.twig', array(
			'twigLayoutPath' => $this->twigLayoutPath,
			'locales' => $this->locales,
			'searchRequest' => $searchRequest,
			'searchResults' => $searchResults,
			'sourceResources' => $resourceFinder->findAll(),
		));
	}

	/**
	 * @Route("/edit/{resourceId}/{language}", name="devture_translation.edit", methods={"GET", "POST"})
	 */
	public function edit(
		Request $request,
		string $resourceId,
		string $language,
		ResourceFinder $resourceFinder,
		ResourcePersister $resourcePersister,
		TranslatorInterface $translator
	) {
		$sourceResource = $resourceFinder->findOneById($resourceId);

		if ($sourceResource === null) {
			throw $this->createNotFoundException('Not found');
		}

		if ($sourceResource->getLocaleKey() === $language) {
			$translatableResource = $sourceResource;
			$defaultTab = 'all';
		} else {
			$translatableResource = $sourceResource->getLocalizedResourceByLocaleKey($language);
			if ($translatableResource === null) {
				throw $this->createNotFoundException('Not found');
			}

			$translatableResource->getTranslationPack()->syncWithSource($sourceResource->getTranslationPack());
			$defaultTab = 'untranslated';
		}

		if ($request->isMethod('POST')) {
			list($errors, $modifiedResources) = $this->bindRequestToResource($request, $sourceResource, $translatableResource);

			if (count($errors) > 0) {
				return $this->json(array('ok' => false, 'errors' => $errors));
			}

			foreach ($modifiedResources as $modifiedResource) {
				$result = $resourcePersister->persist($sourceResource, $modifiedResource);
				if (!$result) {
					return $this->json([
						'ok' => false,
						'errors' => [
							$translator->trans(
								'devture_translation.error_while_writing',
								[
									'%resource%' => $modifiedResource->getName(),
									'%locale%' => $modifiedResource->getLocaleKey(),
								]
							),
						],
					]);
				}
			}

			$packStatus = array();
			/* @var $sourceTranslationString \Devture\Bundle\TranslationBundle\Model\TranslationString */
			foreach ($sourceResource->getTranslationPack() as $sourceTranslationString) {
				/* @var $translationString \Devture\Bundle\TranslationBundle\Model\TranslationString|NULL */
				$translationString = $translatableResource->getTranslationPack()->getByKey($sourceTranslationString->getKey());
				$packStatus[$sourceTranslationString->getKey()] = ($translationString === null ? false : $translationString->isTranslatedVersionOf($sourceTranslationString));
			}

			return $this->json(array('ok' => true, 'packStatus' => $packStatus));
		}

		$tabToActivate = $request->query->get('tab', $defaultTab);
		if (!in_array($tabToActivate, array('untranslated', 'translated', 'all'))) {
			$tabToActivate = $defaultTab;
		}

		return $this->render('@DevtureTranslation/edit.html.twig', array(
			'twigLayoutPath' => $this->twigLayoutPath,
			'sourceResource' => $sourceResource,
			'translatableResource' => $translatableResource,
			'tabToActivate' => $tabToActivate,
		));
	}

	/**
	 * Binds the translations to the $targetResource's translation pack, but may also bind to others.
	 *
	 * In case we're editing a source resource ($sourceResource === $targetResource), and the
	 * "Minor Correction" checkbox was checked for a translation, this may modify other resources as well.
	 *
	 * A list of every touched resource is returned.
	 *
	 * @param Request $request
	 * @param SourceResource $sourceResource
	 * @param ResourceInterface $targetResource
	 * @return 2-tuple: (array $errors, ResourceInterface[] $modifiedResources)
	 */
	private function bindRequestToResource(Request $request, SourceResource $sourceResource, ResourceInterface $targetResource) {
		$sourcePack = $sourceResource->getTranslationPack();
		$targetPack = $targetResource->getTranslationPack();
		$isEditingSourceResource = ($sourceResource === $targetResource);

		$translations = (array) $request->request->all('translations');

		$modifiedResources = array();

		foreach ($translations as $key => $translationData) {
			if (!$targetPack->hasByKey($key) || !$sourcePack->hasByKey($key)) {
				continue;
			}

			$translationString = $targetPack->getByKey($key);

			$value = (string) (isset($translationData['translation']) ? $translationData['translation'] : null);
			$sourceValueHash = (string) (isset($translationData['sourceValueHash']) ? $translationData['sourceValueHash'] : null);

			if ($translationString->getValue() === $value) {
				continue;
			}

			//This minor correction thing is only related to source pack translations.
			if ($isEditingSourceResource) {
				$isMinorSourceCorrection = (isset($translationData['minorSourceFix']) ? ($translationData['minorSourceFix'] === 'on') : false);
			} else {
				$isMinorSourceCorrection = false;
			}

			//Only do this integrity check when translating into another language (when the source pack != target pack).
			//Otherwise any change we make to the source translations would invalidate the form until a reload is done.
			if (!$isEditingSourceResource) {
				if ($sourcePack->getByKey($key)->getSourceValueHash() !== $sourceValueHash) {
					//The original string being translated actually changed. Require a reload.
					return array('The translation files have changed. Please reload the page and try again.', array());
				}
			}

			//We only bind to the value, not to the source value hash.
			//For non-source resources, the source value hash remains the same, when doing changes.
			//For the source resource, we change the source value hash below, because we want to run some other logic first.
			$translationString->setValue($value);
			$modifiedResources[] = $targetResource;

			if ($isEditingSourceResource) {
				$newSourceValueHash = TranslationString::calculateSourceValueHash($translationString->getValue());

				//If a source resource is being translated and a translation is marked as "minor",
				//preserve the translations for all other resources that were up to date until now.
				if ($isMinorSourceCorrection) {
					foreach ($sourceResource->getLocalizedResources() as $otherTranslatableResource) {
						if (!$otherTranslatableResource->getTranslationPack()->hasByKey($key)) {
							//This is not even translated to the other language yet. Nothing to do.
							continue;
						}

						$otherLanguageTranslationString = $otherTranslatableResource->getTranslationPack()->getByKey($key);

						if (!$otherLanguageTranslationString->isTranslatedVersionOf($translationString)) {
							//This other language's translation is not currently up date, so we won't be marking it as "still ok".
							continue;
						}

						$otherLanguageTranslationString->setSourceValueHash($newSourceValueHash);

						$modifiedResources[] = $otherTranslatableResource;
					}
				}

				//Only now could we change this, after we've dealt with all other translated strings derived from it.
				//(because isTranslatedVersionOf() calls above depend on the source value hash not having had changed).
				$translationString->setSourceValueHash($newSourceValueHash);
			}
		}

		return array(array(), array_unique($modifiedResources, SORT_REGULAR));
	}

}
