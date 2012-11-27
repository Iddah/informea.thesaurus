<?php
$id_term = null;
$term = null;
$select = get_request_value('select_source');
$id_src = get_request_int('id_src');
if(empty($select)) {
	$id_term = get_request_int('id_term');
	$term = $page_data->get_term($id_term);
}
if($id_term && !$term) {
	echo '<p>' . __("Term not found in vocabulary!") . '</p>';
	return;
}
if($term) {
	$id_src = $term->id_source;
}
?>
<script type="text/javascript">
	var ajaxSecurity = "<?php echo wp_create_nonce("ajax-informea-vocabulary"); ?>";
	var syn_autocomplete_url = ajaxurl + '?action=synonym_autocomplete&_ajax_nonce=' + ajaxSecurity + '&id=' + <?php echo $id_term; ?>;
	var imagePath = "<?php bloginfo('template_directory') ?>/images/";
</script>
<script src="<?php bloginfo('template_directory'); ?>/scripts/jquery-min.js" type="text/javascript"></script>
<script src="<?php bloginfo('template_directory'); ?>/scripts/ui.js" type="text/javascript"></script>
<script src="<?php bloginfo('template_directory'); ?>/scripts/dhtmlxtree/dhtmlxcommon.js" type="text/javascript"></script>
<script src="<?php bloginfo('template_directory'); ?>/scripts/dhtmlxmenu/dhtmlxmenu.js" type="text/javascript"></script>
<script src="<?php bloginfo('template_directory'); ?>/scripts/dhtmlxmenu/ext/dhtmlxmenu_ext.js" type="text/javascript"></script>
<script src="<?php bloginfo('url'); ?>/wp-content/plugins/thesaurus/admin_views/voc_edit_term.js" type="text/javascript"></script>

<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_directory'); ?>/scripts/dhtmlxtree/dhtmlxtree.css" />
<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_directory'); ?>/scripts/dhtmlxmenu/skins/dhtmlxmenu_dhx_skyblue.css" />
<link rel='stylesheet' href='<?php bloginfo('template_directory'); ?>/ui.css' type='text/css' media='screen' />
<style type="text/css">
.ui-autocomplete, .ui-menu, .ui-menu-item, .ui-widget-content {
	background-color: #C0C0C0;
	padding: 0;
}

.field {
	margin-bottom: 10px;
}

.field label {
	width: 250px; float: left;
}
</style>
<div class="wrap">
	<div id="breadcrumb">
		You are here:
		<a href="<?php echo bloginfo('url');?>/wp-admin/admin.php?page=thesaurus">Vocabulary</a>
		&raquo;
		<a href="<?php echo bloginfo('url');?>/wp-admin/admin.php?page=thesaurus&act=voc_relationships">Edit term</a>
	</div>

	<div id="icon-tools" class="icon32"><br></div>
	<h2>Edit term and its relationships</h2>
	View <a href="<?php bloginfo('url'); ?>/terms">list of terms</a> or <a href="<?php bloginfo('url'); ?>/terms/<?php echo $id_term;?>">term index page</a> in front-end.
	<p>
		Select the term from the list, then enter details below.
	</p>

	<?php include(dirname(__FILE__) . '/operation.html.php'); ?>

	<form id="form_voc_edit_term" action="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus&act=voc_relationships" method="post">
		<?php wp_nonce_field('informea-admin_voc_relationships'); ?>
		<input type="hidden" name="page" value="thesaurus" />
		<input type="hidden" name="act" value="voc_relationships" />
		<div class="field">
			<label for="id_src">Select vocabulary</label>
			<select id="id_src" name="id_src">
				<option value="">-- Please select --</option>
				<?php
				foreach($page_data->get_voc_source() as $row) {
					$checked = $id_src == intval($row->id)  ? ' selected="selected"' : '';
					echo "<option value='{$row->id}'$checked>{$row->name}</option>";
				}?>
			</select>
			<input name="select_source" type="submit" class="button-primary" value="<?php esc_attr_e('select'); ?>" />
		</div>
		<?php
			$sterms = $page_data->get_voc_concept(null, $id_src);
			if(!empty($sterms) && $id_src) {
		?>
		<div class="field">
			<label for="id_term">Select term to edit</label>
			<select id="id_term" name="id_term">
				<option value="">-- Please select --</option>
				<?php
				foreach($sterms as $row) {
					$checked = $id_term == intval($row->id) ? ' selected="selected"' : '';
					echo "<option value='{$row->id}'$checked>{$row->term}</option>";
				}?>
			</select>
			<input name="select" type="submit" class="button-primary" value="<?php esc_attr_e('select'); ?>" />
		</div>
		<?php } ?>
