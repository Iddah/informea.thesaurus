$().ready(function() {
	$('#syn_suggest').autocomplete({
		source: function( request, response ) {
			$.ajax({
				url: syn_autocomplete_url,
				dataType: "json",
				data: {
					maxRows: 10,
					key: request.term
				},
				success: function( data ) {
					response($.map(data, function( item ) {
						return {
							label: item.synonym,
							value: item.synonym
						}
					}));
				}
			});
		},
		minLength: 10,
		delay : 100,
		minLength : 1
	});

	$('#syn_suggest').keydown(function(e){
		return e.keyCode != 13;
	});

	$('#syn_suggest_add').click(function() {
		var add = true;
		var value = $('#syn_suggest').attr('value');
		$('#synonyms option').each(function(index, item) {
			if(item.value == value) { add = false; }
		});
		if(add) {
			$('#synonyms').prepend($("<option></option>").attr("value",value).text(value));
			$('#syn_suggest').attr('value', '');
		} else {
			alert(value + ' is already on the synonyms list');
		}
		$('#syn_suggest').focus();
	});


	// Add contextual menu to the synonyms select control
	var synMenu = new dhtmlXMenuObject();
	synMenu.setIconsPath(imagePath); // Global variable
	synMenu.renderAsContextMenu();
	synMenu.addNewChild(synMenu.topId, 0, 'r_remove', 'Remove synonym', false, 'delete.png');
	synMenu.attachEvent('onClick', function (id, zoneId, casState) {
		if('r_remove' == id) {
			$('#synonyms').find('option:selected').each(function() {
				$(this).remove();
			});
		}
	});

	$('#synonyms').bind('contextmenu', function(e) {
		if($.browser.msie) { e = $.event.fix(e); }
		if($(e.target).is('option') || ($.browser.msie && $(e.target).is('select'))) {
			var selected = $('#synonyms').find('option:selected');
			if(selected.length > 0) {
				synMenu.showContextMenu(e.pageX, e.pageY);
			}
		}
		return false;
	});

	$('#form_voc_edit_term').submit(function() {
		$('#synonyms option').attr('selected', 'selected');
		return true;
	});
});
