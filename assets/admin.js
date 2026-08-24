(function ($) {
	'use strict';

	function toggleBuilder() {
		var action = $('#wbcr_action').val();
		$('.wbcr-action-section').hide().filter(function () {
			return String($(this).data('action') || '').split(' ').indexOf(action) !== -1;
		}).show();

		var condition = $('#wbcr_condition_type').val();
		$('.wbcr-condition-value').hide().filter('[data-condition="' + condition + '"]').show();

		var fieldType = $('#wbcr_field_type').val();
		$('.wbcr-select-options').toggle(fieldType === 'select');
	}

	$(function () {
		toggleBuilder();
		$('#wbcr_action, #wbcr_condition_type, #wbcr_field_type').on('change', toggleBuilder);

		if ($.fn.selectWoo) {
			$(document.body).trigger('wc-enhanced-select-init');
		}
	});
})(jQuery);
