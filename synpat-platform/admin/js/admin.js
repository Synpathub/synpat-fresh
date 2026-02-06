/**
 * SynPat Admin JavaScript
 * 
 * @package SynPat_Platform
 * @since 1.0.0
 */

(function($) {
	'use strict';

	const SynPatAdmin = {
		
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			$('.synpat-confirm-action').on('click', this.handleConfirmAction.bind(this));
		},

		handleConfirmAction: function(e) {
			const message = $(e.currentTarget).data('confirm') || synpatBackend.i18n.confirmDeletion;
			
			if (!confirm(message)) {
				e.preventDefault();
				return false;
			}
		},

		showNotice: function(message, type) {
			const $notice = $('<div class="synpat-notice synpat-notice-' + type + '">' + message + '</div>');
			$('body').append($notice);
			$notice.fadeIn(300);
			
			setTimeout(function() {
				$notice.fadeOut(300, function() {
					$(this).remove();
				});
			}, 3000);
		}
	};

	$(document).ready(function() {
		SynPatAdmin.init();
	});

})(jQuery);
