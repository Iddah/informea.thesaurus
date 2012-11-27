<div class="wrap">
	<div id="breadcrumb">
		You are here:
		<a href="<?php echo bloginfo('url');?>/wp-admin/admin.php?page=thesaurus">Vocabulary</a>
		&raquo;
		<a href="<?php echo bloginfo('url');?>/wp-admin/admin.php?page=thesaurus&act=voc_add_term">Create new term</a>
	</div>
	<div id="icon-tools" class="icon32"><br></div>
	<h2>Create new term</h2>
	<p>
		Add new term into the vocabulary. Please enter the details below and press Submit.
	</p>
	<?php if($page_data->actioned) {?>
	<div class="updated settings-error" id="setting-error-settings_updated">
		<?php if($page_data->success) {?>
			<p><strong>Term was successfully created!
			Add <a href="<?php echo get_bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus&act=voc_add_term">new one</a>,
			go <a href="<?php echo get_bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus">back</a>
			or <a href="<?php echo get_bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus&act=voc_relationships&id_term=<?php echo $last_insert_id; ?>">edit details</a>.</strong></p><?php } ?>
		<?php if(!$page_data->success) {?>
			<p><strong>Error adding term!</strong>
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
		<?php wp_nonce_field('informea-admin_voc_add_term'); ?>
		<input type="hidden" name="page" value="thesaurus" />
		<input type="hidden" name="act" value="voc_add_term" />
		<table>
			<tr>
				<td><label for="term">Term *</label></td>
				<td><input type="text" size="40" id="term" name="term" value="<?php $page_data->get_value_e('term');?>" /></td>
			</tr>
			<tr>
				<td><label for="description">Description</label></td>
				<td><textarea rows="5" cols="40" id="description" name="description" value=""><?php $page_data->get_value_e('description');?></textarea></td>
			</tr>
			<tr>
				<td><label for="reference_url">Reference URL</label></td>
				<td><input type="text" size="60" id="reference_url" name="reference_url" value="<?php $page_data->get_value_e('reference_url');?>" /></td>
			</tr>
			<tr>
				<td><label for="tag">Tag (used for tag cloud)</label></td>
				<td><input type="text" size="60" id="tag" name="tag" value="<?php $page_data->get_value_e('tag', TRUE);?>" /></td>
			</tr>
			<tr>
				<td><label for="id_source">Source *</label></td>
				<td>
					<select id="id_source" name="id_source">
						<option value="">-- Please select --</option>
					<?php
					foreach($page_data->get_voc_source() as $row) {
						$checked = ($page_data->get_value('id_source') == $row->id || ($row->url == 'http://www.unep.org')) ? ' selected="selected"' : '';  // Added UNEP default to ease addition
						echo "<option value='{$row->id}'$checked>{$row->name}</option>";
					}?>
					</select>
				</td>
			</tr>
			<tr>
				<td><label for="top_concept">Top concept (theme)</label></td>
				<td><input type="checkbox" id="top_concept" name="top_concept" value="1" <?php if($page_data->get_value('top_concept')) { echo "checked='checked'"; }?> /></td>
			</tr>
			<tr>
				<td><label for="geg_tools_url">URL to GEG correspondent</label></td>
				<td><input type="text" size="60" id="geg_tools_url" name="geg_tools_url" value="<?php $page_data->get_value_e('geg_tools_url');?>" /></td>
			</tr>
		</table>
		<p>
		* - Required field(s)
		</p>
		<p class="submit">
			<input name="actioned" type="submit" class="button-primary" value="<?php esc_attr_e('Create term'); ?>" />
		</p>
	</form>
</div>
