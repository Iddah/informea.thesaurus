
function showBusyDialog() {
	$('#busy').dialog('open');
}

function closeBusyDialog() {
	$('#busy').dialog('close');
}

// Terms tree
var tree = null;
var treeClipboard = null;
var staticIDs = 0;
var bIsEditingNew = false; // If added & editing new term, forbid to change selection

$(document).ready(function() {
	$('#busy').dialog({
		autoOpen : false, modal : true, draggable: false, width: 460, height: 300, minHeight: 50,
		buttons: {}, resizable: false,
		open: function() {
			// scrollbar fix for IE
			$('body').css('overflow','hidden');
		},
		close: function() {
			// reset overflow
			$('body').css('overflow','auto');
		}
	});
	$('#busy').dialog('option', 'position', 'top');
	$('#termDetailsForm_submit').click(onTermDetailsFormEditClick);
	$('#termDetailsForm_create').click(onTermDetailsFormCreateClick);
	$('#termDetailsForm_cancel').click(function() {
		bIsEditingNew = false;
		$('#termDetailsForm_id').val('');
		$('#termDetails').hide();
		tree.deleteItem($('#termDetailsForm_treeNodeId').val());
	});
	showBusyDialog();
	setupTree();
	setupSynonyms();
});


function setupTree() {

	// Add contextual menu to the tree
	var treeMenu = new dhtmlXMenuObject();
	treeMenu.setIconsPath(treeMenuImagePath); // Global variable
	treeMenu.renderAsContextMenu();
	treeMenu.addNewChild(treeMenu.topId, 1, 'narrower_term', 'Create new narrower term', false, 'add.png');
	treeMenu.addNewChild(treeMenu.topId, 2, 'move_term', 'Move term', false, 'move.png', 'move.png');
	treeMenu.addNewChild(treeMenu.topId, 2, 'copy_term', 'Copy term', false, 'copy.png', 'copy.png');
	treeMenu.addNewChild(treeMenu.topId, 3, 'paste_term', 'Paste term', true, 'paste.png', 'paste.png');
	treeMenu.addNewChild(treeMenu.topId, 4, 'unlink_term', 'Unmark term as narrower', false, 'unlink.png', 'unlink.png');
	treeMenu.attachEvent('onClick', function(id, zoneId, casState) {
		onTreeMenuClick(tree, treeMenu, id);
	});

	// Add contextual menu to the related terms HTML select control
	var relatedTermsMenu = new dhtmlXMenuObject();
	relatedTermsMenu.setIconsPath(treeMenuImagePath); // Global variable
	relatedTermsMenu.renderAsContextMenu();
	relatedTermsMenu.addNewChild(relatedTermsMenu.topId, 0, 'r_remove', 'Remove selected terms', false, 'delete.png');
	relatedTermsMenu.attachEvent('onClick', onRelatedTermsMenuClick);

	tree = new dhtmlXTreeObject('termsTree', '100%', '100%', 0);
	tree.setImagePath(treeImagePath); // Global variable
	tree.enableDragAndDrop(true, false);
	var treeXMLUrl = ajaxurl + '?action=generate_terms_tree&_ajax_nonce=' + ajaxSecurity;
	// tree.setXMLAutoLoading(treeXMLUrl);
	tree.loadXML(treeXMLUrl, function() { // Global variables
		if(expandTerm !== '') {
			var allnodes = tree.getAllSubItems(0);
			var arr = allnodes.split(',');

			var lastMatch = null;
			$.each(arr, function(idx, nodeId) {
				var t = tree.getUserData(nodeId, 'term_id');
				if(t == expandTerm) {
					lastMatch = nodeId;
					tree.openItem(nodeId);
				}
			});
			if(lastMatch != null) {
				tree.focusItem(lastMatch);
				tree.selectItem(lastMatch, true);
			}
		}
		 closeBusyDialog();
	});

	tree.attachEvent('onClick', function(id, prevId) {
		var termId = tree.getUserData(id, 'term_id');
		treeOnClick(id, termId, prevId);
		return true;
	});

	tree.attachEvent('onSelect', function(id) {
	});

	/*
	tree.attachEvent('onDragIn', function(sId, tId, id, sObject, tObject) {
		if(tree.getParentId(sId) == tId || sId == tId || tree.getParentId(sId) == 0) {
			return false;
		}
		return true;
	});
	*/
	tree.attachEvent('onDrag', function(sId, tId, id, sObject, tObject) {
		return false;
		/*
		var child = sObject.getUserData(sId, 'term_id');
		var parentId = tree.getParentId(sId);
		var oldParent = tree.getUserData(parentId, 'term_id');
		var newParent = tObject.getUserData(tId, 'term_id');
		return treeOnDrag(child, oldParent, newParent);
		*/
	});


	tree.attachEvent('onRightClick', function(id, e) {
		if(id != null) {
			tree.selectItem(id);
			tree.openItem(id);
			var parent = tree.getParentId(id);
			if(parent == 0) {
				treeMenu.setItemDisabled('copy_term');
				treeMenu.setItemDisabled('move_term');
				treeMenu.setItemDisabled('unlink_term');
			} else {
				treeMenu.setItemEnabled('copy_term');
				treeMenu.setItemEnabled('move_term');
				treeMenu.setItemEnabled('unlink_term');
			}
			if(treeClipboard && !validateTreeMenuClickPaste(id)) {
				treeMenu.setItemDisabled('paste_term');
			} else if(treeClipboard) {
				treeMenu.setItemEnabled('paste_term');
			}
			if($.browser.msie) { e = $.event.fix(e); }
			treeMenu.showContextMenu(e.pageX, e.pageY);
		}
	});

	var relatedDropTarget = document.getElementById('termDetailsForm_related');
	tree.dragger.addDragLanding(relatedDropTarget, new termsRelatedDropControl);

	$('#termDetailsForm_related').bind('contextmenu', function(e) {
		if($.browser.msie) { e = $.event.fix(e); }
		if($(e.target).is('option') || ($.browser.msie && $(e.target).is('select'))) {
			var selected = $('#termDetailsForm_related').find('option:selected');
			if(selected.length > 0) {
				relatedTermsMenu.showContextMenu(e.pageX, e.pageY);
			}
		}
		return false;
	});

	// (Re)set the cursor on the drag related terms list (dragOut never called)
	$('#termDetailsForm_related').mouseenter(function() {
		$('#termDetailsForm_related').css('cursor', 'default');
	});
}

