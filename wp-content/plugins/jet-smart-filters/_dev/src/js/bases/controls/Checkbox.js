import Filter from 'bases/Filter';

export default class CheckboxControl extends Filter {
	constructor($filter, $checkboxs) {
		super($filter);

		this.$checkboxs = $checkboxs || $filter.find(':checkbox');
		this.relationalOperator = this.$filter.data('relational-operator');

		this.processData();
		this.initEvent();
	}

	addFilterChangeEvent() {
		this.$checkboxs.on('change', () => {
			this.processData();
			this.emitFiterChange();
		})
	}

	removeChangeEvent() {
		this.$checkboxs.off();
	}

	processData() {
		const $checked = this.$checked;
		let dataValue = false;

		if ($checked.length === 1) {
			dataValue = $checked.val();
		} else if ($checked.length > 1) {
			dataValue = [];

			$checked.each(index => {
				dataValue.push($checked.get(index).value);
			})

			if (this.relationalOperator)
				dataValue.push('operator_' + this.relationalOperator);
		}

		this.dataValue = dataValue;
	}

	setData(newData) {
		this.getItemsByValue(newData).forEach($item => {
			$item.prop('checked', true);
		});

		this.processData();
	}

	reset(value = false) {
		if (value) {
			// reset one value
			this.getItemByValue(value).prop('checked', false);
			this.processData();
		} else {
			// reset filter
			this.getItemsByValue(this.dataValue).forEach($item => {
				$item.prop('checked', false);
			});

			this.dataValue = false;
		}
	}

	get activeValue() {
		let currentData = this.data,
			activeValue = '',
			delimiter = '';

		if (!Array.isArray(currentData))
			currentData = [currentData];

		currentData.forEach(value => {
			const label = this.getValueLabel(value);

			if (label) {
				activeValue += delimiter + label;
				delimiter = ', ';
			}
		});

		return activeValue || false;
	}

	get $checked() {
		return this.$checkboxs.filter(':checked');
	}

	// Additional methods
	getItemsByValue(values) {
		const items = [];

		if (!Array.isArray(values))
			values = [values];

		values.forEach(value => {
			items.push(this.getItemByValue(value));
		});

		return items;
	}

	getItemByValue(value) {
		return this.$checkboxs.filter('[value="' + value + '"]');
	}

	getValueLabel(value) {
		return this.$checkboxs.filter('[value="' + value + '"]').data('label');
	}
}