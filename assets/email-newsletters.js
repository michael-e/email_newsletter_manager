/**
 * jQuery magic
 *
 * @package Email Newsletters
 * @author Michael Eichelsdoerfer
 */

jQuery(document).ready(function($) {
	/**
	 * publish panel: reloader
	 */
	var refresh = 2000;
	setInterval(function(){
		if($(".email-newsletters-gui.reloadable").length > 0){
			$.ajax({
				url: location.href,
				cache: false,
				success: function(html){
					$(".email-newsletters-gui").each(function(i){
						if($(this).hasClass("reloadable")){
							$(this).replaceWith($(html).find(".email-newsletters-gui:eq(" + i + ")"));
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