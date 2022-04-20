import {
	isFunction
} from 'includes/utility';

export default class CustomProvider {
	customProviders = ['jet-engine-maps'];
	customAjaxRequests = {
		'jet-engine-maps': this.jetEngineMapsAjaxRequest
	}

	constructor (filterGroup) {
		this.filterGroup = filterGroup;
		this.filterGroup.isCustomProvider = this.customProviders.includes(this.filterGroup.provider);
	}

	ajaxRequest() {
		const ajaxRequestFn = this.customAjaxRequests[this.filterGroup.provider];

		if (!isFunction(ajaxRequestFn))
			return;

		ajaxRequestFn.call(this);
	}

	jetEngineMapsAjaxRequest() {
		this.filterGroup.ajaxRequest(response => {
			this.filterGroup.$provider
				.closest('.elementor-widget-jet-engine-maps-listing')
				.trigger('jet-filter-custom-content-render', response);
		});
	}
}