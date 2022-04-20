import Filter from 'bases/Filter';
import eventBus from 'includes/event-bus';

export default class ButtonRemove extends Filter {
	name = 'button-remove';

	constructor($container) {
		const $filter = $container.find('.jet-remove-all-filters__button');

		super($filter);

		this.$container = $container.find('.jet-remove-all-filters');
		this.$filter.on('click', () => {
			this.emitFitersRemove();
		});

		// Event subscriptions
		eventBus.subscribe('activeItems/change', (activeItems, provider, queryId) => {
			if (!this.isCurrentProvider({ provider, queryId }) && !this.isAdditionalProvider({ provider, queryId }))
				return;

			if (activeItems.length) {
				this.show();
			} else {
				this.hide();
			}
		});
	}
}