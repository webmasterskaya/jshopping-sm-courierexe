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
?>
<div class="form-horizontal">
    <div class="row-fluid">
        <div class="span12">
            <div class="row-fluid form-horizontal-desktop float-cols">
                <div class="span6">
					<?php echo $this->form->renderFieldset('settings'); ?>
                    <div class="control-group">
                        <div><h4>Тип оплаты заказа получателем</h4></div>
                    </div>
                    <div class="control-group">
                        <div class="alert alert-info">
                            <h4>Свяжите ваши методы оплаты с методами службы доставки</h4>
                            <ul>
                                <li><b>CASH</b> - Наличными при получении (по-умолчанию)</li>
                                <li><b>CARD</b> - Картой при получении</li>
                                <li><b>NO</b> - Без оплаты. Этот тип оплаты передается, если заказ уже оплачен и не
                                    требует инкассации. API добавит к товарам строку предоплаты в сумму заказа, чтобы
                                    общая сумма была 0, однако в кассовом чеке будут все товары с ценами, и оплата
                                    предоплатой, как того требует 54-ФЗ.
                                </li>
                                <li><b>OTHER</b> - Прочее (Предусмотрен для того, чтобы оплата поступала непосредственно
                                    в курьерскую службу посредством прочих типов оплаты - таких как: вебмани, яденьги,
                                    картой на сайте, прочие платежные системы и т.д.)
                                </li>
                            </ul>
                        </div>
                    </div>
					<?php foreach ($paymentMethods as $paymentMethod): ?>
                        <div class="control-group">
                            <div class="control-label">
                                <label
                                        id="params_pay_systems_<?php echo $paymentMethod->payment_id; ?>-lbl"
                                        for="params_pay_systems_<?php echo $paymentMethod->payment_id; ?>">
									<?php echo $paymentMethod->name; ?>
                                </label>
                            </div>
                            <div class="controls">
								<?php echo HTMLHelper::_('select.genericlist', [
									HTMLHelper::_('select.option', 'CASH', 'Наличными при получении'),
									HTMLHelper::_('select.option', 'CARD', 'Картой при получении'),
									HTMLHelper::_('select.option', 'NONE', 'Без оплаты'),
									HTMLHelper::_('select.option', 'OTHER', 'Прочее ')
								], 'params[pay_systems][' . $paymentMethod->payment_id . ']', [], 'value', 'text',
									!empty($config['pay_systems'][$paymentMethod->payment_id]) ? $config['pay_systems'][$paymentMethod->payment_id] : 'PAYMENT_TYPE_CASH',
									'params_pay_systems_' . $paymentMethod->payment_id); ?>
                            </div>
                        </div>
					<?php endforeach; ?>
                </div>
                <div class="span6">
					<?php echo $this->form->renderFieldset('connection'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<hr>
