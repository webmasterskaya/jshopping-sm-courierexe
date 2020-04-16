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
	jQuery('[name="sm_params[townfrom]"]').autocomplete({
		serviceUrl: '/component/ajax/?plugin=courierexe&format=json&group=jshoppingcheckout',
		minChars: 3,
		deferRequestBy: 300,
		params: {
			task: 'cities',
		},
		paramName: 'city_name',
		transformResult: function(response) {
			try {
				result = JSON.parse(response);
			}
			catch (e) {
				result = false;
				console.error('Not JSON Response');
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
});