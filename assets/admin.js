(function($) {
	$(document).ready(function() {
		var duplicator = $('ol#duplicator');
		duplicator.symphonyDuplicator({
			minimum:0,
			collapsible:true,
			orderable:false
		});
		duplicator.bind('collapsestop', function(event, item) {
               var instance = $(item);
               instance.find('.header > span:not(:has(i))').append(
                       $('<i />').text(instance.find('label:first input').attr('value'))
               );
       });
       duplicator.bind('expandstop', function(event, item) {
               $(item).find('.header > span > i').remove();
       });
	});
})(jQuery.noConflict());