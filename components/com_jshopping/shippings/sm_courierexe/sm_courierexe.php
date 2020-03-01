<?php

use Joomla\CMS\Form\Form;

/**
 * @package    JShopping - Courierexe shipping
 * @version    __DEPLOY_VERSION__
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

require_once dirname(__FILE__).'/classes/MeasoftCourierExt.php';

class sm_courierexe extends shippingextRoot
{
	var $version = 1;

	protected $form;
	protected $connection;

	/**
	 * sm_courierexe constructor.
	 *
	 * @param   int  $version
	 */
	public function __construct()
	{

	}

	function showShippingPriceForm($params, &$shipping_ext_row, &$template)
	{
		include(dirname(__FILE__) . "/shippingpriceform.php");
	}

	function showConfigForm($config, &$shipping_ext, &$template)
	{
		$this->form = new Form('adminForm');
		$this->form->addFormPath(dirname(__FILE__) . '/forms');
		$this->form->loadFile('connection');
		$this->form->loadFile('settings');
		$this->form->bind(['params' => $config]);
		include(dirname(__FILE__) . "/configform.php");
	}

	function getPrices($cart, $params, $prices, &$shipping_ext_row, &$shipping_method_price)
	{
		$weight_sum = $cart->getWeightProducts();
		$sh_price   = $shipping_method_price->getPrices("desc");
		foreach ($sh_price as $sh_pr)
		{
			if ($weight_sum >= $sh_pr->shipping_weight_from && ($weight_sum <= $sh_pr->shipping_weight_to || $sh_pr->shipping_weight_to == 0))
			{
				$prices['shipping'] = $sh_pr->shipping_price;
				$prices['package']  = $sh_pr->shipping_package_price;
				break;
			}
		}
		return $prices;
	}
}