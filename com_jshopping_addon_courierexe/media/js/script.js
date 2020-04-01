document.addEventListener('DOMContentLoaded', function() {
	initPvzLists();

	if (!!oneStepCheckout) {
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
				if (step == 2) {
					console.log(step);
					initPvzLists();
				}
			}
		})();
	}
});

var pvzData = {};

function initPvzLists() {
	var courierexe = Joomla.getOptions('sm_courierexe');
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
									url: '/component/ajax/?plugin=courierexe&format=json&group=jshoppingcheckout',
									data: function() {
										var query = {};
										query['filter'] = courierexe[key].pvzParams;
										query['filter']['town'] = this.parents(
											'form').
											find('[name="city"]')[0].value;
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