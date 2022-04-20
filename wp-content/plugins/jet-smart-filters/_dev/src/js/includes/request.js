import {
	objectSlice,
	someIsFalse,
	getNesting,
	isValidUrl
} from 'includes/utility';

export default {
	xhrs: {},

	ajax(data) {
		return new Promise((resolve, reject) => {
			const requestData = {},
				url = data.url || getNesting(JetSmartFilterSettings, 'ajaxurl'),
				action = data.action || 'jet_smart_filters',
				query = data.query || false,
				paged = objectSlice(query, 'jet_paged'),
				provider = data.provider || false,
				queryId = data.queryId || 'default',
				props = data.props || getNesting(JetSmartFilterSettings, 'props', provider, queryId) || {},
				defaults = data.defaults || getNesting(JetSmartFilterSettings, 'queries', provider, queryId) || {},
				settings = data.settings || getNesting(JetSmartFilterSettings, 'settings', provider, queryId) || {};

			if (someIsFalse([url, action, query, provider, queryId])) {
				reject('Not enough parameters. Check if the "Provider" and "Query ID" are set correctly');
				return;
			}

			if (this.xhrs[provider + '/' + queryId]) {
				this.xhrs[provider + '/' + queryId].abort();
			}

			requestData.action = action;
			requestData.provider = provider + '/' + queryId;
			requestData.query = query;
			requestData.defaults = defaults;
			requestData.settings = settings;
			requestData.props = props;

			if (paged > 1) {
				requestData.paged = paged;
			}

			this.xhrs[provider + '/' + queryId] = $.ajax({
				url: url,
				type: 'POST',
				dataType: 'json',
				data: requestData,
			}).done(function (response) {
				resolve(response);
			}).fail(function (jqXHR, exception) {
				if (exception === 'abort') {
					reject(false);
				}

				let msg = '';

				if (jqXHR.status === 0) {
					msg = 'Not connect.\n Verify Network.';
				} else if (jqXHR.status == 404) {
					msg = 'Requested page not found. [404]';
				} else if (jqXHR.status == 500) {
					msg = 'Internal Server Error [500].';
				} else if (exception === 'parsererror') {
					msg = 'Requested JSON parse failed.';
				} else if (exception === 'timeout') {
					msg = 'Time out error.';
				} else {
					msg = 'Uncaught Error.\n' + jqXHR.responseText;
				}

				reject(msg);
			});
		});
	},

	reload(urlParams) {
		if (urlParams) {
			document.location.search = urlParams;
		} else {
			document.location = window.location.pathname;
		}
	},

	redirectWithGET(url, getParams) {
		if (!url)
			return;

		if (!isValidUrl(url))
			url = getNesting(JetSmartFilterSettings, 'siteurl') + '/' + url;

		url += url.endsWith('/') ? '' : '/';

		window.location.replace(url + getParams);
	},

	redirectWithPOST(url, postParams) {
		if (!url)
			return;

		if (!isValidUrl(url))
			url = getNesting(JetSmartFilterSettings, 'siteurl') + '/' + url;

		const $form = $('<form></form>').attr('method', 'post').attr('action', url);

		postParams['jet-smart-filters-redirect'] = 1;

		$.each(postParams, function (key, value) {
			if (Array.isArray(value)) {
				value.forEach(valueItem => {
					$form.append(getField(key + '[]', valueItem));
				});
			} else {
				$form.append(getField(key, value));
			}
		});

		$($form).appendTo('body').submit();

		function getField(key, value) {
			const $field = $('<input></input>');

			$field.attr('type', 'hidden');

			$field.attr('name', key);
			$field.attr('value', value);

			return $field;
		}
	}
}