// Drag and drop over related terms
function termsRelatedDropControl() {
	this._drag = function(sourceHtmlObject, dhtmlObject, targetHtmlObject) {
		var id = dhtmlObject.getUserData(sourceHtmlObject.parentObject.id, 'term_id');
		var label = sourceHtmlObject.parentObject.label;

		// Check if we already have this id and reject. Also if drop same term on itself
		var loadedTermId = $('#termDetailsForm_id').val();
		var forbid = false;
		$('#termDetailsForm_related').find('option').each(function() {
			if($(this).val() == id) {
				forbid = true;
			}
		});
		if(forbid) {
			alert('The term is already related!');
		} else if(id == loadedTermId) {
			alert('The term cannot relate with itself!');
		} else {
			$('#termDetailsForm_related').find('option').end().append('<option value="' + id + '">' + label + '</option>').val(id);
		}
	}

	this._dragIn = function(htmlObject, shtmlObject) {
		var nodeId = tree.getSelectedItemId();
		var id = tree.getUserData(nodeId, 'term_id');
		var loadedTermId = $('#termDetailsForm_id').val();
		var forbid = false;
		$('#termDetailsForm_related').find('option').each(function() { // Do not allow drop if term is already is there
			if($(this).val() == id) {
				forbid = true;
			}
		});
		if(id == loadedTermId) { forbid = true; } // Do not allow drop if term is the same
		if(forbid) {
			htmlObject.style.cursor = 'no-drop';
			return false;
		} else {
			htmlObject.style.cursor = 'default';
			return htmlObject;
		}
	}

	this._dragOut = function(htmlObject) {
		return this;
	}
}

/*
function treeOnDrag(child, oldParent, newParent) {
	var url = ajaxurl + '?action=update_term_hierarchy&security=' + ajaxSecurity;
	$.ajax({
		url : url,
		type : 'POST',
		data : {
			child : child,
			newParent : newParent,
			oldParent : oldParent,
		},
		success : function(data) {
			closeBusyDialog();
		},
		error : function(jqXHR, textStatus, errorThrown) {
			closeBusyDialog();
			alert('An Ajax error occurred (' + textStatus + '). Report this error together with a detailed description of what you were doing. Details:' + errorThrown);
		}
	});
	return true;
}
*/


