function titleRefetch( keyword, sig , id ) {
	var base_url = window.location.origin;
	$.ajax({
		type: "POST",
		url: base_url + '/yourls-api.php',
		data:{
			signature: sig,
			action:'refetch', 
			target:'title-force',
			shorturl: keyword,
			format:'json'
		},
		success: function(data) {
			var here = window.location.href;
			var cite = '#url-' + id;
			$( cite ).fadeOut('fast', function() {
				$( cite ).load( here + ' ' + cite);
				$( cite ).fadeIn('fast');
				});
			feedback(data.message, 'success');
		},
 		error: function(response){
			feedback(data.message, 'fail');
		}
	});
	return false;
}
