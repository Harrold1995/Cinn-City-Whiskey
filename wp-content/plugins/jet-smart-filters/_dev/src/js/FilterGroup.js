import AdditionalFilters from 'modules/AdditionalFilters';
import CustomProvider from 'modules/CustomProvider';
import Indexer from 'modules/Indexer';
import eventBus from 'includes/event-bus';
import request from 'includes/request';
import preloader from 'includes/preloader';
import {
	isEmpty,
	isNotEmpty,
	isEqual,
	getNesting,
	getUrlParams,
	mergeData
} from 'includes/utility';

export default class FilterGroup {
	urlPrefix = 'jet-smart-filters';
	activeItemsExceptions = ['sorting', 'pagination'];

	constructor(provider, queryId, filters, queryData = false) {
		this.provider = provider;
		this.queryId = queryId;
		this.filters = filters;
		this.$provider = $(this.providerSelector);

		this.currentQuery = Object.assign({}, this.urlParams, queryData);
		this.currentHashQuery = {};

		// Init modules
		this.additionalFilters = new AdditionalFilters(this);
		this.customProvider = new CustomProvider(this);
		this.initIndexer();

		// Event subscriptions
		eventBus.subscribe('fiter/change', filter => {
			if (!this.isCurrentProvider(filter))
				return;

			this.filterChangeHandler(filter.applyType);
		});
		eventBus.subscribe('fiters/apply', applyFilter => {
			if (!this.isCurrentProvider(applyFilter))
				return;

			this.applyFiltersHandler(applyFilter.applyType, applyFilter.redirect && applyFilter.redirectPath ? applyFilter.redirectPath : false);
		});
		eventBus.subscribe('fiters/remove', removeFilter => {
			if (!this.isCurrentProvider(removeFilter))
				return;

			this.removeFiltersHandler(removeFilter.applyType);
		});
		eventBus.subscribe('pagination/change', paginationFilter => {
			if (!this.isCurrentProvider(paginationFilter))
				return;

			this.paginationСhangeHandler(paginationFilter.applyType, paginationFilter.topOffset);
		});

		preloader.subscribe(this.$provider, {
			provider,
			queryId,
			updateSelector: 'replace' === this.providerSelectorData.action ? true : false
		});

		// After initialization
		setTimeout(() => {
			// update filters with current data
			this.setFiltersData();

			this.currentQuery = this.query
		});
	}

	// Events Handlers
	filterChangeHandler(applyType) {
		this.resetFiltersByName('pagination');
		this.apply(applyType);
	}

	applyFiltersHandler(applyType, redirectPath = false) {
		this.resetFiltersByName('pagination');
		this.updateFiltersData();

		if (redirectPath) {
			this.doRedirect(redirectPath, applyType);
		} else {
			this.apply(applyType);
		}
	}

	removeFiltersHandler(applyType) {
		this.resetFiltersByName('pagination');
		this.resetFilters();
		this.apply(applyType);
	}

	paginationСhangeHandler(applyType, topOffset = 0) {
		this.apply(applyType);

		// scroll to provider
		if (applyType !== 'reload')
			$('html, body').stop().animate({ scrollTop: this.$provider.offset().top - topOffset }, 500);
	}

	// Actions
	apply(applyType = 'ajax') {
		this.emitActiveItems();

		if (applyType === 'reload') {
			this.doReload();
		} else {
			this.doAjax();
		}
	}

	doRedirect(url, applyType = 'ajax') {
		if (applyType === 'reload') {
			request.redirectWithGET(url, this.urlQuery);
		} else {
			const params = {
				[this.urlPrefix]: this.providerKey,
				...this.query
			};

			request.redirectWithPOST(url, params);
		}
	}

	doReload() {
		request.reload(this.urlQuery);
	}

	doAjax() {
		const query = this.query;

		if (isEqual(query, this.currentQuery))
			return;

		this.currentQuery = query;
		this.updateHash();
		if (this.isCustomProvider) {
			this.customProvider.ajaxRequest();
		} else {
			this.ajaxRequest(response => {
				this.ajaxRequestCompleted(response);
			});
		}
	}

