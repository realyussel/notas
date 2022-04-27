	jQuery(document).ready( function() {
		$(".list-group-item button").click(function (event) {
		  $(this).children().toggleClass("fa-caret-up fa-caret-down");
		  event.stopPropagation();
		  event.preventDefault();
		  $(this).parent('.list-group-item').prop('onclick', null);
		});
	} );