$(function() {
	// Minimalized migration of newsletter.js for Vite bundling
	// Note: tinymce initialization remains in blade via script include for now
	$('.newsletter').on('click', '.add-section', function(e) {
		e.preventDefault();
		var button = $(this);
		var newsletter = button.data('newsletter-id');
		var seq = button.data('next-section');
		$.get('/newsletter/'+newsletter+'/create-section', {
			'seq': seq
		}).done(function(response) {
			if(response.success) {
				$('.section').last().after($(response.html));
				button.data('next-section', seq+1);
			} else {
				alert('Error');
			}
		}).fail(function(){alert('Error');});
	});

});
