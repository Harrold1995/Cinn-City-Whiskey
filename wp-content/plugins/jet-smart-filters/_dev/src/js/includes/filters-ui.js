import {
	getNesting
} from 'includes/utility';

const filtersUI = {
	range: {
		inputSelector: '.jet-range__input',
		sliderSelector: '.jet-range__slider',
		sliderMinSelector: '.jet-range__values-min',
		sliderMaxSelector: '.jet-range__values-max',
		init: props => {
			const {
				$container = false,
				$rangeInput = $rangeInput || $container.find(filtersUI.range.inputSelector),
				$slider = $slider || $container.find(filtersUI.range.sliderSelector),
				$sliderMin = $sliderMin || $container.find(filtersUI.range.sliderMinSelector),
				$sliderMax = $sliderMax || $container.find(filtersUI.range.sliderMaxSelector)
			} = props,
				format = $slider.data('format') || {
					'thousands_sep': '',
					'decimal_sep': '',
					'decimal_num': 0,
				};

			$slider.slider({
				range: true,
				min: $slider.data('min'),
				max: $slider.data('max'),
				step: $slider.data('step'),
				values: $slider.data('defaults'),
				slide: (event, ui) => {
					$rangeInput.val(ui.values[0] + ':' + ui.values[1]);

					$sliderMin.html(ui.values[0].jetFormat(
						format.decimal_num,
						3,
						format.thousands_sep,
						format.decimal_sep
					));

					$sliderMax.html(ui.values[1].jetFormat(
						format.decimal_num,
						3,
						format.thousands_sep,
						format.decimal_sep
					));
				},
				stop: (event, ui) => {
					$rangeInput.trigger('change');
				},
			});
			//$slider.trigger('jet-smart-filters/init-slider', [slider]);
		}
	},
	dateRange: {
		inputSelector: '.jet-date-range__input',
		submitSelector: '.jet-date-range__submit',
		fromSelector: '.jet-date-range__from',
		toSelector: '.jet-date-range__to',
		init: props => {
			const {
				id = false,
				$container = false,
				$dateRangeInput = $dateRangeInput || $container.find(filtersUI.dateRange.inputSelector),
				$dateRangeFrom = $dateRangeFrom || $container.find(filtersUI.dateRange.fromSelector),
				$dateRangeTo = $dateRangeTo || $container.find(filtersUI.dateRange.toSelector)
			} = props,
				weekStart = getNesting(JetSmartFilterSettings, 'misc', 'week_start') || 1,
				texts = getNesting(JetSmartFilterSettings, 'datePickerData');

			const from = $dateRangeFrom.datepicker({
				defaultDate: '+1w',
				closeText: texts.closeText,
				prevText: texts.prevText,
				nextText: texts.nextText,
				currentText: texts.currentText,
				monthNames: texts.monthNames,
				monthNamesShort: texts.monthNamesShort,
				dayNames: texts.dayNames,
				dayNamesShort: texts.dayNamesShort,
				dayNamesMin: texts.dayNamesMin,
				weekHeader: texts.weekHeader,
				firstDay: parseInt(weekStart, 10),
				beforeShow: function (textbox, instance) {
					if (id) {
						const $calendar = instance.dpDiv;

						$calendar.addClass('jet-smart-filters-datepicker-' + id);
					}
				}
			}).on('change', () => {
				const fromVal = $dateRangeFrom.val(),
					toVal = $dateRangeTo.val();

				if (fromVal || toVal) {
					$dateRangeInput.val(fromVal + ':' + toVal);
				} else {
					$dateRangeInput.val('');
				}

				to.datepicker('option', 'minDate', getDate(fromVal));
			});

			const to = $dateRangeTo.datepicker({
				defaultDate: '+1w',
				closeText: texts.closeText,
				prevText: texts.prevText,
				nextText: texts.nextText,
				currentText: texts.currentText,
				monthNames: texts.monthNames,
				monthNamesShort: texts.monthNamesShort,
				dayNames: texts.dayNames,
				dayNamesShort: texts.dayNamesShort,
				dayNamesMin: texts.dayNamesMin,
				weekHeader: texts.weekHeader,
				firstDay: parseInt(weekStart, 10),
				beforeShow: function (textbox, instance) {
					if (id) {
						const $calendar = instance.dpDiv;

						$calendar.addClass('jet-smart-filters-datepicker-' + id);
					}
				}
			}).on('change', () => {
				const fromVal = $dateRangeFrom.val(),
					toVal = $dateRangeTo.val();

				if (fromVal || toVal) {
					$dateRangeInput.val(fromVal + ':' + toVal);
				} else {
					$dateRangeInput.val('');
				}

				from.datepicker('option', 'maxDate', getDate(toVal));
			});

			function getDate(dateString) {
				const dateFormat = 'mm/dd/yy';
				let date;

				try {
					date = $.datepicker.parseDate(dateFormat, dateString);
				} catch (error) {
					date = null;
				}

				return date;
			}
		}
	}
};

export default filtersUI;

/**
 * Extend default number object with format function	
 *	
 * @param integer n: length of decimal
 * @param integer x: length of whole part
 * @param mixed   s: sections delimiter
 * @param mixed   c: decimal delimiter
 */
Number.prototype.jetFormat = function (n, x, s, c) {
	var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\D' : '$') + ')',
		num = this.toFixed(Math.max(0, ~~n));
	return (c ? num.replace('.', c) : num).replace(new RegExp(re, 'g'), '$&' + (s || ''));
};