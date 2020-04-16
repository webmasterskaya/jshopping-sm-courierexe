<?php
/**
 * @package    JShopping - Courierexe shipping
 * @version    1.0.0-rc
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;

FormHelper::loadFieldClass('list');
JLoader::register('MeasoftCourier', JPATH_LIBRARIES . '/measoft/MeasoftCourier.php');

class JFormFieldServices extends JFormFieldList
{

	protected $type = 'services';

	protected function getOptions()
	{
		$db= \Joomla\CMS\Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('params'))
			->from($db->quoteName('#__jshopping_shipping_ext_calc'))
			->where($db->quoteName('alias') .' = '.$db->quote('sm_courierexe'));
		$config = unserialize($db->setQuery($query)->loadResult());

		$connection = MeasoftCourier::getInstance();
		$services = $connection->servicesList();
		$options = array();
		if($services){
			foreach ($services as $service)
			{
				$options[] = HTMLHelper::_('select.option', $service->code, $service->name);
			}
		}
		return $options;
	}
}