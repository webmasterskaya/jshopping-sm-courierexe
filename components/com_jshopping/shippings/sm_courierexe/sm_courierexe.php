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

JLoader::register('MeasoftCourierExt', dirname(__FILE__) . '/classes/MeasoftCourierExt.php');

class sm_courierexe extends shippingextRoot
{
	public $version = 1;

	protected $form;
	protected $connection;

	public function getPrice($cart, $params, $prices, &$shipping_ext_row, &$shipping_method_price)
	{
		$user = &JFactory::getUser();
		if ($user->id)
		{
			$user_info = &JSFactory::getUserShop();
		}
		else
		{
			$user_info = &JSFactory::getUserShopGuest();
		}
		if($shipping_method_price->shipping_method_id == $user_info->shipping_id){
			$shipping_params = unserialize($shipping_ext_row->params);
			$weight_sum      = saveAsPrice($cart->getWeightProducts());
			$length_sum      = 0;
			$width_sum       = 0;
			$height_sum      = 0;
			foreach ($cart->products as &$product)
			{
				if (isset($product['extra_fields'][$shipping_params['field_length']]))
				{
					$length_sum += saveAsPrice($product['extra_fields'][$shipping_params['field_length']]['value'] * $product['quantity']);
				}
				if (isset($product['extra_fields'][$shipping_params['field_width']]))
				{
					$width_sum += saveAsPrice($product['extra_fields'][$shipping_params['field_width']]['value'] * $product['quantity']);
				}
				if (isset($product['extra_fields'][$shipping_params['field_height']]))
				{
					$height_sum += saveAsPrice($product['extra_fields'][$shipping_params['field_height']]['value'] * $product['quantity']);
				}
			}
			if (isset($user_info->d_city) && !empty($user_info->d_city))
			{
				$cityto = $user_info->d_city;
			}
			else
			{
				if (isset($user_info->city) && !empty($user_info->city))
				{
					$cityto = $user_info->city;
				}
			}

			if(!strlen($cityto)){
				echo "<small>Для расчёта стоимости доставки, необходимо заполнить поле \"Город\".</small>";
				return 0;
			}

			$this->connection = new MeasoftCourierExt($shipping_params['api_user_name'], $shipping_params['api_user_pw'],
			$shipping_params['api_user_extra']);

			$calcParams = array(
				'townfrom' => $shipping_params['townfrom'],
				'townto' => $cityto,
				'service' => $params['shipping_service']
			);

			var_dump($calcParams);

			if($weight_sum > 0){
				$calcParams['mass'] = $weight_sum;
			}
			if($length_sum > 0){
				$calcParams['l'] = $length_sum;
			}
			if($width_sum > 0){
				$calcParams['w'] = $width_sum;
			}
			if($height_sum > 0){
				$calcParams['h'] = $height_sum;
			}

			//Постоянно идёт ошибка тарифа
//			$price = $this->connection->calculate($calcParams);

		}
		return 0;
	}

	function showShippingPriceForm($params, &$shipping_ext_row, &$template)
	{
		$this->form = new Form('adminForm');
		$this->form->addFormPath(dirname(__FILE__) . '/forms');
		$this->form->loadFile('shippingprice');
		$this->form->bind(['sm_params' => $params]);
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
}