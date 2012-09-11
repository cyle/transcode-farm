/*

	the javascript for farm pages

*/

var temp_id;

$(document).ready(function() {
	
	if ($('#upload-page').length) {
		
		$('#upload-btn').click(function(event) {
			event.preventDefault();
			var file_selected = false;
			$('input.entry-file').each(function(index) {
				if ($(this).val() != '') { file_selected = true; }
			});
			if (!file_selected) {
				alert('Sorry, but you need to select at least one file to upload.');
				return;
			}
			$('div#upload-step').hide();
			$('div#uploading-step').show();
			var username = '';
			if ($('input#un').length && $('input#un').val() != '') {
				username = $('input#un').val();
			} else {
				alert('Sorry, but I cannot read your username.');
				return;
			}
			// get what versions to make...
			var presets = [];
			if ($('input#preset-max').prop('checked')) {
				presets.push('max');
			}
			if ($('input#preset-ultra').prop('checked')) {
				presets.push('ultra');
			}
			if ($('input#preset-high').prop('checked')) {
				presets.push('high');
			}
			if ($('input#preset-medium').prop('checked')) {
				presets.push('medium');
			}
			if ($('input#preset-small').prop('checked')) {
				presets.push('small');
			}
			temp_id = Math.floor(Math.random() * 100000);
			$.ajax({
				url: '/upload.lol?id='+temp_id+'&un='+username,
				//data: $('input.preset').serializeArray(),
				data: { p: presets },
				files: $('input.entry-file'),
				type: 'post',
				iframe: true,
				processData: false,
				dataType: 'json',
				success: function(data) {
					console.log(data);
					var end_result = '';
					var batchinfo = data.info;
					if (batchinfo.error != undefined) {
						end_result += '<div class="alert-box alert">'+batchinfo.error+'</div>';
					} else {
						for (var i = 0; i < batchinfo.length; i++) {
							end_result += '<p>Entry ID #'+batchinfo[i].eid+', '+batchinfo[i].title+': status: <span class="radius label '+((batchinfo[i].status * 1 >= 200) ? 'alert' : 'success')+'">'+batchinfo[i].status_message+'</span>';
							if (batchinfo[i].status * 1 < 200) {
								end_result += ' Making versions: ' + batchinfo[i].versions.join(', ');
							}
							end_result += '</p>';
							if (batchinfo[i].eid != '' && batchinfo[i].eid != '0') {
								end_result += '<input class="eid-field" type="hidden" value="'+batchinfo[i].eid+'" name="eid[]" />';
								end_result += '<input class="eid-status-field" type="hidden" value="'+batchinfo[i].status+'" name="eid-status[]" />';
							}
						}
					}
					$('div#upload-result').html(end_result);
					$('div#uploading-step').hide();
					$('div#progress-bar').hide();
					$('div#upload-step').remove();
					$('div#done-step').show();
					clearInterval(checkStatusTicker);
				},
				error: function(wut, text, err) {
					//console.log(text);
					//console.log(err);
					clearInterval(checkStatusTicker);
					alert('Sorry, it seems the uploader is not available.');
				}
			});
			checkStatusTicker = setInterval(checkStatus, 1000);
		});
		
		$('a.submit-form').click(function(event) {
			event.preventDefault();
			$('form#the-form').submit();
		});
		
		$('a#add-another-file').click(function(event) {
			event.preventDefault();
			var howmany_files = $('div.file-entry').length;
			if (howmany_files >= 5) {
				alert('Sorry, you can only upload five entries at a time.');
				return;
			}
			var another_file_div = $('div#file-entry-template').clone(true);
			//var the_html = another_file_div.html();
			//the_html = the_html.replace(/XXX/g, (howmany_files+1));
			//another_file_div.html(the_html);
			another_file_div.removeAttr('id');
			another_file_div.removeAttr('style');
			another_file_div.addClass('file-entry');
			$('div#file-entry-list').append(another_file_div);
		});
		
		$('div.remove-this-file').click(function(event) {
			event.preventDefault();
			var howmany_files = $('div.file-entry').length;
			if (howmany_files <= 1) {
				alert('Sorry, you need to upload at least one file.');
				return;
			}
			$(this).parent().remove();
		});
		
		$('form#the-form').submit(function() {
			var uploaded_something = false;
			if ($('input.eid-field').length) {
				$('input.eid-field').each(function() {
					if ($(this).val() != '' && $(this).val() * 1 > 0) {
						uploaded_something = true;
					}
				});
			}
			if (uploaded_something == false) {
				alert('Sorry, it looks like you did not actually upload anything.');
				return false;
			}
			return true;
		});
		
	}

});

function checkStatus() { // check status of uploading file(s)
	if (temp_id > 0) {
		$.ajax({
			url: '/status.lol?id='+temp_id,
			type: 'get',
			dataType: 'json',
			success: function(data) {
				//console.log(data);
				//$('#debug_txt').html(data);
				var percentFilled = Math.round((data.bytesReceived/data.bytesTotal) * 100);
				$('div#bar-inner').css('width', percentFilled+'%');
			}
		});
	}
}