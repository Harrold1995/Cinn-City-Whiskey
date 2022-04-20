import CheckboxControl from 'bases/controls/Checkbox';

export default class CheckBoxes extends CheckboxControl {
	name = 'check-boxes';

	constructor ($container) {
		const $filter = $container.find('.jet-checkboxes-list');

		super($filter);

		this.$container = $container;
		this.mergeSameQueryKeys = true;
	}
}