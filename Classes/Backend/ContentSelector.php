<?php
namespace FluidTYPO3\Fluidcontent\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidcontent\Service\ConfigurationService;
use FluidTYPO3\Flux\Utility\MiscellaneousUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use FluidTYPO3\Flux\Form;

/**
 * Class that renders a selection field for Fluid FCE template selection
 */
class ContentSelector {

	/**
	 * @var array
	 */
	protected $templates = [
		'select' => '<div class="form-control-wrap"><div class="input-group">
			<div class="input-group-addon input-group-icon t3js-formengine-select-prepend"><img src="%s" alt="" /></div>
			<select name="%s" class="form-control form-control-adapt"
				onchange="if (confirm(TBE_EDITOR.labels.onChangeAlert)
					&& TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };">
				%s
			</select>
			</div>
			</div>',
		'option' => '<option data-icon="%s" value="%s"%s>%s</option>',
		'optionEmpty' => '<option value="">%s</option>',
		'optgroup' => '<optgroup label="%s">%s</optgroup>'
	];

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
		$selectedIcon = $this->getSelectedIcon($setup, $value);
		$options = $this->renderEmptyOption();
		foreach ($setup as $groupLabel => $configuration) {
			$options .= $this->renderOptionGroup($configuration, $groupLabel, $value);
		}
		return $this->wrapSelector($options, $name, $selectedIcon);
	}

	/**
	 * @param array $setup
	 * @param mixed $value
	 * @return NULL|string
	 */
	protected function getSelectedIcon(array $setup, $value) {
		foreach ($setup as $configuration) {
			foreach ($configuration as $form) {
				$optionValue = $form->getOption('contentElementId');
				if ($optionValue === $value) {
					return MiscellaneousUtility::getIconForTemplate($form);
				}
			}
		}
		return NULL;
	}

	/**
	 * @param string $selector
	 * @param string $name
	 * @param string $selectedIcon
	 * @return string
	 */
	protected function wrapSelector($selector, $name, $selectedIcon) {
		return sprintf($this->templates['select'], $selectedIcon, htmlspecialchars($name), $selector);
	}

	/**
	 * @param array $configuration
	 * @param string $groupLabel
	 * @param mixed $value
	 * @return string
	 */
	protected function renderOptionGroup(array $configuration, $groupLabel, $value) {
		foreach ($configuration as $form) {
			/** @var Form $form */
			$optionValue = $form->getOption('contentElementId');
			$selected = ($optionValue === $value ? ' selected="selected"' : '');
			$label = $form->getLabel();
			$icon = MiscellaneousUtility::getIconForTemplate($form);
			$label = (0 === strpos($label, 'LLL:') ? $GLOBALS['LANG']->sL($label) : $label);
			$valueString = htmlspecialchars($optionValue);
			$labelString = htmlspecialchars($label);
			$optionGroup .= sprintf($this->templates['option'], $icon, $valueString, $selected, $labelString) . LF;
		}
		return sprintf($this->templates['optgroup'], htmlspecialchars($groupLabel), $optionGroup);
	}

	/**
	 * @return string
	 */
	protected function renderEmptyOption() {
		return sprintf(
			$this->templates['optionEmpty'],
			$GLOBALS['LANG']->sL('LLL:EXT:fluidcontent/Resources/Private/Language/locallang.xml:tt_content.tx_fed_fcefile', TRUE)
		);
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