	ajaxRequest(callback, query = this.query) {
		this.startAjaxLoading();

		request.ajax({
			query: query,
			provider: this.provider,
			queryId: this.queryId,
		}).then(response => {
			callback(response);
			this.endAjaxLoading();
		}).catch(error => {
			if (!error)
				return;

			console.error(error);
			this.endAjaxLoading();
		});
	}

	startAjaxLoading() {
		eventBus.publish('ajaxFilters/start-loading', this.provider, this.queryId);
	}

	endAjaxLoading() {
		eventBus.publish('ajaxFilters/end-loading', this.provider, this.queryId);
	}

	ajaxRequestCompleted(response) {
		// update pagination props
		if (response.pagination && getNesting(JetSmartFilterSettings, 'props', this.provider, this.queryId)) {
			window.JetSmartFilterSettings.props[this.provider][this.queryId] = {
				...response.pagination
			}
		}

		// update indexed data
		if (response.jetFiltersIndexedData && getNesting(JetSmartFilterSettings, 'jetFiltersIndexedData')) {
			window.JetSmartFilterSettings.jetFiltersIndexedData = Object.assign(window.JetSmartFilterSettings.jetFiltersIndexedData, response.jetFiltersIndexedData);
		}

		/* // update indexed data
		if (!this.additionalRequest && response.jetFiltersIndexedData && getNesting(JetSmartFilterSettings, 'jetFiltersIndexedData')) {
			window.JetSmartFilterSettings.jetFiltersIndexedData = response.jetFiltersIndexedData;
		} */

		// update provider content
		this.renderResult(response.content);

		eventBus.publish('ajaxFilters/updated', this.provider, this.queryId);
	}

	renderResult(result) {
		if (!this.$provider.length)
			return;

		if ('insert' === this.providerSelectorData.action) {
			this.$provider.html(result);
		} else {
			this.$provider.replaceWith(result);
			this.$provider = $(this.$provider.selector);
		}

		// trigger elementor widgets
		switch (this.provider) {
			case 'jet-engine':
				window.elementorFrontend.hooks.doAction('frontend/element_ready/jet-listing-grid.default', this.$provider, $);
				break;

			case 'epro-portfolio':
				window.elementorFrontend.hooks.doAction('frontend/element_ready/portfolio.default', this.$provider, $);
				break;
		}

		this.$provider.find('[data-element_type]').each((index, item) => {
			const $this = $(item);
			let elementType = $this.data('element_type');

			if ('widget' === elementType) {
				elementType = $this.data('widget_type');
				window.elementorFrontend.hooks.doAction('frontend/element_ready/widget', $this, $);
			}

			window.elementorFrontend.hooks.doAction('frontend/element_ready/global', $this, $);
			window.elementorFrontend.hooks.doAction('frontend/element_ready/' + elementType, $this, $);
		});

		// emit rendered event
		eventBus.publish('provider/content-rendered', this.provider, this.$provider);
		// for backward compatibility with other plugins
		$(document).trigger('jet-filter-content-rendered', [this.$provider, this, this.provider, this.queryId]);
	}

	queryToUrl(query) {
		let urlQuery = '';

		if (isEmpty(query))
			return urlQuery;

		const queryData = {
			...query
		};

		for (var key in queryData) {
			if (urlQuery.length) {
				urlQuery += '&';
			}

			if (Array.isArray(queryData[key])) {
				if (queryData[key].length) {
					urlQuery += key + '[]=' + queryData[key].join('&' + key + '[]=');
				}
			} else {
				urlQuery += key + '=' + queryData[key];
			}
		}

		return encodeURI('?' + this.urlPrefix + '=' + this.providerKey + '&' + urlQuery);
	}

	setFiltersData(data = this.currentQuery) {
		this.filters.forEach(filter => {
			const key = filter.queryKey,
				value = data[key];

			if (value)
				if (!filter.isHierarchy) {
					if (filter.setData)
						filter.setData(value);
				} else {
					filter.dataValue = value;
				}
		});

		this.emitActiveItems();
		this.emitHierarchyFiltersUpdate();
	}

