(function($){
	$(document).ready(function() {
		/**
		 * publish panel: reloader
		 */
		var refresh = 2000;
		setInterval(function(){
			if($(".email-newsletter-manager-gui.reloadable").length > 0){
				$.ajax({
					url: location.href,
					cache: false,
					success: function(html){
						$(".email-newsletter-manager-gui").each(function(i){
							if($(this).hasClass("reloadable")){
								$(this).replaceWith($(html).find(".email-newsletter-manager-gui:eq(" + i + ")"));
							}
						});
					}
				});
			}
		}, refresh);
		/**
		 * prevent double-clicks
		 */
		$("#savesend").one("click", function() {
		    $(this).click(function(){
				return false;
			});
		});
	});
})(jQuery.noConflict());
