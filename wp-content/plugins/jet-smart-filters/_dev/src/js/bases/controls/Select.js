import Filter from 'bases/Filter';

export default class SelectControl extends Filter {
	constructor ($filter, $select) {
		super($filter);

		this.$select = $select || $filter.find('select');

		this.processData();
		this.initEvent();
	}

	addFilterChangeEvent() {
		this.$select.on('change', () => {
			this.processData();
			this.emitFiterChange();
		})
	}

	removeChangeEvent() {
		this.$select.off();
	}

	processData() {
		this.dataValue = this.$selected.val();
	}

	setData(newData) {
		const $item = this.getItemByValue(newData);

		if ($item) {
			$item.prop(this.isSelect ? 'selected' : 'checked', true);
			this.processData();
		}
	}

	reset() {
		this.dataValue = false;
		this.$selected.prop(this.isSelect ? 'selected' : 'checked', false);
	}

	get activeValue() {
		const $item = this.getItemByValue(this.data);

		if ($item)
			return $item.data('label');
	}

	get $selected() {
		return this.isSelect ?
			this.$select.find(':checked') :
			this.$select.filter(':checked');

	}

	get isSelect() {
		return this.$select.prop('tagName') === 'SELECT' ? true : false;
	}

	// Additional methods
	getItemByValue(value) {
		let $item = false;

		if (this.isSelect) {
			this.$select.find('option').each((index, item) => {
				const $option = $(item);

				if ($option.val() === value)
					$item = $option
			});
		} else {
			$item = this.$select.filter('[value="' + value + '"]');
		}

		return $item;
	}
}