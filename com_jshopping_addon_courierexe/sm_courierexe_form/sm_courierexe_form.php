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
		$language->load('lib_measoft', JPATH_SITE, $language->getTag(), true);
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

		//var_dump($shipping_params);
		if ($shipping_params['show_pvz'])
		{
			$pvzParams = array();

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

			if (strlen($cityto) > 0)
			{
				$arrTown = $connection->citiesList(array('namecontains' => strtolower($cityto), 'country' => 1),
					array('limitcount' => 1));
				if (!empty($arrTown))
				{
					$townArr = array_shift($arrTown);
					$cityto  = $townArr['name'];
				}
				else
				{
					$cityto = '';
				}
			}

			$pvzParams['town'] = $cityto;

			if (!empty($pvzParams['town']))
			{
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

				$doc->addScriptDeclaration('var pvzParams = ' . json_encode($pvzParams,
						JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ';');

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
												'id'   => '[' . $item->code . '](' . $item->parentname . ') ' . $item->address,
												'text' => '(' . $item->parentname . ') ' . $item->address
											];
										}
										else
										{
											$pvz[] = [
												'id'   => '[' . $item->code . '] ' . $item->address,
												'text' => $item->address->__toString()
											];
										}
									}
								}
								$doc->addScriptDeclaration('var pvzData = ' . json_encode($pvz,
										JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ';');
								$doc->addScriptDeclaration('
								jQuery(document).ready(function() {
									jQuery(\'[name="params[' . $shipping_id . '][sm_courierexe_pickup]"]\').
										select2({
											data: pvzData,
										});
								});
								');
							}
						}
						catch (Exception $e)
						{
							echo '<div class="alert">Не удалось загрузить список ПВЗ</div>';
							saveToLog("courierexe.log", $e->getCode() . ' - ' . $e->getMessage());
						}
					}
					echo HTMLHelper::_('select.genericlist', [], 'params[' . $shipping_id . '][sm_courierexe_pickup]');
				}
			}
			else
			{
				echo '<div class="alert">Не удалось загрузить список ПВЗ</div>';
			}
		}
	}

	function getDisplayNameParams()
	{
		return array('sm_courierexe_form' => 'ID пункта выдачи');
	}
}