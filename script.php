<?php

use Joomla\CMS\Factory;

/**
 * @package    JShopping - Courierexe shipping
 * @version    1.0.0-rc
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */
class pkg_jshopping_courierexeInstallerScript
{

	protected $minimumPhp = '7.2';

	protected $minimumJoomla = '3.9.0';

	/**
	 * Runs just before any installation action is performed on the component.
	 * Verifications and pre-requisites should run in this function.
	 *
	 * @param   string     $type    - Type of PreFlight action. Possible values are:
	 *                              - * install
	 *                              - * update
	 *                              - * discover_install
	 * @param   \stdClass  $parent  - Parent object calling object.
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function preflight($type, $parent)
	{
		$app = JFactory::getApplication();

		// Check old Joomla!
		if (!class_exists('Joomla\CMS\Version'))
		{
			$app->enqueueMessage(JText::sprintf('PKG_JSHOPPING_COURIEREXE_ERR_COMPATIBLE_JOOMLA',
				$this->minimumJoomla), 'error');
		}

		$jversion = new JVersion();

		// Check PHP
		if (!(version_compare(PHP_VERSION, $this->minimumPhp) >= 0))
		{
			$app->enqueueMessage(JText::sprintf('PKG_JSHOPPING_COURIEREXE_ERR_COMPATIBLE_PHP',
				$this->minimumPhp), 'error');
		}

		// Check Joomla version
		if (!$jversion->isCompatible($this->minimumJoomla))
		{
			$app->enqueueMessage(JText::sprintf('PKG_JSHOPPING_COURIEREXE_ERR_COMPATIBLE_JOOMLA',
				$this->minimumJoomla), 'error');
		}
	}

	/**
	 * Runs right after any installation action is performed on the component.
	 *
	 * @param   string     $type    - Type of PostFlight action. Possible values are:
	 *                              - * install
	 *                              - * update
	 *                              - * discover_install
	 * @param   \stdClass  $parent  - Parent object calling object.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function postflight($type, $parent)
	{
		$version = $parent->get('manifest')->version->__toString();
		$db      = Factory::getDbo();

		//register JSopping shipping calc
		if (version_compare($version, '1.0.0', 'ge') && $type == 'install')
		{

			$query = $db->getQuery(true);

			$columns = array(
				'name',
				'alias',
				'description',
				'params',
				'shipping_method',
				'published',
				'ordering'
			);
			$values  = array(
				$db->quote('"Курьерская служба 2008"'),
				$db->quote('sm_courierexe'),
				$db->quote('Подключение к программе доставки "Курьерская служба 2008"'),
				$db->quote(''),
				$db->quote(''),
				1,
				1
			);

			$query
				->insert($db->quoteName('#__jshopping_shipping_ext_calc'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));

			$db->setQuery($query);
			$db->execute();
		}

		//enable courierexe plugin
		if (version_compare($version, '1.0.0', 'ge'))
		{
			$query = $db->getQuery(true);

			$query
				->update($db->quoteName('#__extensions'))
				->set($db->quoteName('enabled') . '=1')
				->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
				->where($db->quoteName('element') . ' = ' . $db->quote('courierexe'))
				->where($db->quoteName('folder') . ' = ' . $db->quote('jshoppingcheckout'));

			$db->setQuery($query);
			$db->execute();
		}
	}
}