function treeOnClick(id, termId, prevId) {
	if(bIsEditingNew) {
		if(!confirm('Do you abandon creation of a new term?')) {
			tree.selectItem(prevId);
			return;
		} else {
			tree.deleteItem(prevId);
		}
	}
	bIsEditingNew = false;
	showBusyDialog();
	var url = ajaxurl + '?action=load_term_json&_ajax_nonce=' + ajaxSecurity + '&id=' + termId;
	$.ajax({
		url : url,
		dataType: 'json',
		success : function(data) {
			$('#termDetailsForm_create').hide();
			$('#termDetailsForm_submit').show();
			$('#termDetailsForm_cancel').hide();
			$('#termDetailsForm_id').val(data.term.id);
			$('#termDetailsForm_term').val(data.term.term);
			$('#termDetailsForm_description').val(data.term.description);
			$('#termDetailsForm_reference_url').val(data.term.reference_url);
			$('#termDetailsForm_tag').val(data.term.tag);
			$('#termDetailsForm_id_source').val(data.term.id_source);
			$('#termDetailsForm_top_concept').attr('checked', data.term.top_concept == '1');
			$('#termDetailsForm_treeNodeId').val(id);
			$('#termDetailsForm_related').find('option').remove();
			$('#syn_suggest').val('');
			$('#termDetailsForm_synonyms').find('option').remove();
			if(data.related.length > 0) {
				$.each(data.related, function(index, value) {
					$('#termDetailsForm_related').append($("<option></option>").attr("value",value.id).text(value.term));
				});
				$('#termDetailsForm_related').find('option:selected').each(function() {
					$(this).attr('selected', false);
				});
			}
			if(data.synonyms.length > 0) {
				$.each(data.synonyms, function(index, value) {
					$('#termDetailsForm_synonyms').append($("<option></option>").attr("value", value.synonym).text(value.synonym));
				});
			}
			$('#termDetails').show();
			closeBusyDialog();
		},
		error : function(jqXHR, textStatus, errorThrown) {
			closeBusyDialog();
			alert('An Ajax error occurred (' + textStatus + '). Report this error together with a detailed description of what you were doing. Details:' + errorThrown);
		}
	});
}

function onTermDetailsFormEditClick() {
	showBusyDialog();
	var url = ajaxurl + '?action=update_term&_ajax_nonce=' + ajaxSecurity;

	var related = [];
	$('#termDetailsForm_related').find('option').each(function() {
		related.push(this.value);
	});

	var synonyms = [];
	$('#termDetailsForm_synonyms').find('option').each(function() {
		synonyms.push(this.value);
	});
	var newLabel = $('#termDetailsForm_term').val();
	var newDescription = $('#termDetailsForm_description').val();
	var postData = {
			id_term : $('#termDetailsForm_id').val(),
			term : newLabel,
			description : newDescription,
			reference_url : $('#termDetailsForm_reference_url').val(),
			tag : $('#termDetailsForm_tag').val(),
			id_source : $('#termDetailsForm_id_source').val(),
			treeNodeId : $('#termDetailsForm_treeNodeId').val(),
			related : related,
			synonyms : synonyms,
		};
	if($('#termDetailsForm_top_concept').attr('checked')) {
		postData['top_concept'] = 1;
	}

	$.ajax({
		url : url,
		type : 'POST',
		data : postData,
		success : function(data) {
			closeBusyDialog();
			// Update node label if required
			var nodeId = data.treeNodeId;
			tree.selectItem(nodeId);
			var term_id = tree.getUserData(nodeId, 'term_id');
			// Set the text for all the instances of this term in the tree
			var allNodes = tree.getAllSubItems('0');
			var arr = allNodes.split(',');
			$.each(arr, function(idx, nodeId) {
				var term = tree.getUserData(nodeId, 'term_id');
				if(term == term_id) {
					tree.setItemText(nodeId, newLabel, newDescription + ' (term id:' + term_id + ')');
				}
			});
			if(data.success == true) {
				alert('Term was successfully updated');
			}
		},
		error : function(jqXHR, textStatus, errorThrown) {
			closeBusyDialog();
			alert('An Ajax error occurred (' + textStatus + '). Report this error together with a detailed description of what you were doing. Details:' + errorThrown);
		}
	});
}


function onRelatedTermsMenuClick(id, zoneId, casState) {
	if('r_remove' == id) {
		$('#termDetailsForm_related').find('option:selected').each(function() {
			$(this).remove();
		});
	}
}


