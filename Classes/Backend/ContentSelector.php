<?php
namespace FluidTYPO3\Fluidcontent\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidcontent\Service\ConfigurationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use FluidTYPO3\Flux\Form;

/**
 * Class that renders a selection field for Fluid FCE template selection
 */
class ContentSelector {

	/**
	 * Render a Flexible Content Element type selection field
	 *
	 * @param array $parameters
	 * @param mixed $parentObject
	 * @return string
	 */
	public function renderField(array &$parameters, &$parentObject) {
		$contentService = $this->getConfigurationService();
		$setup = $contentService->getContentElementFormInstances();
		$name = $parameters['itemFormElName'];
		$value = $parameters['itemFormElValue'];
		$selectedIcon = '';
		$option = '<option value="">' . $GLOBALS['LANG']->sL('LLL:EXT:fluidcontent/Resources/Private/Language/locallang.xml:tt_content.tx_fed_fcefile', TRUE) . '</option>' . LF;
		foreach ($setup as $groupLabel => $configuration) {
			$option .= '<optgroup label="' . htmlspecialchars($groupLabel) . '">' . LF;
			foreach ($configuration as $form) {
				/** @var Form $form */
				$selected = '';
				$optionValue = $form->getOption('contentElementId');
				if ($optionValue === $value) {
					$selected = ' selected="selected"';
					$selectedIcon = $form->getOption(Form::OPTION_ICON);
				}
				$label = $form->getLabel();
				$label = (0 === strpos($label, 'LLL:') ? $GLOBALS['LANG']->sL($label) : $label);
				if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) < 7000000 ) {
					$option .= '<option ' .
						'style="background:#fff url(' . $form->getOption(Form::OPTION_ICON) . ') 2px 50% / 16px 16px no-repeat; height: 16px; padding-top: 2px; padding-left: 22px;" ' .
						'value="' . htmlspecialchars($optionValue) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>' . LF;
				} else {
					$option .= '<option ' .
						'data-icon="<img src=\'' . $form->getOption(Form::OPTION_ICON)  . '\' alt=\'' . htmlspecialchars($label) . '\' title=\'' . htmlspecialchars($label) . '\' />" '.
						'value="' . htmlspecialchars($optionValue) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>' . LF;
				}
			}
			$option .= '</optgroup>' . LF;
		}
		if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) < 7000000 ) {
			$select = '<div><select ' .
				'style="background: #fff url(' . $selectedIcon . ') 5px 50% / 16px 16px no-repeat; padding-top: 2px; padding-left: 24px;" ' .
				'name="' . htmlspecialchars($name) . '"  class="formField select" onchange="if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };">' . LF;
		} else {
			$select = '<div class="form-control-wrap"><div class="input-group">' .
				'<div id="' . htmlspecialchars($name) . '_icon" class="input-group-addon input-group-icon t3js-formengine-select-prepend">' .
				'<img src="' . $selectedIcon  . '" alt="' . htmlspecialchars($label) . '" title="' . htmlspecialchars($label) . '" />' .
				'</div>' .
				'<select ' .
				'name="' . htmlspecialchars($name) . '"  class="form-control form-control-adapt" onchange="document.getElementById(\'' . htmlspecialchars($name) . '_icon\').innerHTML = this.options[this.selectedIndex].getAttribute(\'data-icon\'); if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };">' . LF;
		}
		$select .= $option;
		if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) < 7000000 ) {
			$select .= '</select></div>' . LF;
		} else {
			$select .= '</select></div></div>' . LF;
		}
		unset($parentObject);
		return $select;
	}

	/**
	 * @return ConfigurationService
	 */
	protected function getConfigurationService() {
		/** @var ConfigurationService $contentService */
		$contentService = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager')
			->get('FluidTYPO3\Fluidcontent\Service\ConfigurationService');
		return $contentService;
	}

}