<?php if($id_term !== NULL) { ?>
		<h3>Properties</h3>
		<div class="field">
			<label for="term">Term text</label>
			<input class="input" type="text" size="42" id="term" name="term" value="<?php echo $term->term;?>" />
		</div>

		<div class="field">
			<label for="description">Description</label>
			<textarea class="input" rows="5" cols="40" id="description" name="description" value=""><?php echo $term->description;?></textarea>
		</div>

		<div class="field">
			<label for="reference_url">Reference URL</label>
			<input type="text" size="42" id="reference_url" name="reference_url" value="<?php echo $term->reference_url;?>" />
		</div>

		<div class="field">
			<label for="tag">Tag (used for tag cloud)</label>
			<input type="text" size="42" id="tag" name="tag" value="<?php echo $term->tag;?>" />
		</div>

		<div class="field">
			<label for="id_source">Source</label>
			<select id="id_source" name="id_source">
				<option value="">-- Please select --</option>
				<?php
					foreach($page_data->get_voc_source() as $row) {
					$checked = $row->id == $term->id_source ? ' selected="selected"' : '';
				?>
					<option value="<?php echo $row->id; ?>"<?php echo $checked; ?>><?php echo $row->name;?></option>
				<?php } ?>
			</select>
		</div>
		<div class="field">
			<label for="top_concept">Top concept (theme)</label>
			<input type="checkbox" id="top_concept" name="top_concept" value="1" <?php if($term->top_concept) { echo "checked='checked'"; }?> />
		</div>

		<div class="field">
			<label for="geg_tools_url">URL to GEG correspondent</label>
			<input type="text" size="60" id="geg_tools_url" name="geg_tools_url" value="<?php echo $term->geg_tools_url;?>" />
		</div>

		<h3>Term relationships</h3>
		<a href="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus&amp;act=voc_manage_terms&amp;expand=<?php echo $term->id; ?>">View hierarhical relationships</a>
		<?php if(!$term->top_concept) { ?>
		<div class="field">
			<label for="broader">
				Broader terms
				<br />
				<em style="color: red;">Use <strong>(Ctrl, Shift) + Click</strong> to select/deselect <br />multiple item(s) and range of terms</em>
				<br />
				<br />
			</label>
			<select id="broader" name="broader[]" size="12" multiple="multiple" style="width: 360px; height: 25em">
				<option value="">-- Please select --</option>
				<?php
				$broader_terms = $page_data->get_broader_terms($term->id);
				foreach($page_data->get_voc_concept($id_term) as $row) {
					$checked = in_array($row->id, $broader_terms)  ? ' selected="selected"' : '';
					$label = esc_attr($row->term);
				?>
					<option title="<?php echo $label; ?>" value="<?php echo $row->id; ?>"<?php echo $checked; ?>><?php echo $label; ?></option>
				<?php }?>
			</select>
		</div>
		<?php } ?>

		<h3>Synonyms</h3>
		<div class="field">
			<label for="syn_suggest">Type in the synonym and press 'Add'</label>
			<input type="text" id="syn_suggest" name="syn_suggest" size="40" /><input id="syn_suggest_add" type="button" value="Add" />
		</div>

		<div class="field">
			<label for="synonyms">Assigned synonym(s), <br /> right-click to remove</label>
			<?php $synonyms = $page_data->get_synonyms($id_term); ?>
			<select id="synonyms" name="synonyms[]" multiple="multiple" style="width: 360px; height: 100px;" size="5">
			<?php foreach($synonyms as $s) { ?>
				<option value="<?php echo $s->synonym; ?>"><?php echo $s->synonym; ?></option>
			<?php } ?>
			</select>
		</div>
		<?php if($term->id_source == 9) { ?>
		<div class="field">
			<label for="geg">GEG synonym focus</label>
			<?php $geg_terms = $page_data->get_geg_focus_terms(); ?>
			<select id="geg" name="geg">
				<option value="">-- Please select --</option>
			<?php
				foreach($geg_terms as $s) {
					$tt = $page_data->get_term_geg_synonym($term->id);
					$selected = ($tt && $tt->id == $s->id) ? ' selected="selected"' : '';
			?>
				<option value="<?php echo $s->id; ?>"<?php echo $selected; ?>><?php echo $s->term; ?></option>
			<?php } ?>
			</select>
		</div>
		<div class="field">
			<label for="ecolex">Ecolex synonym term</label>
			<?php $ecolex_terms = $page_data->get_ecolex_terms(); ?>
			<select id="ecolex" name="ecolex">
				<option value="">-- Please select --</option>
			<?php
				foreach($ecolex_terms as $s) {
					$tt = $page_data->get_term_ecolex_synonym($term->id);
					$selected = ($tt && $tt->id == $s->id) ? ' selected="selected"' : '';
			?>
				<option value="<?php echo $s->id; ?>"<?php echo $selected; ?>><?php echo $s->term; ?></option>
			<?php } ?>
			</select>
		</div>
		<div class="field">
			<label for="gemet">GEMET synonym term</label>
			<?php $gemet_terms = $page_data->get_gemet_terms(); ?>
			<select id="gemet" name="gemet">
				<option value="">-- Please select --</option>
			<?php
				foreach($gemet_terms as $s) {
					$tt = $page_data->get_term_gemet_synonym($term->id);
					$selected = ($tt && $tt->id == $s->id) ? ' selected="selected"' : '';
			?>
				<option value="<?php echo $s->id; ?>"<?php echo $selected; ?>><?php echo $s->term; ?></option>
			<?php } ?>
			</select>
		</div>
		<?php } ?>
		<p class="submit">
			<input name="save" type="submit" class="button-primary" value="<?php esc_attr_e('Save changes'); ?>" />
		</p>
	</form>

	<form action="" method="post" onsubmit="return confirm('This will delete PERMANENTLY the term and its relationships! Are you sure?');">
		<?php wp_nonce_field('informea-admin_voc_relationships'); ?>
		<input type="hidden" name="page" value="thesaurus" />
		<input type="hidden" name="act" value="voc_relationships" />
		<input type="hidden" name="id_term" value="<?php echo $id_term ?>" />
		<input name="delete" type="submit" class="button" value="<?php esc_attr_e('Delete this term'); ?>" />
	</form>
<?php } ?>
</div>
