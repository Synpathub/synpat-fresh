/**
 * SynPat Store Frontend JavaScript
 * Handles AJAX interactions for wishlist and PDF generation
 * 
 * @package SynPat_Platform
 * @since 1.0.0
 */

(function($) {
	'use strict';

	const SynPatStore = {
		
		init: function() {
			this.bindEvents();
			this.loadWishlistState();
		},

		bindEvents: function() {
			$(document).on('click', '.add-to-wishlist', this.handleWishlistToggle.bind(this));
			$(document).on('click', '.remove-from-wishlist', this.handleWishlistRemove.bind(this));
			$(document).on('click', '.btn-generate-pdf', this.handlePDFGeneration.bind(this));
			$(document).on('click', '.btn-search', this.handleSearch.bind(this));
			$(document).on('keypress', '#portfolio-search', this.handleSearchKeypress.bind(this));
			$('#filter-orderby').on('change', this.handleFilterChange.bind(this));
		},

		handleWishlistToggle: function(e) {
			e.preventDefault();
			
			const $button = $(e.currentTarget);
			const portfolioId = $button.data('portfolio-id');
			const isInWishlist = $button.hasClass('in-wishlist');
			
			if (!portfolioId) {
				this.showNotification('Invalid portfolio ID', 'error');
				return;
			}

			const actionName = isInWishlist ? 'synpat_remove_from_wishlist' : 'synpat_add_to_wishlist';
			
			$button.prop('disabled', true).text('Processing...');

			$.ajax({
				url: synpatStore.ajaxUrl,
				type: 'POST',
				data: {
					action: actionName,
					portfolio_id: portfolioId,
					nonce: synpatStore.nonce
				},
				success: (response) => {
					if (response.success) {
						if (isInWishlist) {
							$button.removeClass('in-wishlist').text('Add to Wishlist');
						} else {
							$button.addClass('in-wishlist').text('Remove from Wishlist');
						}
						this.showNotification(response.data.message, 'success');
						this.updateWishlistCount();
					} else {
						this.showNotification(response.data.message || 'Operation failed', 'error');
					}
				},
				error: () => {
					this.showNotification('Network error occurred', 'error');
				},
				complete: () => {
					$button.prop('disabled', false);
				}
			});
		},

		handleWishlistRemove: function(e) {
			e.preventDefault();
			
			const $button = $(e.currentTarget);
			const portfolioId = $button.data('portfolio-id');
			const $row = $button.closest('tr');
			
			if (!confirm('Remove this portfolio from your wishlist?')) {
				return;
			}

			$.ajax({
				url: synpatStore.ajaxUrl,
				type: 'POST',
				data: {
					action: 'synpat_remove_from_wishlist',
					portfolio_id: portfolioId,
					nonce: synpatStore.nonce
				},
				success: (response) => {
					if (response.success) {
						$row.fadeOut(400, function() {
							$(this).remove();
							
							if ($('.wishlist-table tbody tr').length === 0) {
								$('.wishlist-table').replaceWith(
									'<div class="empty-wishlist">Your wishlist is empty</div>'
								);
							}
						});
						this.showNotification('Removed from wishlist', 'success');
					}
				}
			});
		},

		handlePDFGeneration: function(e) {
			e.preventDefault();
			
			const $button = $(e.currentTarget);
			const portfolioId = $button.data('portfolio-id');
			const pdfType = $button.data('pdf-type') || 'portfolio';
			
			$button.prop('disabled', true).html('<span class="spinner"></span> Generating PDF...');

			$.ajax({
				url: synpatStore.ajaxUrl,
				type: 'POST',
				data: {
					action: 'synpat_generate_' + pdfType + '_pdf',
					portfolio_id: portfolioId,
					nonce: synpatStore.nonce
				},
				success: (response) => {
					if (response.success && response.data.file_url) {
						window.location.href = response.data.file_url;
						this.showNotification('PDF generated successfully', 'success');
					} else {
						this.showNotification('Failed to generate PDF', 'error');
					}
				},
				error: () => {
					this.showNotification('PDF generation failed', 'error');
				},
				complete: () => {
					$button.prop('disabled', false).html('Generate PDF');
				}
			});
		},

		handleSearch: function(e) {
			e.preventDefault();
			const searchTerm = $('#portfolio-search').val().trim();
			
			if (searchTerm.length < 2) {
				this.showNotification('Enter at least 2 characters', 'error');
				return;
			}

			this.performSearch(searchTerm);
		},

		handleSearchKeypress: function(e) {
			if (e.which === 13) {
				e.preventDefault();
				this.handleSearch(e);
			}
		},

		performSearch: function(searchTerm) {
			$('.portfolio-grid').html('<div class="loading">Searching...</div>');

			$.ajax({
				url: synpatStore.ajaxUrl,
				type: 'POST',
				data: {
					action: 'synpat_search_portfolios',
					search: searchTerm,
					nonce: synpatStore.nonce
				},
				success: (response) => {
					if (response.success && response.data.portfolios) {
						this.renderSearchResults(response.data.portfolios);
					} else {
						$('.portfolio-grid').html('<div class="no-portfolios">No portfolios found</div>');
					}
				},
				error: () => {
					this.showNotification('Search failed', 'error');
				}
			});
		},

		renderSearchResults: function(portfolios) {
			const $grid = $('.portfolio-grid').empty();
			
			portfolios.forEach((portfolio) => {
				const card = this.createPortfolioCard(portfolio);
				$grid.append(card);
			});
		},

		createPortfolioCard: function(portfolio) {
			return `
				<div class="portfolio-card" data-portfolio-id="${portfolio.id}">
					<div class="card-header">
						<h3 class="card-title">
							<a href="${portfolio.url}">${portfolio.title}</a>
						</h3>
					</div>
					<div class="card-body">
						<div class="portfolio-metrics">
							<div class="metric-item">
								<span class="metric-label">Patents</span>
								<span class="metric-value">${portfolio.n_patents}</span>
							</div>
							<div class="metric-item">
								<span class="metric-label">Essential</span>
								<span class="metric-value">${portfolio.essnt}</span>
							</div>
							<div class="metric-item">
								<span class="metric-label">Licensees</span>
								<span class="metric-value">${portfolio.n_lic}</span>
							</div>
						</div>
						<div class="portfolio-description">${portfolio.description}</div>
					</div>
					<div class="card-footer">
						<a href="${portfolio.url}" class="btn btn-primary">View Portfolio</a>
						${this.getWishlistButton(portfolio.id)}
					</div>
				</div>
			`;
		},

		getWishlistButton: function(portfolioId) {
			if (!synpatStore.isLoggedIn) {
				return '';
			}
			
			const wishlistIds = this.getWishlistIds();
			const isInWishlist = wishlistIds.includes(portfolioId);
			const buttonClass = isInWishlist ? 'in-wishlist' : '';
			const buttonText = isInWishlist ? 'Remove from Wishlist' : 'Add to Wishlist';
			
			return `<button class="btn btn-secondary add-to-wishlist ${buttonClass}" 
				data-portfolio-id="${portfolioId}">${buttonText}</button>`;
		},

		handleFilterChange: function(e) {
			const orderBy = $(e.target).val();
			const currentUrl = new URL(window.location.href);
			currentUrl.searchParams.set('orderby', orderBy);
			window.location.href = currentUrl.toString();
		},

		loadWishlistState: function() {
			if (!synpatStore.isLoggedIn) {
				return;
			}

			$.ajax({
				url: synpatStore.ajaxUrl,
				type: 'POST',
				data: {
					action: 'synpat_get_wishlist',
					nonce: synpatStore.nonce
				},
				success: (response) => {
					if (response.success && response.data.wishlist) {
						this.updateWishlistButtons(response.data.wishlist);
					}
				}
			});
		},

		updateWishlistButtons: function(wishlistItems) {
			const wishlistIds = wishlistItems.map(item => item.portfolio_id);
			
			$('.add-to-wishlist').each(function() {
				const portfolioId = $(this).data('portfolio-id');
				if (wishlistIds.includes(portfolioId)) {
					$(this).addClass('in-wishlist').text('Remove from Wishlist');
				}
			});
		},

		updateWishlistCount: function() {
			const $counter = $('.wishlist-count');
			if ($counter.length) {
				const currentCount = parseInt($counter.text()) || 0;
				$counter.text(currentCount + 1);
			}
		},

		getWishlistIds: function() {
			const ids = [];
			$('.add-to-wishlist.in-wishlist').each(function() {
				ids.push($(this).data('portfolio-id'));
			});
			return ids;
		},

		showNotification: function(message, type) {
			const $notice = $('<div class="synpat-notice synpat-notice-' + type + '">' + message + '</div>');
			
			$('body').append($notice);
			
			setTimeout(() => {
				$notice.fadeIn(300);
			}, 100);
			
			setTimeout(() => {
				$notice.fadeOut(300, function() {
					$(this).remove();
				});
			}, 3000);
		}
	};

	$(document).ready(function() {
		SynPatStore.init();
	});

})(jQuery);
