<?php
	$expand = get_request_value('expand');
?>
<style type="text/css">
.ui-icon-closethick {
	display : none !important;
}

#termDetails {
	float : left;
	margin-left : 10px;
	display : none;
}

ol {
	margin: 0px !important;
}
</style>
<script type="text/javascript">
	var treeImagePath = "<?php bloginfo('template_directory'); ?>/scripts/dhtmlxtree/admin/imgs/";
	var treeMenuImagePath = "<?php bloginfo('template_directory') ?>/images/";
	var ajaxSecurity = "<?php echo wp_create_nonce("ajax-informea-vocabulary"); ?>";
	var syn_autocomplete_url = ajaxurl + '?action=synonym_autocomplete&_ajax_nonce=' + ajaxSecurity;
</script>
<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_directory'); ?>/scripts/dhtmlxtree/admin/dhtmlxtree.css">
<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_directory'); ?>/scripts/dhtmlxmenu/skins/dhtmlxmenu_dhx_skyblue.css">
<link rel='stylesheet' href='<?php bloginfo('template_directory'); ?>/ui.css' type='text/css' media='screen' />
<script src="<?php bloginfo('template_directory'); ?>/scripts/jquery-min.js"></script>
<script src="<?php bloginfo('template_directory'); ?>/scripts/ui.js"></script>
<script src="<?php bloginfo('template_directory'); ?>/scripts/dhtmlxtree/admin/dhtmlxcommon.js"></script>
<script src="<?php bloginfo('template_directory'); ?>/scripts/dhtmlxtree/admin/dhtmlxtree.js"></script>
<script src="<?php bloginfo('template_directory'); ?>/scripts/dhtmlxmenu/dhtmlxmenu.js"></script>
<script src="<?php bloginfo('template_directory'); ?>/scripts/dhtmlxmenu/ext/dhtmlxmenu_ext.js"></script>
<script src="<?php bloginfo('url'); ?>/wp-content/plugins/thesaurus/admin_views/voc_manage_terms.js"></script>
<div id="busy" style="background-color: #F0F0F0; border: 1px solid black; text-align : center; padding-top: 120px;">
	<span style="font-size : 2em">Loading, please wait ...</span>
