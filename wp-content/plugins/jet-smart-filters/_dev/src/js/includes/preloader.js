import eventBus from 'includes/event-bus';

const preloader = {
	init() {
		this.subscribers = [];

		// Event subscriptions
		eventBus.subscribe('ajaxFilters/start-loading', (provider, queryId) => {
			this.action(this.currentElements(provider, queryId), 'show');
		});
		eventBus.subscribe('ajaxFilters/end-loading', (provider, queryId) => {
			this.action(this.currentElements(provider, queryId), 'hide');
		});
	},

	subscribe($element, props) {
		const {
			provider = false,
			queryId = 'default',
			preloaderClass = 'jet-filters-loading',
			updateSelector = false
		} = props;

		if (!provider)
			return;

		this.subscribers.push({
			$el: $element,
			provider,
			queryId,
			preloaderClass,
			updateSelector
		});
	},

	action(elements, action) {
		elements.forEach(element => {
			const {
				$el,
				preloaderClass,
				updateSelector
			} = element;

			switch (action) {
				case 'show':
					$el.addClass(preloaderClass);
					break;

				case 'hide':
					$el.removeClass(preloaderClass);

					if (updateSelector)
						element.$el = $($el.selector);

					break;
			}
		});
	},

	currentElements(provider, queryId) {
		return this.subscribers.filter(element => {
			return element.provider === provider && element.queryId === queryId;
		});
	},
}

export default preloader;