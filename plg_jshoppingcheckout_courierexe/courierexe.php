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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die();

JLoader::register('MeasoftCourier', JPATH_LIBRARIES . '/measoft/MeasoftCourier.php');

class plgJshoppingcheckoutCourierexe extends CMSPlugin
{
	protected $connection;
	protected $sm_config;

	public function __construct(&$subject, $config = array())
	{
		$this->connection = MeasoftCourier::getInstance();
		$db               = Factory::getDbo();
		$query            = $db->getQuery(true);
		$query->select($db->quoteName('params'))
			->from($db->quoteName('#__jshopping_shipping_ext_calc'))
			->where($db->quoteName('alias') . ' = ' . $db->quote('sm_courierexe'));
		$this->sm_config = unserialize($db->setQuery($query)->loadResult());
		parent::__construct($subject, $config);
	}

	public function onBeforeCreateOrder(&$order, &$cart, &$checkout)
	{
		$shipping_params_data = $order->getShippingParamsData();

		if (!$shipping_params_data['sm_courierexe'])
		{
			return;
		}

		$shipping_method        = $checkout->getShippingMethod();
		$shipping_method_params = unserialize($checkout->getShippingMethodPrice()->params);

		$city = array_shift($this->getCitiesList(!empty($order->d_city) ? $order->d_city : $order->city, 1));

		if (!empty($shipping_params_data['sm_courierexe_pvz_id']))
		{
			$shippingForm = $shipping_method->getShippingForm();

			$filter = ['town' => $city['name'], 'code' => $shipping_params_data['sm_courierexe_pvz_id']];
			$pvz    = array_shift($this->getPvzList($filter));

			$shipping_params_data['sm_courierexe_pvz_name']       = $pvz->name->__toString();
			$shipping_params_data['sm_courierexe_pvz_parentname'] = $pvz->parentname->__toString();
			$shipping_params_data['sm_courierexe_pvz_address']    = $pvz->address->__toString();

			if ($shippingForm)
			{
				$shippingForm->setParams($shipping_params_data);
				$shipping_params_names  = $shippingForm->getDisplayNameParams();
				$order->shipping_params = getTextNameArrayValue($shipping_params_names, $shipping_params_data);
			}

			$order->setShippingParamsData($shipping_params_data);
		}

		$newOrder = [
			'orderno'         => $order->order_number,
			'person'          => implode(' ', [$order->d_f_name . ' ' . $order->d_l_name . ' ' . $order->d_m_name]),
			'phone'           => implode(', ', [$order->d_email, $order->d_phone, $order->d_mobil_phone]),
			'town'            => $city['fiascode'],
			'address'         => implode(' ',
				[$order->d_street, $order->d_home, $order->d_apartment]),
			'weight'          => saveAsPrice($cart->getWeightProducts()),
			'service'         => $shipping_method_params['shipping_service'],
			'price'           => $order->order_total,
			//Настроить связь типов оплаты и методов оплаты
			'paytype'         => 'CASH',
			'deliveryprice'   => $order->jshop_price_shipping,
			'acceptpartially' => 'NO'
		];

		$products = [];
		foreach ($cart->products as $product)
		{
			$products[] = [
				'item'     => $product['product_name'],
				'quantity' => $product['quantity'],
				'mass'     => $product['weight'],
				'retprice' => $product['price'],
				'type'     => 1,
				'extcode'  => $product['ean']
			];
		}

		if (!$this->setOrder($newOrder, $products))
		{
			saveToLog('courierexe.log', 'Не удалось оформить заказ');
		}
	}

	protected function getCitiesList($str, $limit = 10)
	{
		if (strlen($str) > 0)
		{
			$result = $this->connection->citiesList(array('namecontains' => strtolower($str), 'country' => 1),
				array('limitcount' => $limit));
		}

		return !empty($result) ? $result : [];
	}

	protected function getPvzList($filter)
	{
		return $this->connection->servicesPvzlist($filter);
	}

	protected function setOrder($data, $items)
	{
		return $this->connection->orderCreate($data, $items);
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

	public function onBeforeDisplayCheckoutStep2View(&$view)
	{
		if (!$this->sm_config['use_dadata'])
		{
			return;
		}

		if (empty($this->sm_config['dadata_key']))
		{
			return;
		}

		if (empty($this->sm_config['dadata_field']))
		{
			return;
		}

		if (empty($view->config_fields[$this->sm_config['dadata_field']]))
		{
			return;
		}

		$view->config_fields[$this->sm_config['dadata_field']]['display'] = 1;

		$other_fields_name = ['zip', 'state', 'city', 'street', 'home', 'apartment'];

		switch ($this->sm_config['dadata_hide_other'])
		{
			case 1:
				foreach ($other_fields_name as $field)
				{
					$view->config_fields[$field]['display']        = 0;
					$view->config_fields['d_' . $field]['display'] = 0;

					$view->_tmpl_address_html_4 .= PHP_EOL . '<input type="hidden" id="' . $field . '" name="' . $field . '" value="' . $view->user->{$field} . '" />';
					$view->_tmpl_address_html_7 .= PHP_EOL . '<input type="hidden" id="d_' . $field . '" name="d_' . $field . '" value="' . $view->user->{'d_' . $field} . '" />';
				}
				break;
			case 2:
				foreach ($other_fields_name as $field)
				{
					$view->config_fields[$field]['display']        = 1;
					$view->config_fields['d_' . $field]['display'] = 1;
				}
				break;
			default:
				foreach ($other_fields_name as $field)
				{
					if ($view->config_fields[$field]['display'] == 0)
					{
						$view->_tmpl_address_html_4 .= PHP_EOL . '<input type="hidden" id="' . $field . '" name="' . $field . '" value="' . $view->user->{$field} . '" />';
					}
					if ($view->config_fields['d_' . $field]['display'] == 0)
					{
						$view->_tmpl_address_html_7 .= PHP_EOL . '<input type="hidden" id="d_' . $field . '" name="d_' . $field . '" value="' . $view->user->{'d_' . $field} . '" />';
					}
				}
				break;
		}

		HTMLHelper::_('behavior.keepalive');
		HTMLHelper::_('jquery.framework');
		HTMLHelper::_('script', 'com_jshopping_addon_courierexe/jquery.suggestions.js', ['relative'=>true, 'version'=>'auto']);
		HTMLHelper::_('script', 'com_jshopping_addon_courierexe/dadata-helper.js', ['relative'=>true, 'version'=>'auto']);
		HTMLHelper::_('stylesheet', 'com_jshopping_addon_courierexe/suggestions.css', ['relative'=>true, 'version'=>'auto']);

		$app = Factory::getApplication();
		$doc = $app->getDocument();

		$doc->addScriptOptions('sm_courierexe_dadata', [
			'token' => $this->sm_config['dadata_key'],
			'element' => $this->sm_config['dadata_field']
		]);
	}
}