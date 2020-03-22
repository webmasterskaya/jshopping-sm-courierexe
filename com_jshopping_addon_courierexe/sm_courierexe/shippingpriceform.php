<?php
/**
 * @package    JShopping - Courierexe shipping
 * @version    __DEPLOY_VERSION__
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('script', 'com_jshopping_addon_courierexe/autocomplite.js',
	array('relative' => true, 'version' => 'auto'));
?>
    <div class="form-horizontal">
		<?php echo $this->form->renderFieldset('shippingprice'); ?>
    </div>
<?php


$app = Factory::getApplication();
$doc = $app->getDocument();
$doc->addStyleDeclaration('
    .autocomplete-suggestions {
        border: 1px solid #999;
        background: #FFF;
        overflow: auto;
    }

    .autocomplete-suggestion {
        padding: 2px 5px;
        white-space: nowrap;
        overflow: hidden;
    }

    .autocomplete-selected {
        background: #F0F0F0;
    }

    .autocomplete-suggestions strong {
        font-weight: normal;
        color: #3399FF;
    }

    .autocomplete-group {
        padding: 2px 5px;
    }

    .autocomplete-group strong {
        display: block;
        border-bottom: 1px solid #000;
    }
    .jshop_edit fieldset.adminform label {
        display: inline-block;
    }');
$doc->addScriptDeclaration('
    document.addEventListener(\'DOMContentLoaded\', function() {
		jQuery(\'[name="sm_params[townfrom]"]\').autocomplete({
			serviceUrl: \'/component/ajax/?plugin=courierexe&format=json&group=jshoppingcheckout\',
			minChars: 3,
			deferRequestBy: 300,
			params: {
				task: \'cities\',
			},
			paramName: \'city_name\',
			transformResult: function(response) {
				try {
					result = JSON.parse(response);
				}
				catch (e) {
					result = false;
					console.error(\'Not JSON Response\');
				}
				return {
					suggestions: jQuery.map(result.data[0], function(dataItem) {
						return {
							value: dataItem.name,
						};
					}),
				};
			},
		});
	});');