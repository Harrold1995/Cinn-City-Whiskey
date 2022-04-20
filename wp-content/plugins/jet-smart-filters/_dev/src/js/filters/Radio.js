import SelectControl from 'bases/controls/Select';

export default class Radio extends SelectControl {
	name = 'radio';

	constructor ($container) {
		const $filter = $container.find('.jet-radio-list');

		super($filter, $filter.find(':radio'));

		this.$container = $container;
		this.mergeSameQueryKeys = true;
	}
}