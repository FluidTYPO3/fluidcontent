<?php
namespace FluidTYPO3\Fluidcontent\Provider;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\Provider\AbstractProvider;
use FluidTYPO3\Flux\Provider\ProviderInterface;
use FluidTYPO3\Fluidcontent\Service\ConfigurationService;

/**
 * Class BackendUserGroupProvider
 */
class BackendUserGroupProvider extends AbstractProvider implements ProviderInterface
{

    /**
     * @var ConfigurationService
     */
    protected $contentConfigurationService;

    /**
     * @var string
     */
    protected $tableName = 'be_groups';

    /**
     * @var string|NULL
     */
    protected $fieldName = null;

    /**
     * @param ConfigurationService $configurationService
     * @return void
     */
    public function injectContentConfigurationService(ConfigurationService $configurationService)
    {
        $this->contentConfigurationService = $configurationService;
    }

    /**+
     * @param array $row
     * @param array $configuration
     * @return array
     */
    public function processTableConfiguration(array $row, array $configuration)
    {

        // Create values for the fluidcontent type selectors
        $configuration['processedTca']['columns']['tx_fluidcontent_allowedfluidcontent']['config']['items'] =
            $this->contentConfigurationService->getContentTypeSelectorItems();
        $configuration['processedTca']['columns']['tx_fluidcontent_deniedfluidcontent']['config']['items'] =
            $this->contentConfigurationService->getContentTypeSelectorItems();

        return $configuration;
    }
}
