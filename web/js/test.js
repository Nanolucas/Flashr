var countdown, 
	timer;

$(function() {
	initialise_question();
});

function initialise_question() {
	$('.answer_container input').click(function() {
		var $input = $(this);
		$.post('/' + CURRENT_LANGUAGE + '/test/answer/', 'question=' + $('#question_id').val() + '&answer=' + $(this).data('value'), function(response) {
			if (response.data.answer == 'ok') {
				$input.addClass('correct');

				var answer_translation = ($('#question_phrase').html() == response.data.phrase) ? response.data.translation : response.data.phrase;
				$('.answer_container').hide(500).after('<h1 class="correct">' + answer_translation + '</h1>');

				$('#answer_extra h1').html(response.data.phonetic);
				$('#answer_extra').fadeIn(500);

				timer = 5;
				run_countdown();
			} else {
				$input.addClass('wrong');
			}
		});
	});
}

function run_countdown() {
	if (timer <= 0) {
		next_question();

		return;
	}

	var $countdown_timer = $('#countdown_timer');
	if ($countdown_timer.length) {
		$countdown_timer.val(--timer)
	} else {
		$countdown_timer = $('<input id="countdown_timer" type="button" value="' + timer + '" />')
			.mouseover(function() {
				pause_countdown();
			})
			.mouseout(function() {
				run_countdown();
			})
			.click(function() {
				stop_countdown();
			});

		$('#question_language').after($countdown_timer);
	}

	countdown = setTimeout(function() {
		run_countdown();
	}, 1000);
}

function pause_countdown() {
	var $countdown_timer = $('#countdown_timer');
	if (!$countdown_timer.length) {
		return;
	}

	$countdown_timer.val('II');

	clearTimeout(countdown);
}

function stop_countdown() {
	var $countdown_timer = $('#countdown_timer');
	if (!$countdown_timer.length) {
		return;
	}

	clearTimeout(countdown);

	$countdown_timer
		.unbind('mouseover')
		.unbind('mouseout')
		.click(function() {
			next_question();
		})
		.val('>>');
}

function next_question() {
	var url = '/' + CURRENT_LANGUAGE + '/test/question/';

	if (category != 'all') {
		url += category + '/';
	}

	$.get(url, function(response) {
		$('.test_container').html(response);
		initialise_question();
	});
}