	updateFiltersData() {
		this.filters.forEach(filter => {
			if (filter.processData)
				filter.processData();
		});
	}

	resetFilters() {
		this.filters.forEach(filter => {
			if (filter.reset)
				filter.reset();
		});
	}

	getFiltersByName(name) {
		return this.filters.filter(filter => {
			return filter.name === name;
		});
	}

	resetFiltersByName(name) {
		const filters = this.getFiltersByName(name);

		filters.forEach(filter => {
			if (filter.reset)
				filter.reset();
		});
	}

	// Hash methods
	updateHash() {
		let hashHasBeenChanged = false;

		this.filters.forEach(filter => {
			if (filter.isMixed || filter.isReload) {
				hashHasBeenChanged = true;
				this.addToHash(filter);
			}
		});

		if (hashHasBeenChanged) {
			this.updateHashInAddressBar();
		}
	}

	updateHashInAddressBar() {
		let urlHash = this.urlHash;

		if (!urlHash) {
			urlHash = window.location.pathname;
		}

		history.replaceState(null, null, urlHash);
	}

	addToHash(filter) {
		const data = filter.data,
			key = filter.queryKey;

		if (data) {
			this.currentHashQuery[key] = data;
		} else {
			if (this.currentHashQuery.hasOwnProperty(key)) {
				delete this.currentHashQuery[key];
			}
		}
	}

	// module initialization
	initIndexer() {
		const indexedClass = 'jet-filter-indexed';

		this.filters.forEach(filter => {
			if (filter.$container && filter.$container.hasClass(indexedClass)) {
				// Init Indexer Class
				filter.indexer = new Indexer(filter);
			}
		});
	}

	// emitters
	emitActiveItems() {
		eventBus.publish('activeItems/change', this.activeItems, this.provider, this.queryId);
	}

	emitHierarchyFiltersUpdate() {
		eventBus.publish('hierarchyFilters/update', this.hierarchyFilters);
	}

	isCurrentProvider(filter) {
		return filter.provider === this.provider && filter.queryId === this.queryId ? true : false;
	}

	// Getters
	get query() {
		const query = {};

		this.filters.forEach(filter => {
			const data = filter.data,
				key = filter.queryKey;

			if (!data || !key)
				return;

			if (query[key] && filter.mergeSameQueryKeys) {
				query[key] = mergeData(query[key], data, 'operator_AND');
			} else {
				query[key] = data;
			}
		});

		return query;
	}

	get urlQuery() {
		return this.queryToUrl(this.query);
	}

	get urlHash() {
		return this.queryToUrl(this.currentHashQuery);
	}

	get providerKey() {
		return this.provider + '/' + this.queryId;
	}

	get providerSelectorData() {
		return getNesting(JetSmartFilterSettings, 'selectors', this.provider);
	}

	get providerSelector() {
		const delimiter = this.providerSelectorData.inDepth ? ' ' : '';

		return 'default' === this.queryId ? this.providerSelectorData.selector : this.providerSelectorData.idPrefix + this.queryId + delimiter + this.providerSelectorData.selector;
	}

	get urlParams() {
		const urlParams = getUrlParams();

		if (urlParams[this.urlPrefix] !== this.providerKey)
			return false;

		delete urlParams[this.urlPrefix];

		return urlParams;
	}

	get activeItems() {
		return this.filters.filter(filter => {
			return filter.data && filter.reset && !this.activeItemsExceptions.includes(filter.name);
		});
	}

	get hierarchyFilters() {
		const hierarchyFilters = {};

		this.filters.forEach(filter => {
			if (filter.isHierarchy) {
				if (!hierarchyFilters[filter.filterId])
					hierarchyFilters[filter.filterId] = [];

				hierarchyFilters[filter.filterId].push(filter);
			}
		});

		return isNotEmpty(hierarchyFilters) ? hierarchyFilters : false;
	}

	// backup methods
	/* checkStorage() {
		let storageData = storage.get(this.providerKey, true);
		if (!storageData || !isObject(storageData))
			return;
		storageData = Object.assign(this.currentQuery, storageData);
		this.ajaxRequest(storageData);
		this.setFiltersData(storageData);
	} */
}