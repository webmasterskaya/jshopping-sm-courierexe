<?php
/**
 * @package    JShopping - Courierexe shipping
 * @version    1.0.0-rc2
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2020 Webmasterskaya. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 *  @link       https://webmasterskaya.xyz/
 * @since      1.0.0
 */

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;

FormHelper::loadFieldClass('list');

class JFormFieldExtrafields extends JFormFieldList
{

	public $type = 'extrafields';

	protected function getOptions()
	{
		$fields = JSFactory::getAllProductExtraField();

		$options[] = HTMLHelper::_('select.option', '', '---');
		if($fields){
			foreach ($fields as $field)
			{
				$options[] = HTMLHelper::_('select.option', $field->id, $field->name);
			}
		}
		return $options;
	}
}