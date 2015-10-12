<?php
namespace FluidTYPO3\Fluidcontent\Service;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Utility\MiscellaneousUtility;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * WizardTab Service
 *
 * Provides methods to create a WizardTab item
 * Using TYPO3\CMS\Core\Imaging\IconRegistry to register the icon
 */
class WizardTabService {

   /**
    * Builds a single Wizard item (one FCE) based on the
    * tab id, element id, configuration array and special
    * template identity (groupName:Relative/Path/File.html)
    *
    * @param ConfigurationService $configurationService
    * @param string               $tabId
    * @param string               $id
    * @param Form                 $form
    * @param string               $templateFileIdentity
    *
    * @return string
    */
   public function buildWizardTabItem($configurationService, $tabId, $id, $form, $templateFileIdentity) {
      $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);

      if (TRUE === method_exists(MiscellaneousUtility::class, 'getIconForTemplate')) {
         $icon = MiscellaneousUtility::getIconForTemplate($form);
         $icon = ($icon ? $icon : $configurationService->getDefaultIcon());
      } else {
         $icon = $configurationService->getDefaultIcon();
      }
      $description = $form->getDescription();
      if (0 === strpos($icon, '../')) {
         $icon = substr($icon, 2);
      }
      if ('/' === $icon[0]) {
         $icon = realpath(PATH_site . $icon);
      }

      if (TRUE === file_exists($icon) && TRUE === method_exists(MiscellaneousUtility::class, 'createIcon')) {
         $ext = strtolower(pathinfo($icon, PATHINFO_EXTENSION));

         $iconRegistry->registerIcon(
            'content-' . $id,
            $ext === 'svg' ? \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class : \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            array(
               'source' => $icon
            )
         );
      }

      return sprintf('
			mod.wizards.newContentElement.wizardItems.%s.elements.%s {
				iconIdentifier = %s
				title = %s
				description = %s
				tt_content_defValues {
					CType = fluidcontent_content
					tx_fed_fcefile = %s
				}
			}
			',
         $tabId,
         $id,
         'content-' . $id,
         $form->getLabel(),
         $description,
         $templateFileIdentity
      );
   }

}
