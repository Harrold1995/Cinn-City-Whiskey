import filtersUI from 'includes/filters-ui';

export default {
	initFiltersUI() {
		const widgets = {
			'jet-smart-filters-range.default': this.range,
			'jet-smart-filters-date-range.default': this.dateRange
		};

		for (const widget in widgets) {
			const callback = widgets[widget];

			window.elementorFrontend.hooks.addAction('frontend/element_ready/' + widget, callback.bind(this));
		}
	},

	range($scope) {
		filtersUI.range.init({
			$container: $scope
		});
	},

	dateRange($scope) {
		filtersUI.dateRange.init({
			id: $scope.data('id'),
			$container: $scope
		});
	},
};