/**
 * SynPat Pro Tools Admin JavaScript
 * 
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

(function($) {
	'use strict';

	const SynPatPro = {
		
		/**
		 * Initialize the admin interface
		 */
		init: function() {
			this.bindEvents();
			this.initClaimChartBuilder();
			this.initPriorArtSearch();
			this.initAnalysisTools();
			this.initExpertReports();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			// Claim chart actions
			$(document).on('click', '.synpat-create-claim-chart', this.createClaimChart.bind(this));
			$(document).on('click', '.synpat-save-claim-chart', this.saveClaimChart.bind(this));
			
			// Prior art actions
			$(document).on('click', '.synpat-search-prior-art', this.searchPriorArt.bind(this));
			$(document).on('click', '.synpat-add-prior-art', this.addPriorArt.bind(this));
			
			// Analysis actions
			$(document).on('click', '.synpat-run-analysis', this.runAnalysis.bind(this));
			$(document).on('change', '.synpat-analysis-type', this.handleAnalysisTypeChange.bind(this));
			
			// Expert report actions
			$(document).on('click', '.synpat-generate-report', this.generateReport.bind(this));
			$(document).on('click', '.synpat-save-report', this.saveReport.bind(this));
			
			// Confirmation dialogs
			$(document).on('click', '.synpat-confirm-action', this.handleConfirmAction.bind(this));
		},

		/**
		 * Initialize Claim Chart Builder
		 */
		initClaimChartBuilder: function() {
			const $builder = $('.synpat-claim-chart-builder');
			if (!$builder.length) return;

			// Add draggable functionality for claim elements
			$('.synpat-claim-element').draggable({
				handle: '.claim-element-handle',
				containment: 'parent'
			});
		},

		/**
		 * Create new claim chart
		 */
		createClaimChart: function(e) {
			e.preventDefault();
			
			const $button = $(e.currentTarget);
			const patentId = $button.data('patent-id');
			
			if (!patentId) {
				this.showNotice('Please select a patent first', 'error');
				return;
			}

			// Show claim chart creation modal/form
			this.showClaimChartForm(patentId);
		},

		/**
		 * Save claim chart via AJAX
		 */
		saveClaimChart: function(e) {
			e.preventDefault();
			
			const $form = $(e.currentTarget).closest('form');
			const formData = $form.serialize();
			
			this.showLoading($form);
			
			$.ajax({
				url: synpatPro.ajaxUrl,
				type: 'POST',
				data: {
					action: 'synpat_pro_create_claim_chart',
					nonce: synpatPro.nonce,
					data: formData
				},
				success: (response) => {
					this.hideLoading($form);
					
					if (response.success) {
						this.showNotice('Claim chart saved successfully', 'success');
						this.refreshClaimChartList();
					} else {
						this.showNotice(response.data.message || 'Failed to save claim chart', 'error');
					}
				},
				error: () => {
					this.hideLoading($form);
					this.showNotice('An error occurred. Please try again.', 'error');
				}
			});
		},

		/**
		 * Initialize Prior Art Search
		 */
		initPriorArtSearch: function() {
			const $search = $('.synpat-prior-art-search');
			if (!$search.length) return;

			// Auto-complete for patent numbers if jQuery UI is available
			if ($.fn.autocomplete) {
				$('.synpat-patent-search-input').autocomplete({
					source: (request, response) => {
						$.ajax({
							url: synpatPro.ajaxUrl,
							data: {
								action: 'synpat_pro_search_patents',
								nonce: synpatPro.nonce,
								term: request.term
							},
							success: (data) => {
								response(data.data);
							}
						});
					},
					minLength: 2
				});
			}
		},

		/**
		 * Search for prior art
		 */
		searchPriorArt: function(e) {
			e.preventDefault();
			
			const $form = $(e.currentTarget).closest('form');
			const searchTerm = $form.find('.synpat-prior-art-query').val();
			
			if (!searchTerm) {
				this.showNotice('Please enter a search term', 'error');
				return;
			}
			
			const $resultsContainer = $('.synpat-prior-art-results');
			this.showLoading($resultsContainer);
			
			$.ajax({
				url: synpatPro.ajaxUrl,
				type: 'POST',
				data: {
					action: 'synpat_pro_search_prior_art',
					nonce: synpatPro.nonce,
					query: searchTerm,
					filters: $form.serialize()
				},
				success: (response) => {
					this.hideLoading($resultsContainer);
					
					if (response.success) {
						this.displayPriorArtResults(response.data);
					} else {
						this.showNotice(response.data.message || 'Search failed', 'error');
					}
				},
				error: () => {
					this.hideLoading($resultsContainer);
					this.showNotice('An error occurred during search', 'error');
				}
			});
		},

		/**
		 * Display prior art search results
		 */
		displayPriorArtResults: function(results) {
			const $container = $('.synpat-prior-art-results');
			$container.empty();
			
			if (!results || results.length === 0) {
				$container.html('<p>No prior art found matching your search criteria.</p>');
				return;
			}
			
			results.forEach((item) => {
				const $item = $('<div class="synpat-prior-art-item">')
					.html(`
						<h4>${this.escapeHtml(item.title)}</h4>
						<p>${this.escapeHtml(item.description)}</p>
						<div class="synpat-prior-art-meta">
							<span class="patent-number">${this.escapeHtml(item.patent_number)}</span>
							<span class="filing-date">${this.escapeHtml(item.filing_date)}</span>
						</div>
						<button class="synpat-pro-btn synpat-add-prior-art" data-id="${item.id}">
							Add to Report
						</button>
					`);
				$container.append($item);
			});
		},

		/**
		 * Initialize Analysis Tools
		 */
		initAnalysisTools: function() {
			const $analysis = $('.synpat-analysis-dashboard');
			if (!$analysis.length) return;

			// Make analysis options selectable
			$('.synpat-analysis-option').on('click', function() {
				$(this).toggleClass('selected').siblings().removeClass('selected');
			});
		},

		/**
		 * Run patent analysis
		 */
		runAnalysis: function(e) {
			e.preventDefault();
			
			const $button = $(e.currentTarget);
			const analysisType = $('.synpat-analysis-option.selected').data('type');
			const patentId = $button.data('patent-id');
			
			if (!analysisType) {
				this.showNotice('Please select an analysis type', 'error');
				return;
			}
			
			if (!patentId) {
				this.showNotice('Please select a patent', 'error');
				return;
			}
			
			const $resultsContainer = $('.synpat-analysis-results');
			this.showLoading($resultsContainer);
			
			$.ajax({
				url: synpatPro.ajaxUrl,
				type: 'POST',
				data: {
					action: 'synpat_pro_run_analysis',
					nonce: synpatPro.nonce,
					patent_id: patentId,
					analysis_type: analysisType
				},
				success: (response) => {
					this.hideLoading($resultsContainer);
					
					if (response.success) {
						this.displayAnalysisResults(response.data);
						this.showNotice('Analysis completed successfully', 'success');
					} else {
						this.showNotice(response.data.message || 'Analysis failed', 'error');
					}
				},
				error: () => {
					this.hideLoading($resultsContainer);
					this.showNotice('An error occurred during analysis', 'error');
				}
			});
		},

		/**
		 * Display analysis results
		 */
		displayAnalysisResults: function(data) {
			const $container = $('.synpat-analysis-results');
			$container.html(data.html || '<p>Analysis complete. No results to display.</p>');
		},

		/**
		 * Handle analysis type change
		 */
		handleAnalysisTypeChange: function(e) {
			const analysisType = $(e.currentTarget).val();
			$('.synpat-analysis-params').hide();
			$(`.synpat-analysis-params[data-type="${analysisType}"]`).show();
		},

		/**
		 * Initialize Expert Reports
		 */
		initExpertReports: function() {
			const $reports = $('.synpat-expert-report-editor');
			if (!$reports.length) return;

			// Rich text editor initialization if available
			if (typeof wp !== 'undefined' && wp.editor) {
				$('.synpat-report-section textarea').each(function() {
					const id = $(this).attr('id');
					if (id) {
						wp.editor.initialize(id, {
							tinymce: {
								wpautop: true,
								plugins: 'lists,link,paste,textcolor',
								toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink'
							},
							quicktags: true
						});
					}
				});
			}
		},

		/**
		 * Generate expert report
		 */
		generateReport: function(e) {
			e.preventDefault();
			
			const $button = $(e.currentTarget);
			const reportType = $button.data('report-type');
			const patentId = $button.data('patent-id');
			
			this.showLoading($button.closest('.synpat-expert-report-editor'));
			
			$.ajax({
				url: synpatPro.ajaxUrl,
				type: 'POST',
				data: {
					action: 'synpat_pro_generate_report',
					nonce: synpatPro.nonce,
					patent_id: patentId,
					report_type: reportType
				},
				success: (response) => {
					this.hideLoading($('.synpat-expert-report-editor'));
					
					if (response.success) {
						this.populateReportFields(response.data);
						this.showNotice('Report generated successfully', 'success');
					} else {
						this.showNotice(response.data.message || 'Failed to generate report', 'error');
					}
				},
				error: () => {
					this.hideLoading($('.synpat-expert-report-editor'));
					this.showNotice('An error occurred generating the report', 'error');
				}
			});
		},

		/**
		 * Populate report fields with generated content
		 */
		populateReportFields: function(data) {
			$.each(data, (field, value) => {
				const $field = $(`[name="${field}"]`);
				if ($field.length) {
					$field.val(value);
					// Trigger editor update if TinyMCE
					if (typeof wp !== 'undefined' && wp.editor && wp.editor.getEditor($field.attr('id'))) {
						wp.editor.getEditor($field.attr('id')).setContent(value);
					}
				}
			});
		},

		/**
		 * Save expert report
		 */
		saveReport: function(e) {
			e.preventDefault();
			
			const $form = $(e.currentTarget).closest('form');
			const formData = new FormData($form[0]);
			formData.append('action', 'synpat_pro_save_report');
			formData.append('nonce', synpatPro.nonce);
			
			this.showLoading($form);
			
			$.ajax({
				url: synpatPro.ajaxUrl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: (response) => {
					this.hideLoading($form);
					
					if (response.success) {
						this.showNotice('Report saved successfully', 'success');
					} else {
						this.showNotice(response.data.message || 'Failed to save report', 'error');
					}
				},
				error: () => {
					this.hideLoading($form);
					this.showNotice('An error occurred saving the report', 'error');
				}
			});
		},

		/**
		 * Refresh claim chart list
		 */
		refreshClaimChartList: function() {
			const $list = $('.synpat-claim-chart-list');
			if (!$list.length) return;
			
			$.ajax({
				url: synpatPro.ajaxUrl,
				data: {
					action: 'synpat_pro_get_claim_charts',
					nonce: synpatPro.nonce
				},
				success: (response) => {
					if (response.success) {
						$list.html(response.data.html);
					}
				}
			});
		},

		/**
		 * Show claim chart form
		 */
		showClaimChartForm: function(patentId) {
			// Implementation would show a modal or expand a form
			// For now, just show a notice
			this.showNotice('Claim chart builder opened for patent ' + patentId, 'success');
		},

		/**
		 * Handle confirmation dialogs
		 */
		handleConfirmAction: function(e) {
			const message = $(e.currentTarget).data('confirm') || 'Are you sure you want to proceed?';
			
			if (!confirm(message)) {
				e.preventDefault();
				return false;
			}
		},

		/**
		 * Show loading state
		 */
		showLoading: function($element) {
			$element.addClass('synpat-pro-loading');
		},

		/**
		 * Hide loading state
		 */
		hideLoading: function($element) {
			$element.removeClass('synpat-pro-loading');
		},

		/**
		 * Show notification message
		 */
		showNotice: function(message, type) {
			type = type || 'info';
			
			const $notice = $('<div class="synpat-pro-notice synpat-pro-notice-' + type + '">')
				.text(message)
				.hide();
			
			$('.wrap').prepend($notice);
			$notice.slideDown(300);
			
			setTimeout(() => {
				$notice.slideUp(300, function() {
					$(this).remove();
				});
			}, 4000);
		},

		/**
		 * Escape HTML for safe display
		 */
		escapeHtml: function(text) {
			if (!text) return '';
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
	};

	// Initialize when document is ready
	$(document).ready(() => {
		SynPatPro.init();
	});

	// Expose to global scope if needed
	window.SynPatPro = SynPatPro;

})(jQuery);
