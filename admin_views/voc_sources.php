<?php
	$sources = $page_data->get_voc_source();
?>
<div class="wrap">
	<div id="breadcrumb">
		You are here:
		<a href="<?php echo bloginfo('url');?>/wp-admin/admin.php?page=thesaurus">Vocabulary</a>
		&raquo;
		<a href="<?php echo bloginfo('url');?>/wp-admin/admin.php?page=thesaurus&act=voc_source">Edit vocabulary sources</a>
	</div>
	<div id="icon-tools" class="icon32"><br></div>
	<h2>Edit vocabulary sources</h2>
	Vocabulary sources are used to keep track of terms' source. Terms may come from different providers such as CITES, CBD etc.
	When you add a new term you must select its origin. If not available, use the form below to create a new source.

	<p><em>Note: At the moment you cannot remove the sources</em></p>
	<h3>Existing sources</h3>
	<table class="widefat page fixed">
		<thead>
			<tr>
				<th>ID (Unique)</th>
				<th>Name</th>
				<th>URL</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach($sources as $row) { ?>
			<tr>
				<td><?php echo $row->id; ?></td>
				<td><?php echo $row->name; ?></td>
				<td><?php echo $row->url; ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
	<h3>Create new term</h3>
	<?php if($page_data->actioned) {?>
	<div class="updated settings-error" id="setting-error-settings_updated">
		<?php if($page_data->success) {?><p><strong>Source was successfully added!</p><?php } ?>
		<?php if(!$page_data->success) {?>
			<p><strong>Error adding souce!</strong>
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
		<?php wp_nonce_field('informea-admin_voc_source'); ?>
		<input type="hidden" name="page" value="thesaurus" />
		<input type="hidden" name="act" value="voc_source" />
		<table>
			<tr>
				<td><label for="name">Name *</label></td>
				<td><input type="text" size="60" id="name" name="name" value="" /></td>
			</tr>
			<tr>
				<td><label for="url">URL</label></td>
				<td><input type="text" size="60" id="url" name="url" value="" /></td>
			</tr>
		</table>
		<p>
		* - Required field(s)
		</p>
		<p class="submit">
			<input name="actioned" type="submit" class="button-primary" value="<?php esc_attr_e('Create'); ?>" />
		</p>
	</form>
</div>
