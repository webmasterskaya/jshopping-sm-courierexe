/**
 * @package    JShopping - Courierexe shipping
 * @version    __DEPLOY_VERSION__
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

jQuery(function() {
	dadataSuggestionsInit();
});

function dadataSuggestionsInit() {
	var dadataParams = {
		token: Joomla.getOptions('sm_courierexe_dadata').token,
		type: 'ADDRESS',
		onSelect: function(suggestion) {
			window.lastValue = suggestion.value;
			if (this.name.substr(0, 2) == '_d') {
				dataSetFromSuggestion(suggestion.data, 'd_');
			}
			else {
				dataSetFromSuggestion(suggestion.data, '');
			}
		},
		onSelectNothing: function(query) {
			if (this.value != '') {
				this.value = window.lastValue ? window.lastValue : '';
			} else {
				if (this.name.substr(0, 2) == '_d') {
					dataSetFromSuggestion({}, 'd_');
				}
				else {
					dataSetFromSuggestion({}, '');
				}
			}
		},
	};

	var dadataAddressField = jQuery(
		'#' + Joomla.getOptions('sm_courierexe_dadata').element),
		dadataDAddressField = jQuery(
			'#d_' + Joomla.getOptions('sm_courierexe_dadata').element);

	dadataAddressField.suggestions(dadataParams);
	dadataDAddressField.suggestions(dadataParams);

	dadataAddressField.on('focus', function() {
		window.lastValueD = this.value;
	});
	dadataDAddressField.on('focus', function() {
		window.lastValueD = this.value;
	});
}

function dataSetFromSuggestion(data, prefix) {
	jQuery('[name="'+prefix+'zip"]').val(data['postal_code'] ? data['postal_code'] : '');
	jQuery('[name="'+prefix+'state"]').val(data['region_with_type'] ? data['region_with_type'] : '');
	jQuery('[name="'+prefix+'city"]').val(data['city_with_type'] ? data['city_with_type'] : '');
	jQuery('[name="'+prefix+'street"]').val(data['street_with_type'] ? data['street_with_type'] : '');
	var home = '';
	if(data['house']){
		home += ' ' + data['house'];
	}
	if(data['block_type']){
		home += ' ' + data['block_type'];
	}
	if(data['block']){
		home += ' ' + data['block'];
	}
	jQuery('[name="'+prefix+'home"]').val(jQuery.trim(home));
	var apartment = '';
	if(data['flat_type']){
		apartment += ' ' + data['flat_type'];
	}
	if(data['flat']){
		apartment += ' ' + data['flat'];
	}
	jQuery('[name="'+prefix+'apartment"]').val(jQuery.trim(apartment));
}