</div>
<div class="wrap">
	<div id="breadcrumb">
		You are here:
		<a href="<?php echo bloginfo('url');?>/wp-admin/admin.php?page=thesaurus">Vocabulary</a>
		&raquo;
		Manage terms relationships

	</div>
	<div id="icon-tools" class="icon32"><br></div>
	<h2>Manage vocabulary of terms</h2>
	<p>
		Manage the vocabulary of terms in InforMEA.
		You can add new terms, edit or delete existing terms and define relationships between terms such as defining broader, narrower, related terms etc.
	</p>
	<a href="javascript:reloadTree();">Reload Tree</a> | <a href="javascript:expandAllTree();">Expand All</a> | <a href="javascript:colapseAllTree();">Collapse All</a>
	<br clear="all" />
	<div id="termsTree" style="float: left; width : 400px; height : 400px; position : relative; border 2px solid black;"></div>
	<div id="termDetails">
		<form id="termDetailsForm">
			<input type="hidden" id="termDetailsForm_id" name="id" />
			<input type="hidden" id="termDetailsForm_broader" name="broader" />
			<input type="hidden" id="termDetailsForm_treeNodeId" name="treeNodeId" />
		<table>
			<tr>
				<td><label for="termDetailsForm_term">Term:</label></td>
				<td>
					<input type="text" id="termDetailsForm_term" name="term" size="40" /> (required)
				</td>
			</tr>
			<tr>
				<td><label for="termDetailsForm_description">Description:</label></td>
				<td><textarea id="termDetailsForm_description" name="description" cols="40" rows="5"></textarea></td>
			</tr>
			<tr>
				<td><label for="termDetailsForm_reference_url">Reference URL:</label></td>
				<td><input type="text" id="termDetailsForm_reference_url" name="reference_url" size="40" /></td>
			</tr>
			<tr>
				<td><label for="termDetailsForm_tag">Tag (used for tag cloud)</label></td>
				<td><input type="text" id="termDetailsForm_tag" name="tag" size="40"></td>
			</tr>
			<tr>
				<td><label for="termDetailsForm_id_source">Source</label></td>
				<td>
					<select id="termDetailsForm_id_source" name="id_source">
						<option value="">-- Please select --</option>
					<?php
					foreach($page_data->get_voc_source() as $row) {
						$checked = ($page_data->get_value('id_source') == $row->id || ($row->url == 'http://www.unep.org')) ? ' selected="selected"' : '';  // Added UNEP default to ease addition
						echo "<option value='{$row->id}'$checked>{$row->name}</option>";
					}?>
					</select> (required)
				</td>
			</tr>
			<tr>
				<td><label for="termDetailsForm_top_concept">Top concept (theme)</label></td>
				<td><input type="checkbox" id="termDetailsForm_top_concept" name="top_concept" value="1" <?php if($page_data->get_value('top_concept')) { echo "checked='checked'"; }?> /></td>
			</tr>
			<tr>
				<td>
					<label for="termDetailsForm_related">
						Related terms <br /> <em>(drag here terms from tree,<br />right click to remove, <br />use CTRL+mouse click to <br />select multiple items)</em>
					</label>
				<td>
					<select id="termDetailsForm_related" name="related[]" multiple="true" size="10" style="width: 350px; height : 100px;">
					</select>
				</td>
			</tr>
			<tr style="background-color: #F0F0F0;">
				<td>
					<label for="termDetailsForm_synonyms">Synonyms</label>
				</td>
				<td>
					<input type="text" id="syn_suggest" name="syn_suggest" size="40" /><input id="syn_suggest_add" type="button" value="Add" />
					<br />
					<select id="termDetailsForm_synonyms" name="synonyms[]" multiple="multiple" style="width: 360px; height: 100px;" size="5">
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input id="termDetailsForm_submit" name="actioned" type="button" class="button-primary" value="<?php _e('Save changes', 'informea'); ?>" />
					<input id="termDetailsForm_create" name="actioned" type="button" class="button-primary" value="<?php _e('Create term', 'informea'); ?>" />
					<input id="termDetailsForm_cancel" name="actioned" type="button" class="button-primary" value="<?php _e('Cancel', 'informea'); ?>" />
				</td>
			</tr>
		</table>
		</form>
	</div>
	<br clear="all" />
	<?php if($expand) { ?>
	<a href="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus&act=voc_relationships&id_term=<?php echo $expand; ?>">Go back to term edit form</a>
	<?php } ?>
	<h3>How to use this form</h3>
	<img src="<?php bloginfo('template_directory'); ?>/images/icon-about-large.png" style="float: left" />
	<div style="float: left; margin-left: 2em;">
	<ol>
		<li>The tree shows the hierarchical relations between terms, terms filed under another term are narrower terms to his parent;</li>
		<li>Click on the term and start editing the term. When finished, press '<strong>Save changes</strong>';</li>
		<li>When editing a term, you add 'related terms' by dragging terms from the tree on left, to the 'Related terms' area on the right, within editing form;</li>
		<li>You can move a term under another term, just drag it to the new position;</li>
		<li>You can unmark a term as narrower, by right-click on term and select 'Unmark term as narrower'. <strong>This will not delete the term</strong>!</li>
		<li>In case you get an error message, please <em>refresh the page, and try again</em> or <strong>contact technical support as you may just hit over a bug</strong></li>
		<li>If you want to mark a term as narrower to multiple terms, just right-click on the term, select 'Copy term', then right-click on target term and click 'Paste term'. This will duplicate the term as narrower to the new parent. Note: Only the term will be copied, excluding its narrower terms.</li>
		<li><span style="color: red;"><strong>Warning:</strong> If a term is not appearing anymore it means is not narrower to any other term! Go to <a href="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus&act=voc_relationships">Manual edit term</a> and select its broader term from there</span></li>
	</ol>
	</div>
	<script type="text/javascript">
		var expandTerm = '<?php echo $expand != null ? $expand : ''; ?>';
	</script>

