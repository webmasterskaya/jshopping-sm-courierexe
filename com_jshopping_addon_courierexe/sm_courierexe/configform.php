<?php
/**
 * @package    JShopping - Courierexe shipping
 * @version    __DEPLOY_VERSION__
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die();

HTMLHelper::_('formbehavior.chosen', 'select');
?>
<div class="form-horizontal">
    <div class="row-fluid">
        <div class="span12">
            <div class="row-fluid form-horizontal-desktop float-cols">
                <div class="span6">
					<?php echo $this->form->renderFieldset('settings'); ?>
                </div>
                <div class="span6">
					<?php echo $this->form->renderFieldset('connection'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<hr>
