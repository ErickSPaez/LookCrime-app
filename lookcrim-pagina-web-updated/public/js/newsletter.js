$(function() {
	tinymce.init({ selector:'textarea',
		plugins: "link" });
		
	var totalSections = 0;//is increased for each section;

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
				tinymce.init({
					mode : "specific_textareas",
					editor_selector : "lastArea"
				});
			} else {
				notifyError();
			}
		}).fail(notifyError);
	});

	$('.newsletter').on('click', '.delete-section', function(e) {
		e.preventDefault();
		if(!confirm('Apagar secção?')) {
			return;
		}
		var button = $(this);
		var newsletterID = button.data('newsletter-id');
		var sectionID = button.data('section-id');
		var section = button.closest('.section');


		$.delete('/newsletter/'+newsletterID+'/'+sectionID).done(function(response) {
			if(response.success) {
				section.remove();
			} else {
				notifyError();
			}
		}).fail(notifyError);
	});

	$('.newsletter').on('click', '.section .move-up', function(e) {
		e.preventDefault();

		var minSeq = 9999;

		$('.seq').each(function() {
			var val = parseInt($(this).val());
			minSeq = val < minSeq ? val : minSeq;
		});

		var section = $(this).closest('.section');
		var seq = section.find('.seq')
		var button = $(this);
		if(seq.val() != minSeq) {
			var prevSection = section.prev();
			var prevSeq = prevSection.find('.seq');
			var curSeqVal = seq.val();
			var mceid = "textmce"+button.data('section-id');
			seq.val(prevSeq.val());
			prevSeq.val(curSeqVal);
			tinymce.EditorManager.execCommand('mceRemoveEditor',true, mceid);
			section.detach();
			prevSection.before(section);
			section.find('.section-num').html(seq.val());
			prevSection.find('.section-num').html(prevSeq.val());			
			tinymce.execCommand('mceAddEditor', true, mceid);
		}
	});

	$('.newsletter').on('click', '.section .move-down', function(e) {
		e.preventDefault();

		var maxSeq = null;

		$('.seq').each(function() {
			var val = parseInt($(this).val());
			maxSeq = val > maxSeq ? val : maxSeq;
		});

		var section = $(this).closest('.section');
		var seq = section.find('.seq')
		var button = $(this);
		if(seq.val() != maxSeq) {			
			var nextSection = section.next();
			var nextSeq = nextSection.find('.seq');
			var curSeqVal = seq.val();
			var mceid = "textmce"+button.data('section-id');			
			seq.val(nextSeq.val());
			nextSeq.val(curSeqVal);
			tinymce.EditorManager.execCommand('mceRemoveEditor',true, mceid);
			section.detach();
			nextSection.after(section);
			section.find('.section-num').html(seq.val());
			nextSection.find('.section-num').html(nextSeq.val());
			tinymce.execCommand('mceAddEditor', true, mceid);
		}
	});

	$('.newsletter').on('click', '.next-button', function(e) {
		e.preventDefault();
		var panel = $(this).closest('.panel');
		panel.fadeOut(function() {
			panel.next().fadeIn();
		});
	});

	$('.newsletter').on('click', '.finish-button', function(e) {
		e.preventDefault();
		var panel = $(this).closest('.panel');
		$("#total-sections").html(totalSections);
		panel.fadeOut(function() {
			panel.next().fadeIn();
		});
	});

	var $grid = $('.grid').isotope({
		itemSelector: '.isotope',
		layoutMode: 'cellsByRow'
	});

	function notifyError() {
		$.notify({
			message: errorMessage
		}, {
			type: 'danger',
			z_index: 1075,
			placement: {
				from: 'bottom',
				align: 'center'
			}
		});
	}		
});