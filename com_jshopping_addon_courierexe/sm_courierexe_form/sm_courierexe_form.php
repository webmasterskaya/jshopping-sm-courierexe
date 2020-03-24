<?php

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper as HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * @package    JShopping - Courierexe shipping
 * @version    __DEPLOY_VERSION__
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

JLoader::register('MeasoftCourier', JPATH_LIBRARIES . '/measoft/MeasoftCourier.php');
require_once JPATH_SITE . '/components/com_jshopping/lib/functions.php';

class sm_courierexe_form extends ShippingFormRoot
{
	function showForm($shipping_id, $shippinginfo, $params)
	{
		$app      = Factory::getApplication();
		$doc      = $app->getDocument();
		$language = Factory::getLanguage();
		$language->load('com_jshopping_addon_courierexe', JPATH_ADMINISTRATOR, $language->getTag(), true);
		$connection = MeasoftCourier::getInstance();

		$shipping_params = unserialize($shippinginfo->params);

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

		if (!$shippinginfo->calculeprice)
		{
			echo '<div class="alert"><h5>' . Text::_('COM_JSHOPPING_ADDON_COURIEREXE_ERROR_PRICE_CALC_TITLE') . '</h5>' . Text::_('COM_JSHOPPING_ADDON_COURIEREXE_ERROR_PRICE_CALC_DESCRIPTION') . '</div>';
		}

		//var_dump($shipping_params);
		if ($shipping_params['show_pvz'])
		{
			echo '<input type="hidden" name="params[' . $shipping_id . '][sm_courierexe]" value="true" />';

			$pvzParams = array();

			$pvzParams['town'] = $cityto;

			if (isset($shipping_params['acceptcash']))
			{
				$pvzParams['acceptcash'] = $shipping_params['acceptcash'];
			}

			if (isset($shipping_params['acceptcard']))
			{
				$pvzParams['acceptcard'] = $shipping_params['acceptcard'];
			}

			if (isset($shipping_params['acceptfitting ']))
			{
				$pvzParams['acceptfitting '] = $shipping_params['acceptfitting '];
			}

			if (isset($shipping_params['acceptindividuals ']))
			{
				$pvzParams['acceptindividuals '] = $shipping_params['acceptindividuals '];
			}

			echo "<script>" . 'var pvzParams = ' . json_encode($pvzParams,
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ';' . "</script>";

			if ($shipping_params['show_pvz_list'])
			{
				HTMLHelper::_('stylesheet', 'com_jshopping_addon_courierexe/select2.min.css',
					array('relative' => true, 'version' => 'auto'));
				HTMLHelper::_('script', 'com_jshopping_addon_courierexe/select2.min.js',
					array('relative' => true, 'version' => 'auto'));

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
							echo "<script>" . 'var pvzData = ' . json_encode($pvz,
									JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ';' . "</script>";
							$doc->addScriptDeclaration('
								jQuery(document).ready(function() {
									jQuery(\'[name="params[' . $shipping_id . '][sm_courierexe_pvz_id]"]\').
										select2({
											data: pvzData,
										});
								});
								');
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
		return ['sm_courierexe_pvz_id' => 'Код ПВЗ в системе:',
			'sm_courierexe_pvz_name' => 'Наименование ПВЗ:',
			'sm_courierexe_pvz_parentname' => 'Наименование родительского элемента:',
			'sm_courierexe_pvz_address' => 'Адрес ПВЗ:',
			];
	}
}