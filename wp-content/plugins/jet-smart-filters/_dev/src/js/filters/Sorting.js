import SelectControl from 'bases/controls/Select';

export default class Sorting extends SelectControl {
	name = 'sorting';

	constructor ($container) {
		const $filter = $container.find('.jet-sorting');

		super($filter, $filter.find('.jet-sorting-select'));

		this.$container = $container;
		this.mergeSameQueryKeys = false;
	}
}