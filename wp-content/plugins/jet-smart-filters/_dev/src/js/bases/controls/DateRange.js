import Filter from 'bases/Filter';
import filtersUI from 'includes/filters-ui';

export default class DateRangeControl extends Filter {
	dateRangeInputSelector = filtersUI.dateRange.inputSelector;
	dateRangeSubmitSelector = filtersUI.dateRange.submitSelector;
	dateRangeFromSelector = filtersUI.dateRange.fromSelector;
	dateRangeToSelector = filtersUI.dateRange.toSelector;

	constructor ($filter, $dateRangeInput, $dateRangeSubmit, $dateRangeFrom, $dateRangeTo) {
		super($filter);

		this.$dateRangeInput = $dateRangeInput || $filter.find(this.dateRangeInputSelector);
		this.$dateRangeSubmit = $dateRangeSubmit || $filter.find(this.dateRangeSubmitSelector);
		this.$dateRangeFrom = $dateRangeFrom || $filter.find(this.dateRangeFromSelector);
		this.$dateRangeTo = $dateRangeTo || $filter.find(this.dateRangeToSelector);

		this.initDateRangeUI();
		this.processData();
		this.addFilterChangeEvent();
	}

	initDateRangeUI() {
		filtersUI.dateRange.init({
			id: this.$filter.closest('.elementor-widget-jet-smart-filters-date-range').data('id'),
			$dateRangeInput: this.$dateRangeInput,
			$dateRangeFrom: this.$dateRangeFrom,
			$dateRangeTo: this.$dateRangeTo
		});
	}

	addFilterChangeEvent() {
		this.$dateRangeSubmit.on('click', () => {
			this.processData();
			this.emitFiterChange();
		})
	}

	removeChangeEvent() {
		this.$dateRangeSubmit.off();
	}

	processData() {
		this.dataValue = this.$dateRangeInput.val();
	}

	setData(newData) {
		this.$dateRangeInput.val(newData);

		const data = newData.split(':');
		if (data[0])
			this.$dateRangeFrom.val(data[0]);
		if (data[1])
			this.$dateRangeTo.val(data[1]);

		this.processData();
	}

	reset() {
		this.dataValue = false;
		this.$dateRangeInput.val('');
		this.$dateRangeFrom.val('');
		this.$dateRangeFrom.datepicker('option', 'maxDate', null);
		this.$dateRangeTo.val('');
		this.$dateRangeTo.datepicker('option', 'minDate', null);
	}

	get activeValue() {
		return this.dataValue.replace(/^:/, '∞ — ').replace(/:$/, ' — ∞').replace(':', ' — ');
	}
}