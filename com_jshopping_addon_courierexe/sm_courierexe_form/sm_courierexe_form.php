<?php
/**
 * @package    JShopping - Courierexe shipping
 * @version    1.0.0-rc2
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2020 Webmasterskaya. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       https://webmasterskaya.xyz/
 * @since      1.0.0
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper as HTMLHelper;
use Joomla\CMS\Language\Text;

JLoader::register('MeasoftCourier', JPATH_LIBRARIES . '/measoft/MeasoftCourier.php');
require_once JPATH_SITE . '/components/com_jshopping/lib/functions.php';

class sm_courierexe_form extends ShippingFormRoot
{
	function showForm($shipping_id, $shippinginfo, $params)
	{
		$doc      = Factory::getDocument();
		$language = Factory::getLanguage();
		$language->load('com_jshopping_addon_courierexe', JPATH_ADMINISTRATOR, $language->getTag(), true);
		$connection = MeasoftCourier::getInstance();

		$shipping_params = unserialize($shippinginfo->params);

		HTMLHelper::_('jquery.framework');

		if ($shipping_params['show_pvz'])
		{
			if ($shipping_params['show_pvz_list'])
			{
				HTMLHelper::_('stylesheet', 'com_jshopping_addon_courierexe/select2.min.css',
					array('relative' => true, 'version' => 'auto'));
				HTMLHelper::_('script', 'com_jshopping_addon_courierexe/select2.min.js',
					array('relative' => true, 'version' => 'auto'));
				HTMLHelper::_('script',
					'com_jshopping_addon_courierexe/i18n/' . substr($language->getTag(), 0, 2) . '.js',
					array('relative' => true, 'version' => 'auto'));
			}
		}

		HTMLHelper::_('script', 'com_jshopping_addon_courierexe/script.js',
			array('relative' => true, 'version' => 'auto'));

		$pvzParams = array();

		if (isset($shipping_params['acceptcash']))
		{
			$pvzParams['acceptcash'] = $shipping_params['acceptcash'];
		}

		if (isset($shipping_params['acceptcard']))
		{
			$pvzParams['acceptcard'] = $shipping_params['acceptcard'];
		}

		if (isset($shipping_params['acceptfitting']))
		{
			$pvzParams['acceptfitting'] = $shipping_params['acceptfitting'];
		}

		if (isset($shipping_params['acceptindividuals ']))
		{
			$pvzParams['acceptindividuals '] = $shipping_params['acceptindividuals '];
		}

		if ($shipping_params['show_pvz'])
		{
			$arParams = [
				$shipping_id => [
					'pvzParams'          => $pvzParams,
					'show_pvz_list'      => $shipping_params['show_pvz_list'],
					'show_pvz_list_ajax' => $shipping_params['show_pvz_list_ajax'],
				]
			];
			echo "<script>var sm_courierexe = " . json_encode($arParams,
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . "</script>";
		}

		$user = &JFactory::getUser();
		if ($user->id)
		{
			$user_info = &JSFactory::getUserShop();
		}
		else
		{
			$user_info = &JSFactory::getUserShopGuest();
		}

		$cityto = '';

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

		if (empty($cityto))
		{
			echo '<div class="alert"><h5>' . Text::_('COM_JSHOPPING_ADDON_COURIEREXE_ERROR_PRICE_CALC_TITLE') . '</h5>' . Text::_('COM_JSHOPPING_ADDON_COURIEREXE_ERROR_CITY_NOT_SPECIFIED_DESCRIPTION') . '</div>';

			return;
		}
		else
		{
			echo '<input type="hidden" name="params[' . $shipping_id . '][sm_courierexe_townto]" id="params_' . $shipping_id . '_sm_courierexe_townto" value="' . $cityto . '" />';
		}

		if (!$shippinginfo->calculeprice)
		{
			echo '<div class="alert"><h5>' . Text::_('COM_JSHOPPING_ADDON_COURIEREXE_ERROR_PRICE_CALC_TITLE') . '</h5>' . Text::_('COM_JSHOPPING_ADDON_COURIEREXE_ERROR_PRICE_CALC_DESCRIPTION') . '</div>';
		}

		echo '<input type="hidden" name="params[' . $shipping_id . '][sm_courierexe]" value="true" />';

		if ($shipping_params['show_pvz'])
		{
			if ($shipping_params['show_pvz_list'])
			{
				$pvzParams['town'] = $cityto;

				if (!$shipping_params['show_pvz_list_ajax'])
				{
					try
					{
						$pvzList = $connection->servicesPvzlist($pvzParams);
						$pvz     = [];

						if ($pvzList)
						{
							foreach ($pvzList as $item)
							{
								if (!empty($item->code) && !empty($item->address))
								{
									if (!empty($item->parentname))
									{
										$pvz[] = [
											'id'   => $item->code->__toString(),
											'text' => '(' . $item->parentname->__toString() . ') ' . $item->address->__toString()
										];
									}
									else
									{
										$pvz[] = [
											'id'   => $item->code->__toString(),
											'text' => $item->address->__toString()
										];
									}
								}
							}
							echo '<script>pvzData[' . $shipping_id . '] = ' . json_encode($pvz,
									JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ';</script>';
						}
					}
					catch (Exception $e)
					{
						echo '<div class="alert alert-error">' . Text::_('COM_JSHOPPING_ADDON_COURIEREXE_ERROR_PVZ_LIST_NOT_LOADED_DESCRIPTION') . '</div>';
						saveToLog("courierexe.log", $e->getCode() . ' - ' . $e->getMessage());

						return;
					}
				}
				echo HTMLHelper::_('select.genericlist', [], 'params[' . $shipping_id . '][sm_courierexe_pvz_id]');
			}
		}
	}

	function getDisplayNameParams()
	{
		return [
			'sm_courierexe_pvz_id'         => 'Код ПВЗ в системе',
			'sm_courierexe_pvz_name'       => 'Наименование ПВЗ',
			'sm_courierexe_pvz_parentname' => 'Наименование родительского элемента',
			'sm_courierexe_pvz_address'    => 'Адрес ПВЗ',
		];
	}
}