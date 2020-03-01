<?php
/**
 * @package    JShopping - Courierexe shipping
 * @version    __DEPLOY_VERSION__
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

require_once dirname(__FILE__) . '/MeasoftCourier.php';

class MeasoftCourierExt extends MeasoftCourier
{
	public function servicesList()
	{
		$results = $this->sendRequest($this->makeXML('services', array()));
		if (!empty($results))
		{
			return $results->xpath('./service');
		}
		else
		{
			return array();
		}

	}
}