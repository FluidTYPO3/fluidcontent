<?php
namespace FluidTYPO3\Fluidcontent\Hooks;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidcontent\Service\ConfigurationService;
use FluidTYPO3\Flux\Form\FormInterface;
use FluidTYPO3\Flux\Hooks\WizardItemsHookSubscriber as FluxWizardItemsHookSubscriber;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

/**
 * WizardItems Hook Subscriber
 */
class WizardItemsHookSubscriber extends FluxWizardItemsHookSubscriber implements NewContentElementWizardHookInterface {

	/**
	 * @var ConfigurationService
	 */
	protected $configurationService;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$configurationService = $this->objectManager->get('FluidTYPO3\\Fluidcontent\\Service\\ConfigurationService');
		$this->injectConfigurationService($configurationService);
	}

	/**
	 * @param array $items
	 * @param NewContentElementController $parentObject
	 * @return void
	 */
	public function manipulateWizardItems(&$items, &$parentObject) {
		$this->configurationService->writeCachedConfigurationIfMissing();
		$items = $this->filterPermittedFluidContentTypesByInsertionPosition($items, $parentObject);
	}

	/**
	 * @param array $items
	 * @param array $whitelist
	 * @return array
	 */
	protected function applyWhitelist(array $items, array $whitelist) {
		if (0 < count($whitelist)) {
			foreach ($items as $name => $item) {
				if (FALSE !== strpos($name, '_') && 'fluidcontent_content' === $item['tt_content_defValues']['CType']
					&& FALSE === in_array($item['tt_content_defValues']['tx_fed_fcefile'], $whitelist)) {
					unset($items[$name]);
				}
			}
		}
		return $items;
	}

	/**
	 * @param array $items
	 * @param array $blacklist
	 * @return array
	 */
	protected function applyBlacklist(array $items, array $blacklist) {
		if (0 < count($blacklist)) {
			foreach ($blacklist as $contentElementType) {
				foreach ($items as $name => $item) {
					if ('fluidcontent_content' === $item['tt_content_defValues']['CType']
						&& $item['tt_content_defValues']['tx_fed_fcefile'] === $contentElementType) {
						unset($items[$name]);
					}
				}
			}
		}
		return $items;
	}

	/**
	 * @param FormInterface $component
	 * @param array $whitelist
	 * @param array $blacklist
	 * @return array
	 */
	protected function appendToWhiteAndBlacklistFromComponent(FormInterface $component, array $whitelist, array $blacklist) {
		$allowed = $component->getVariable('Fluidcontent.allowedContentTypes');
		if (NULL !== $allowed) {
			$whitelist = array_merge($whitelist, GeneralUtility::trimExplode(',', $allowed));
		}
		$denied = $component->getVariable('Fluidcontent.deniedContentTypes');
		if (NULL !== $denied) {
			$blacklist = array_merge($blacklist, GeneralUtility::trimExplode(',', $denied));
		}
		return [$whitelist, $blacklist];
	}
}
