<?php
namespace FluidTYPO3\Fluidcontent\Hooks;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidcontent\Backend\ContentTypeFilter;
use FluidTYPO3\Fluidcontent\Service\ConfigurationService;
use FluidTYPO3\Flux\Form\FormInterface;
use FluidTYPO3\Flux\Hooks\WizardItemsHookSubscriber as FluxWizardItemsHookSubscriber;
use TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * WizardItems Hook Subscriber
 */
class WizardItemsHookSubscriber extends FluxWizardItemsHookSubscriber implements NewContentElementWizardHookInterface
{

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        /** @var ConfigurationService $configurationService */
        $configurationService = $this->objectManager->get(ConfigurationService::class);
        $this->injectConfigurationService($configurationService);
    }

    /**
     * @param array $items
     * @param NewContentElementController $parentObject
     * @return void
     */
    public function manipulateWizardItems(&$items, &$parentObject)
    {
        $this->configurationService->writeCachedConfigurationIfMissing();
        $items = $this->filterPermittedFluidContentTypesByInsertionPosition($items, $parentObject);
        $items = $this->filterPermittedFluidContentTypesByUserGroupAccessList($items, $parentObject);
    }

    /**
     * @param array $items
     * @param NewContentElementController $parentObject
     * @return array
     */
    protected function filterPermittedFluidContentTypesByUserGroupAccessList(array $items, $parentObject)
    {
        $filter = $this->getContentTypeFilter(
            (array) $GLOBALS['TCA']['tt_content']['columns']['tx_fed_fcefile']['config']['items']
        );
        list ($blacklist, $whitelist) = $filter->extractBlacklistAndWhitelistFromCurrentBackendUser();
        // Filter by which fluidcontent types are allowed by backend user group
        $items = $this->applyWhitelist($items, (array) $whitelist);
        $items = $this->applyBlacklist($items, (array) $blacklist);
        return $items;
    }

    /**
     * @param array $items
     * @param array $whitelist
     * @return array
     */
    protected function applyWhitelist(array $items, array $whitelist)
    {
        if (0 < count($whitelist)) {
            foreach ($items as $name => $item) {
                if (false !== strpos($name, '_') && 'fluidcontent_content' === $item['tt_content_defValues']['CType']
                    && false === in_array($item['tt_content_defValues']['tx_fed_fcefile'], $whitelist)) {
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
    protected function applyBlacklist(array $items, array $blacklist)
    {
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
    protected function appendToWhiteAndBlacklistFromComponent(
        FormInterface $component,
        array $whitelist,
        array $blacklist
    ) {
        $allowed = $component->getVariable('Fluidcontent.allowedContentTypes');
        if (null !== $allowed) {
            $whitelist = array_merge($whitelist, GeneralUtility::trimExplode(',', $allowed));
        }
        $denied = $component->getVariable('Fluidcontent.deniedContentTypes');
        if (null !== $denied) {
            $blacklist = array_merge($blacklist, GeneralUtility::trimExplode(',', $denied));
        }
        return [$whitelist, $blacklist];
    }

    /**
     * @param array $items
     * @return ContentTypeFilter
     */
    protected function getContentTypeFilter(array $items)
    {
        return new ContentTypeFilter($items);
    }
}
