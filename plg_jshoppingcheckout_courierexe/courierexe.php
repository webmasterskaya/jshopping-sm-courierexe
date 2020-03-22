<?php
/**
 * @package    JShopping - Courierexe shipping
 * @version    __DEPLOY_VERSION__
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\Factory as Factory;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die();

JLoader::register('MeasoftCourier', JPATH_LIBRARIES . '/measoft/MeasoftCourier.php');

class plgJshoppingcheckoutCourierexe extends CMSPlugin
{
	protected $connection;

	public function __construct(&$subject, $config = array())
	{
		$this->connection = MeasoftCourier::getInstance();
		parent::__construct($subject, $config);
	}

	public function onAjaxCourierexe()
	{
		$input = Factory::getApplication()->input;
		$get   = $input->get->getArray();
		$task  = $input->get('task', '');
		if (!$task)
		{
			return true;
		}

		switch ($task)
		{
			case 'cities':
				return $this->getCitiesList(trim($get['city_name']));
				break;
		}

		return true;
	}

	public function getCitiesList($str, $limit = 10)
	{
		if (strlen($str) > 0)
		{
			$result = $this->connection->citiesList(array('namecontains' => strtolower($str), 'country' => 1),
				array('limitcount' => $limit));
		}
		return !empty($result) ? $result : [];
	}
}