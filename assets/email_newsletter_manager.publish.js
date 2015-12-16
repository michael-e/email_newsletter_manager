(function($) {
	$(document).ready(function() {
		/**
		 * publish panel: reloader
		 */
		setInterval(function() {
			$('.field-email_newsletter_manager .reloadable').each(function() {
				gui = $(this);
				$.ajax({
					url: Symphony.Context.get('symphony')  + '/extension/email_newsletter_manager/publishfield/' + $(this).parent().attr('id').substring(6) + '/' + Symphony.Context.get('env').entry_id  + '/',
					cache: false,
					context: gui,
					dataType: 'html',
					success: function(html) {
						//console.log($(html).$('field'));
						//$(this).parent().html($(html).find('field').html());
						$(this).parent().empty().html($(html).contents());
					}
				});
			});
		}, 2000);
		/**
		 * prevent double-clicks
		 */
		$("button").one("click", function() {
		    $(this).click(function() {
				return false;
			});
		});
	});
})(jQuery.noConflict());
