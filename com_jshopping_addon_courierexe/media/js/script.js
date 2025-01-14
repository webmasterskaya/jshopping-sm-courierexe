/**
 * @package    JShopping - Courierexe shipping
 * @version    1.0.0-rc2
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2020 Webmasterskaya. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       https://webmasterskaya.xyz/
 * @since      1.0.0
 */

document.addEventListener('DOMContentLoaded', function() {
	initPvzLists();

	if (typeof oneStepCheckout !== 'undefined') {
		(function(shippingMethod, updateForm) {
			var showShippingFormOriginal = oneStepCheckout.showShippingForm;
			oneStepCheckout.showShippingForm = showShippingFormMy;

			function showShippingFormMy(shippingMethod, updateForm) {
				document.querySelectorAll('.shipping_form_active').
					forEach(function(el, key) {
						el.classList.remove('shipping_form_active');
					});
				showShippingFormOriginal.apply(oneStepCheckout, arguments);
				document.querySelector('#shipping_form_' + shippingMethod).
					classList.
					add('shipping_form_active');
			}
		})();

		(function(step) {
			var updateFormOriginal = oneStepCheckout.updateForm;
			oneStepCheckout.updateForm = updateFormMy;

			function updateFormMy(step) {
				step = step || 2;
				updateFormOriginal.apply(oneStepCheckout, arguments);
				if (step == 3) {
					initPvzLists();
				}
			}
		})();
	}
});

var pvzData = {};

function initPvzLists() {
	var courierexe = sm_courierexe;
	if (!!courierexe) {
		for (key in courierexe) {
			if (courierexe.hasOwnProperty(key)) {
				if (courierexe[key].show_pvz_list == '1') {
					if (courierexe[key].show_pvz_list_ajax == '0') {
						jQuery(
							'[name="params[' + key +
							'][sm_courierexe_pvz_id]"]').
							select2({
								data: pvzData[key],
								language: document.documentElement.lang.slice(0,
									2),
							});
					}
					else {
						jQuery(
							'[name="params[' + key +
							'][sm_courierexe_pvz_id]"]').
							select2({
								ajax: {
									url: Joomla.getOptions('system.paths').root + '/component/ajax/?plugin=courierexe&format=json&group=jshoppingcheckout',
									data: function() {
										var query = {};
										query['filter'] = courierexe[key].pvzParams;
										query['filter']['town'] = this.parents(
											'form').
											find('[name="params[' + key +
												'][sm_courierexe_townto]"]')[0].value;
										query['task'] = 'pvz';
										return query;
									},
									processResults: function(response) {
										return {
											results: response.data[0],
										};
									},
								},
								language: document.documentElement.lang.slice(0,
									2),
							});
					}
				}
			}
		}
	}
}