<?php
/**
 * @package    JShopping - Courierexe shipping
 * @version    __DEPLOY_VERSION__
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die();

JLoader::register('MeasoftCourierExt',
	JPATH_ROOT . '/components/com_jshopping/shippings/sm_courierexe/classes/MeasoftCourierExt.php');

class plgJshoppingcheckoutCourierexe extends CMSPlugin
{
	protected $connection;

	public function __construct(&$subject, $config = array())
	{
		$db    = \Joomla\CMS\Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('params'))
			->from($db->quoteName('#__jshopping_shipping_ext_calc'))
			->where($db->quoteName('alias') . ' = ' . $db->quote('sm_courierexe'));
		$config = unserialize($db->setQuery($query)->loadResult());

		$this->connection = new MeasoftCourierExt($config['api_user_name'], $config['api_user_pw'],
			$config['api_user_extra']);

		parent::__construct($subject, $config);
	}

	public function onAjaxCourierexe()
	{
		$input = \Joomla\CMS\Factory::getApplication()->input;
		$get   = $input->get->getArray();
		$task  = $input->get('task', '');
		if (!$task)
		{
			return true;
		}

		switch ($task){
			case 'cities':
				$path_of_city_name = trim($get['city_name']);
				if(strlen($path_of_city_name) > 0){
					return $this->connection->citiesList(array('namecontains'=>strtolower($path_of_city_name), 'country'=>1), array('limitcount'=>10));
				}
				break;
		}

		return true;
	}
}