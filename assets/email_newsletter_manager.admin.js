(function($) {
	/**
	 * ENM core interactions
	 */
	$(document).ready(function() {
		
		var html = $('html').addClass('active'),
			body = html.find('body'),
			wrapper = html.find('#wrapper'),
			header = wrapper.find('#header'),
			nav = wrapper.find('#nav'),
			navContent = nav.find('ul.content'),
			navStructure = nav.find('ul.structure'),
			session = header.find('#session'),
			context = wrapper.find('#context'),
			contents = wrapper.find('#contents'),
			form = contents.find('> form'),
			user = session.find('li:first a'),
			pagination = contents.find('ul.page');
		
		/*--------------------------------------------------------------------------
			ENM - Sender Editor (taken from admin.js)
		--------------------------------------------------------------------------*/
		contents.find('select[name="settings[gateway]"]').symphonyPickable();
		
		/*--------------------------------------------------------------------------
			ENM - Recipient group Editor (taken from admin.js)
		--------------------------------------------------------------------------*/
		if(body.is('#extensionemail_newsletter_managerrecipientgroups')) {
			var maxRecord = $('input[name*=max_records]'),
				pageNumber = $('input[name*=page_number]');

			// Update Data Source output parameter
			contents.find('input[name="fields[name]"]').on('blur.admin input.admin', function(){
				var value = $(this).val();

				if(value == '' || $('select[name="fields[param][]"]:visible').length == 0) {
					$('select[name="fields[param][]"] option').each(function(){
						var item = $(this),
							field = item.text().split('.')[1];

						item.text('$ds-' + '?' + '.' + field);
					});

					return false;
				}

				$.ajax({
					type: 'GET',
					data: { 'string': value },
					dataType: 'json',
					async: false,
					url: Symphony.Context.get('root') + '/symphony/ajax/handle/',
					success: function(result) {
						$('select[name="fields[param][]"] option').each(function(){
							var item = $(this),
								field = item.text().split('.')[1];

							item.text('$ds-' + result + '.' + field);
						});

						return false;
					}
				});
			});

			// Data source manager options
			contents.find('select.filtered > optgroup').each(function() {
				var optgroup = $(this),
					select = optgroup.parents('select'),
					label = optgroup.attr('label'),
					options = optgroup.remove().find('option').addClass('optgroup');

				// Fix for Webkit browsers to initially show the options
				if (select.attr('multiple')) {
					select.scrollTop(0);
				}

				// Show only relevant options based on context
				$('#ds-context').on('change.admin', function() {
					if($(this).find('option:selected').text() == label) {
						select.find('option.optgroup').remove();
						select.append(options.clone(true));
					}
				});
			});

			// Data source manager context
			contents.find('.contextual').each(function() {
				var area = $(this);

				$('#ds-context').on('change.admin', function() {
					var select = $(this),
						optgroup = select.find('option:selected').parent(),
						value = select.val().replace(/\W+/g, '_'),
						group = optgroup.data('label') || optgroup.attr('label').replace(/\W+/g, '_');

					// Show only relevant interface components based on context
					area[(area.hasClass(value) || area.hasClass(group)) ^ area.hasClass('inverse') ? 'removeClass' : 'addClass']('irrelevant');
				});
			});

			// Trigger the parameter name being remembered when the Datasource context changes
			contents.find('#ds-context')
				.on('change.admin', function() {
					contents.find('input[name="fields[name]"]').trigger('blur.admin');
				})
				.trigger('change.admin');

			// Once pagination is disabled, maxRecords and pageNumber are disabled too
			contents.find('input[name*=paginate_results]').on('change.admin', function(event) {

				// Turn on pagination
				if($(this).is(':checked')) {
					maxRecord.attr('disabled', false);
					pageNumber.attr('disabled', false);
				}

				// Turn off pagination
				else {
					maxRecord.attr('disabled', true);
					pageNumber.attr('disabled', true);
				}
			}).trigger('change.admin');

			// Disable paginate_results checking/unchecking when clicking on either maxRecords or pageNumber
			maxRecord.add(pageNumber).on('click.admin', function(event) {
				event.preventDefault();
			});

			// Enabled fields on submit
			form.on('submit.admin', function() {
				maxRecord.attr('disabled', false);
				pageNumber.attr('disabled', false);
			});
		}
	});
})(jQuery.noConflict());