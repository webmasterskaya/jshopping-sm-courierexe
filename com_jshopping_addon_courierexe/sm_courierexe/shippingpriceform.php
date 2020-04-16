<?php
/**
 * @package    JShopping - Courierexe shipping
 * @version    1.0.0-rc2
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2020 Webmasterskaya. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 *  @link       https://webmasterskaya.xyz/
 * @since      1.0.0
 */

use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die();

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('jquery.framework');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('script', 'com_jshopping_addon_courierexe/jquery.autocomplete.js',
	array('relative' => true, 'version' => 'auto'));
HTMLHelper::_('script', 'com_jshopping_addon_courierexe/admin-script.js',
	array('relative' => true, 'version' => 'auto'));
HTMLHelper::_('stylesheet', 'com_jshopping_addon_courierexe/admin-style.css',
	array('relative' => true, 'version' => 'auto'));

?>
<div class="form-horizontal">
    <div class="span6">
		<?php echo $this->form->renderFieldset('shippingprice'); ?>
    </div>
</div>