function onTreeMenuClick(tree, treeMenu, menu_id) {
	var nodeId = tree.getSelectedItemId();
	var parentId = tree.getParentId(nodeId);
	if('copy_term' == menu_id) {
		treeClipboard = {
			op : menu_id,
			nodeId : nodeId,
			term : tree.getUserData(nodeId, 'term_id'),
			label : tree.getItemText(nodeId),
			oldParent : tree.getUserData(parentId, 'term_id')
		};
		treeMenu.setItemEnabled('paste_term');
	} else if('move_term' == menu_id) {
		treeClipboard = {
			op : menu_id,
			nodeId : nodeId,
			term : tree.getUserData(nodeId, 'term_id'),
			label : tree.getItemText(nodeId),
			oldParent : tree.getUserData(parentId, 'term_id')
		};
		treeMenu.setItemEnabled('paste_term');
	} else if('paste_term' == menu_id) {
		showBusyDialog();
		var url = ajaxurl + '?action=manipulate_term&_ajax_nonce=' + ajaxSecurity;
		var op = treeClipboard['op'];
		$.ajax({
			url : url,
			type : 'POST',
			data : {
				'op' : op,
				term : treeClipboard['term'],
				oldParent : treeClipboard['oldParent'],
				newParent : tree.getUserData(nodeId, 'term_id')
			},
			success : function(data) {
				var newParent = nodeId.split('_')[1];
				var cnid = treeClipboard['nodeId'].split('_')[1];
				var newNodeId = newParent + '_' + cnid + '_' + Math.floor(Math.random()*1999999999);
				if(op == 'copy_term') {
					tree.insertNewChild(nodeId, newNodeId, treeClipboard['label'], 0, 0, 0, 0, 'SELECT');
					tree.setUserData(newNodeId, 'term_id', treeClipboard['term']);
				}
				if(op == 'move_term') {
					tree.deleteItem(treeClipboard['nodeId']);
					tree.insertNewChild(nodeId, newNodeId, treeClipboard['label'], 0, 0, 0, 0, 'SELECT');
					tree.setUserData(newNodeId, 'term_id', treeClipboard['term']);
				}
				treeMenu.setItemDisabled('paste_term');
				closeBusyDialog();
			},
			error : function(jqXHR, textStatus, errorThrown) {
				closeBusyDialog();
				alert('An Ajax error occurred (' + textStatus + '). Report this error together with a detailed description of what you were doing. Details:' + errorThrown);
			}
		});
	} else if('unlink_term' == menu_id) {
		var url = ajaxurl + '?action=unlink_term&_ajax_nonce=' + ajaxSecurity;
		$.ajax({
			url : url,
			type : 'POST',
			data : { term : tree.getUserData(nodeId, 'term_id'), parent : tree.getUserData(parentId, 'term_id') },
			success : function(data) {
				tree.deleteItem(nodeId, true);
				closeBusyDialog();
			},
			error : function(jqXHR, textStatus, errorThrown) {
				closeBusyDialog();
				alert('An Ajax error occurred (' + textStatus + '). Report this error together with a detailed description of what you were doing. Details:' + errorThrown);
			}
		});
	} else if('narrower_term' == menu_id) {
		$('#termDetailsForm_id').val('');
		$('#termDetailsForm_term').val('');
		$('#termDetailsForm_description').val('');
		$('#termDetailsForm_reference_url').val('');
		$('#termDetailsForm_tag').val('');
		$('#termDetailsForm_id_source').val('');
		$('#termDetailsForm_top_concept').attr('checked', false);
		$('#termDetailsForm_related').find('option').remove();
		$('#termDetailsForm_submit').hide();
		$('#termDetailsForm_create').show();
		$('#termDetailsForm_cancel').show();
		$('#termDetailsForm_broader').val(tree.getUserData(nodeId, 'term_id'));
		var newId = 'static_' + (++staticIDs);
		$('#termDetailsForm_treeNodeId').val(newId);
		$('#termDetails').show();
		bIsEditingNew = true;
		tree.insertNewChild(nodeId, newId, "New term", 0, 0, 0, 0, 'SELECT,TOP');
	}
}


function validateTreeMenuClickPaste(targetId) {
	var pastedId = treeClipboard['nodeId'];
	var pastedTerm = tree.getUserData(pastedId, 'term_id');
	var targetTerm = tree.getUserData(targetId, 'term_id');
	// Cannot paste if target == pasted
	if(pastedTerm == targetTerm) {
		return false;
	}
	var strChildren = tree.getAllSubItems(pastedId);
	var children = [];
	if(strChildren != '') {
		children = strChildren.split(',');
	}
	children.push(pastedId);
	// paste destination cannot be children of clipboard term
	$.each(children, function(index, childId) {
		var term = tree.getUserData(childId, 'term_id');
		if(targetTerm == term) {
			return false;
		}
	});
	// cannot paste if term is already there (a child)
	// TODO: Does not work on nodes that have unloaded childrens (due to tree asynchronous calls when loading nodes)
	var ret = true;
	children = [];
	strChildren = tree.getSubItems(targetId);
	if(strChildren != '') {
		children = strChildren.split(',');
	}
	$.each(children, function(index, childId) {
		var term = tree.getUserData(childId, 'term_id');
		if(term == pastedTerm) {
			ret = false;
			return;
		}
	});
	return ret;
}

