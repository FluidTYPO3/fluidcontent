<?php
namespace FluidTYPO3\Fluidcontent\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

/**
 * Class that renders a selection field for Fluid FCE template selection
 *
 * Legacy version for 6.2 LTS support
 */
class LegacyContentSelector extends ContentSelector {

	/**
	 * @var array
	 */
	protected $templates = [
		'select' => '<div><select
			style="background: #fff url(%s) 5px 50%% / 16px 16px no-repeat; padding-top: 2px; padding-left: 24px;"
			name="%s"
			class="formField select"
			onchange="if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };">
			%s</select></div>',
		'option' => '<option
			style="background:#fff url(%s) 2px 50%% / 16px 16px no-repeat; height: 16px; padding-top: 2px; padding-left: 22px;"
			value="%s"%s>%s</option>',
		'optionEmpty' => '<option value="">%s</option>',
		'optgroup' => '<optgroup label="%s">%s</optgroup>'
	];

}
