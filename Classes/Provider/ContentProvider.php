<?php
namespace FluidTYPO3\Fluidcontent\Provider;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidcontent\Backend\ContentTypeFilter;
use FluidTYPO3\Fluidcontent\Service\ConfigurationService;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Provider\ContentProvider as FluxContentProvider;
use FluidTYPO3\Flux\Provider\ProviderInterface;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use FluidTYPO3\Flux\Utility\PathUtility;
use FluidTYPO3\Flux\View\TemplatePaths;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Content object configuration provider
 */
class ContentProvider extends FluxContentProvider implements ProviderInterface
{

    /**
     * @var string
     */
    protected $controllerName = 'Content';

    /**
     * @var string
     */
    protected $tableName = 'tt_content';

    /**
     * @var string
     */
    protected $fieldName = 'pi_flexform';

    /**
     * @var string
     */
    protected $extensionKey = 'fluidcontent';

    /**
     * @var string
     */
    protected $contentObjectType = 'fluidcontent_content';

    /**
     * @var string
     */
    protected $configurationSectionName = 'Configuration';

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var ConfigurationService
     */
    protected $contentConfigurationService;

    /**
     * @param ConfigurationManagerInterface $configurationManager
     * @return void
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param ConfigurationService $configurationService
     * @return void
     */
    public function injectContentConfigurationService(ConfigurationService $configurationService)
    {
        $this->contentConfigurationService = $configurationService;
    }

    /**
     * @param array $items
     * @return ContentTypeFilter
     */
    protected function getContentTypeFilter(array $items)
    {
        return new ContentTypeFilter($items);
    }

    /**
     * @param array $row
     * @param array $configuration
     * @return array
     */
    public function processTableConfiguration(array $row, array $configuration)
    {
        if ($row['CType'] === $this->contentObjectType && isset($configuration['processedTca']['columns']['tx_fed_fcefile'])) {
            // Create values for the fluidcontent type selector
            $configuration['processedTca']['columns']['tx_fed_fcefile']['config']['items'] = array_merge(
                $configuration['processedTca']['columns']['tx_fed_fcefile']['config']['items'],
                $this->contentConfigurationService->getContentTypeSelectorItems()
            );

            // Filter by which fluidcontent types are allowed by backend user group
            $configuration['processedTca']['columns']['tx_fed_fcefile']['config']['items'] =
            $this->getContentTypeFilter(
                $configuration['processedTca']['columns']['tx_fed_fcefile']['config']['items']
            )->filterItemsByGroupAccess();

            // Filter by which fluidcontent types are allowed by content area, if element is nested
            if (!empty($row['tx_flux_parent']) && !empty($row['tx_flux_column'])) {
                $filter = $this->getContentTypeFilter(
                    $configuration['processedTca']['columns']['tx_fed_fcefile']['config']['items']
                );
                $configuration['processedTca']['columns']['tx_fed_fcefile']['config']['items'] =
                $this->getContentTypeFilter(
                    $configuration['processedTca']['columns']['tx_fed_fcefile']['config']['items']
                )->filterItemsByGridColumn(
                    $this->getGrid(
                        $this->recordService->getSingle(
                            'tt_content',
                            '*',
                            (integer) $row['tx_flux_parent']
                        )
                    ),
                    $row['tx_flux_column']
                );
            }
        }
        return $configuration;
    }

    /**
     * @param array $row
     * @return \FluidTYPO3\Flux\Form|NULL
     */
    public function getForm(array $row)
    {
        $form = parent::getForm($row);
        if (null !== $form) {
            $moveSortingProperty = (
                false === $form->hasOption(Form::OPTION_SORTING)
                && true === $form->hasOption('Fluidcontent.sorting')
            );
            if (true === $moveSortingProperty) {
                $form->setOption(Form::OPTION_SORTING, $form->getOption('Fluidcontent.sorting'));
            }
        }
        return $form;
    }

    /**
     * @param array $row
     * @return string
     */
    public function getTemplatePathAndFilename(array $row)
    {
        if (false === empty($this->templatePathAndFilename)) {
            $templateReference = $this->templatePathAndFilename;
            if ('/' !== $templateReference{0}) {
                $templateReference = GeneralUtility::getFileAbsFileName($templateReference);
            }
            if (true === file_exists($templateReference)) {
                return $templateReference;
            }
            return null;
        }
        $templateReference = $this->getControllerActionReferenceFromRecord($row);
        list (, $filename) = explode(':', $templateReference);
        list ($controllerAction, $format) = explode('.', $filename);
        $controllerAction = $this->getControllerActionFromRecord($row);
        $paths = $this->getTemplatePaths($row);
        $templatePaths = new TemplatePaths($paths);
        return $templatePaths->resolveTemplateFileForControllerAndActionAndFormat(
            'Content',
            $controllerAction,
            $format
        );
    }

    /**
     * @param array $row
     * @return array
     */
    public function getTemplatePaths(array $row)
    {
        $extensionName = $this->getExtensionKey($row);
        $paths = $this->contentConfigurationService->getContentConfiguration($extensionName);
        if (true === is_array($paths) && false === empty($paths)) {
            $paths = PathUtility::translatePath($paths);
        }
        return $paths;
    }

    /**
     * @param array $row
     * @return string
     */
    public function getExtensionKey(array $row)
    {
        $extensionKey = $this->extensionKey;
        $action = $row['tx_fed_fcefile'];
        if (false !== strpos($action, ':')) {
            $extensionName = array_shift(explode(':', $action));
            if (false === empty($extensionName)) {
                $extensionKey = ExtensionNamingUtility::getExtensionKey($extensionName);
            }
        }
        return $extensionKey;
    }

    /**
     * @param array $row
     * @return string
     */
    public function getControllerExtensionKeyFromRecord(array $row)
    {
        $fileReference = $this->getControllerActionReferenceFromRecord($row);
        $identifier = explode(':', $fileReference);
        $extensionName = array_shift($identifier);
        return $extensionName;
    }

    /**
     * @param array $row
     * @return string
     */
    public function getControllerActionFromRecord(array $row)
    {
        $fileReference = $this->getControllerActionReferenceFromRecord($row);
        $identifier = explode(':', $fileReference);
        $actionName = array_pop($identifier);
        $actionName = basename($actionName, '.html');
        $actionName = lcfirst($actionName);
        return $actionName;
    }

    /**
     * @param array $row
     * @return string
     */
    public function getControllerActionReferenceFromRecord(array $row)
    {
        $fileReference = $row['tx_fed_fcefile'];
        return true === empty($fileReference) ? 'Fluidcontent:error.html' : $fileReference;
    }

    /**
     * Switchable priority: highest possible for records matching
     * this Provider's targeted CType - lowest possible for others.
     *
     * @param array $row
     * @return integer
     */
    public function getPriority(array $row)
    {
        if (true === isset($row['CType']) && $this->contentObjectType === $row['CType']) {
            return 100;
        }
        return 0;
    }

    /**
     * Get preview chunks - header and content - as
     * array(string $headerContent, string $previewContent, boolean $continueRendering)
     *
     * @param array $row The record data to be analysed for variables to use in a rendered preview
     * @return array
     */
    public function getPreview(array $row)
    {
        if ($this->contentObjectType !== $row['CType']) {
            return [null, null, true];
        }

        $previewContent = $this->getPreviewView()->getPreview($this, $row);
        return [null, $previewContent, empty($previewContent)];
    }
}