function onTermDetailsFormCreateClick() {
	showBusyDialog();
	bIsEditingNew = false;
	var url = ajaxurl + '?action=create_term&_ajax_nonce=' + ajaxSecurity;

	var related = [];
	$('#termDetailsForm_related').find('option').each(function() {
		related.push(this.value);
	});
	var synonyms = [];
	$('#termDetailsForm_synonyms').find('option').each(function() {
		synonyms.push(this.value);
	});
	var newLabel = $('#termDetailsForm_term').val();
	var newDescription = $('#termDetailsForm_description').val();
	var postData = {
			term : newLabel,
			treeNodeId : $('#termDetailsForm_treeNodeId').val(),
			broader : $('#termDetailsForm_broader').val(),
			description : newDescription,
			reference_url : $('#termDetailsForm_reference_url').val(),
			tag : $('#termDetailsForm_tag').val(),
			id_source : $('#termDetailsForm_id_source').val(),
			related : related,
			synonyms : synonyms
	};
	if($('#termDetailsForm_top_concept').attr('checked')) {
		postData['top_concept'] = 1;
	}

	$.ajax({
		url : url,
		type : 'POST',
		dataType: 'json',
		data : postData,
		success : function(data) {
			closeBusyDialog();
			if(data.success) {
				tree.selectItem(data.treeNodeId);
				tree.setUserData(data.treeNodeId, 'term_id', data.id);
				tree.setItemText(data.treeNodeId, newLabel, newDescription + ' (term id:' + data.id + ')');
				$('#termDetailsForm_submit').show();
				$('#termDetailsForm_create').hide();
				$('#termDetailsForm_cancel').hide();
				$('#termDetailsForm_id').val(data.id);
				alert('Term was successfully created');
			}
		},
		error : function(jqXHR, textStatus, errorThrown) {
			closeBusyDialog();
			alert('An Ajax error occurred (' + textStatus + '). Report this error together with a detailed description of what you were doing. Details:' + errorThrown);
		}
	});
}

function reloadTree() {
	tree.deleteChildItems(0);
	var treeXMLUrl = ajaxurl + '?action=generate_terms_tree&_ajax_nonce=' + ajaxSecurity;
	tree.loadXML(treeXMLUrl, function() { // Global variables
		 closeBusyDialog();
	});
}

function expandAllTree() {
	tree.openAllItems(0);
}

function colapseAllTree() {
	tree.closeAllItems(0);
}


function setupSynonyms() {
	$('#syn_suggest').autocomplete({
		source: function( request, response ) {
			$.ajax({
				url: syn_autocomplete_url + '&id=' + $('#termDetailsForm_id').val(),
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
		$('#termDetailsForm_synonyms option').each(function(index, item) {
			if(item.value == value) { add = false; }
		});
		if(add) {
			$('#termDetailsForm_synonyms').prepend($("<option></option>").attr("value",value).text(value));
			$('#syn_suggest').attr('value', '');
		} else {
			alert(value + ' is already on the synonyms list');
		}
		$('#syn_suggest').focus();
	});

	// Add contextual menu to the synonyms select control
	var synMenu = new dhtmlXMenuObject();
	synMenu.setIconsPath(treeMenuImagePath); // Global variable
	synMenu.renderAsContextMenu();
	synMenu.addNewChild(synMenu.topId, 0, 'r_remove', 'Remove synonym', false, 'delete.png');
	synMenu.attachEvent('onClick', function (id, zoneId, casState) {
		if('r_remove' == id) {
			$('#termDetailsForm_synonyms').find('option:selected').each(function() {
				$(this).remove();
			});
		}
	});

	$('#termDetailsForm_synonyms').bind('contextmenu', function(e) {
		if($.browser.msie) { e = $.event.fix(e); }
		if($(e.target).is('option') || ($.browser.msie && $(e.target).is('select'))) {
			var selected = $('#termDetailsForm_synonyms').find('option:selected');
			if(selected.length > 0) {
				synMenu.showContextMenu(e.pageX, e.pageY);
			}
		}
		return false;
	});
}
