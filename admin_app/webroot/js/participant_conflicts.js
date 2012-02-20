$(document).ready(function() {

	var delete_from_adjacents = [];

	var $dialog = $('<div id="dialog-confirm"></div>')
		.html('<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 7px 0;"></span>This user is already booked elsewhere during this timeslot. If you schedule them for this panel now, they will be automatically removed from their previous booking. Are you sure you want to do that?')
		.dialog({
			autoOpen: false,
			resizable: false,
			height: 300,
			modal: true,
			buttons: {
				"I'll make my decision wisely.": function() {
					$(this).dialog("close");
				}
			}
		});

	$('.already-booked').click(function() {
		if ($(this).attr('checked')) {
			$dialog.dialog('open');
		}
	});

	$('.side-by-side').click(function() {
		if ($(this).attr('checked')) {
			var this_name = reload_adjacent_conflicts[$(this).val()].username;
			var this_id = $(this).val();
			var $dialog_adjacent = $('<div id="dialog-adjacent-confirm"></div>')
				.html('<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 7px 0;"></span>This user has a back-to-back scheduling conflict, and asked not to do back-to-back panels. How do you want to handle the conflict?')
				.dialog({
					autoOpen: false,
					resizable: false,
					height: 500,
					modal: true,
					buttons: {
						"Never mind.": function() {
							var id = $(this).data("this").id;
							$('.'+id).removeAttr('checked'); 
							$(this).dialog("close");
						},
						"Assign this user to the current panel and delete them from their adjacent panel(s).": function() {
							var id = $(this).data("this").id;
							// Add the user to the list of schedule-conflict users who'll be deleted from adjacent
							// panels if they're assigned to the current panel
							var index= $.inArray(id, delete_from_adjacents);
							if(index== -1) {
								//Value not found in the array.  Add to the end of the array with push();
								delete_from_adjacents.push(id);
								$("#adjacent_panels_to_delete").val(delete_from_adjacents);
							} 
							// alert($("#adjacent_panels_to_delete").val()); (tests array format)
							$(this).dialog("close");
						},
						"Assign this user to the current panel and leave them on their adjacent panel(s).": function() {
							var id = $(this).data("this").id;
							// Remove the user from the list of schedule-conflict users who'll be deleted from adjacent
							// panels if they're assigned to the current panel
							var index= $.inArray(id, delete_from_adjacents);
							if(index != -1) {
								//Value found in the array.  Remove from the array with splice();
								delete_from_adjacents.splice(index,1);
								$("#adjacent_panels_to_delete").val(delete_from_adjacents);
							} 
							$(this).dialog("close");
						}
					}
				});
			$dialog_adjacent.data("this", { name: this_name, id: this_id } ).dialog('open');
		}
	});
});


