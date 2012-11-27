<style type="text/css">
.field {
	margin-bottom: 10px;
}

.field label {
	width: 200px; float: left;
}
</style>
<div class="wrap">
	<div id="breadcrumb">
		You are here:
		<a href="<?php echo bloginfo('url');?>/wp-admin/admin.php?page=thesaurus">Vocabulary</a>
		&raquo;
		<a href="<?php echo bloginfo('url');?>/wp-admin/admin.php?page=thesaurus&act=voc_clone_term">Clone term</a>
	</div>
	<div id="icon-tools" class="icon32"><br></div>
	<h2>Clone term</h2>
	<p>
		This page allows you to create a 'clone' of a term. The new term will be assigned to the same paragraphs, decisions etc. as the old term, having only different text (value).
		<br />
		Managers will clone terms to new ones and then assign them accordingly to other paragraphs, avoiding to do all the work twice for new terms.
	</p>
	<?php if($page_data->actioned) {?>
	<div class="updated settings-error" id="setting-error-settings_updated">
		<?php if($page_data->success) {?>
			<p>
				<strong>Term was successfully cloned!</strong>
				<a href="<?php echo get_bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus&act=voc_relationships&id_term=<?php echo $last_insert_id; ?>">Edit details</a>
			</p>
		<?php } ?>
		<?php if(!$page_data->success) {?>
			<p><strong>Error cloning term!</strong>
				<ul>
				<?php foreach($page_data->errors as $inpname => $inp_err)
				{
					echo "<li>$inpname : $inp_err</li>";
				}?>
				</ul>
			</p>
		<?php } ?>
	</div>
	<?php } ?>
	<form action="" method="post">
		<?php wp_nonce_field('informea-admin_voc_clone_term'); ?>
		<input type="hidden" name="page" value="thesaurus" />
		<input type="hidden" name="act" value="voc_clone_term" />

		<div class="field">
			<label for="id_cloned">Term to clone *</label>
			<select id="id_cloned" name="id_cloned">
				<option value="">-- Please select --</option>
				<?php
				$sel = get_request_value('id_cloned');
				foreach($page_data->get_voc_concept_informea() as $row) {
					$selected = $sel == $row->id ? ' selected="selected"' : '';
					echo "<option value='{$row->id}'$selected>{$row->term}</option>";
				}?>
			</select>
			<em>You can only clone InforMEA source terms!</em>
		</div>
		<div class="field">
			<label for="term">Cloned term *</label>
			<input class="input" type="text" size="42" id="term" name="term" value="<?php echo get_request_value('term');?>" />
		</div>
		<div class="field">
			<label for="description">Description</label>
			<textarea class="input" rows="5" cols="40" id="description" name="description" value=""><?php echo get_request_value('description');?></textarea>
		</div>



		<p>* - Required field(s)</p>
		<p class="submit">
			<input name="actioned" type="submit" class="button-primary" value="<?php esc_attr_e('Clone term'); ?>" />
		</p>
	</form>


