<?php
	$themes = $page_data->get_top_concepts();
?>
<style>
	.sortable {
		list-style-type: none;
		margin: 0;
		padding: 0;
		width: 60%;
		min-height: 50px;
	}
	.sortable li {
		width: 500px;
		background-color: grey;
		border: 1px solid dark-grey;
		margin: 0 3px 3px 3px;
		padding: 0.4em;
		padding-left: 1.4em;
		font-size: 1.4em;
	}
	.sortable li span { position: absolute; margin-left: -1.3em; }

	#generic {
		width: 60em;
		padding: 1em;
		background-color: #4facdc;
	}

	#substantives {
		width: 60em;
		padding: 1em;
		background-color: #a0d773;
	}
</style>
<script src="<?php bloginfo('template_directory'); ?>/scripts/jquery-min.js"></script>
<script src="<?php bloginfo('template_directory'); ?>/scripts/ui.js"></script>

<script>
	$(function() {
		$( "#generic" ).sortable({connectWith: "ul", dropOnEmpty: true});
		$( "#generic" ).disableSelection();
		$( "#substantives" ).sortable({connectWith: "ul", dropOnEmpty: true});
		$( "#substantives" ).disableSelection();
		$( "#form-reorder" ).submit(function() {
			var generic = $( "#generic" ).sortable('toArray');
			for(var i = 0; i < generic.length; i++) {
				$('#input-group-' + generic[i]).val(0);
				$('#input-sort-' + generic[i]).val(i + 1);
			}
			var substantives = $( "#substantives" ).sortable('toArray');
			for(var i = 0; i < substantives.length; i++) {
				$('#input-group-' + substantives[i]).val(1);
				$('#input-sort-' + substantives[i]).val(i + 1);
			}
			return true;
		});
	});
</script>

<div class="wrap">
	<div id="breadcrumb">
		You are here:
		<a href="<?php echo bloginfo('url');?>/wp-admin/admin.php?page=thesaurus">Vocabulary</a>
		&raquo;
		<a href="<?php echo bloginfo('url');?>/wp-admin/admin.php?page=thesaurus&act=voc_group_themes">Group themes into generic/substantives</a>
	</div>
	<div id="icon-tools" class="icon32"><br></div>
	<h2>Group themes into generic/substantives</h2>
	<p>
		From this page you can group themes into substantives (green) or generic (blue) pockets.
	</p>
	<em><strong>Drag and drop</strong> themes into correct place, then press <strong>Save changes</strong></em>
</div>
<?php if($page_data->actioned) {?>
<div class="updated settings-error" id="setting-error-settings_updated">
	<?php if($page_data->success) {?>
		<p>
			<strong>Order was successfully updated!</strong>
			<a href="<?php bloginfo('url'); ?>/terms">See it live</a>
		</p>
	<?php } else {?>
		<p><strong>Error updating the order, lease report this to technical people!</strong></p>
	<?php } ?>
</div>
<?php } ?>

<form id="form-reorder" action="" method="post">
	<input type="hidden" name="page" value="thesaurus" />
	<input type="hidden" name="act" value="voc_group_themes" />
	<input type="hidden" name="save" value="true" />
	<div class="generic">
		<h2>Generic</h2>
		<ul id="generic" class="droptrue sortable">
	<?php
		foreach($themes as $term) {
			if($term->substantive == 0) {
	?>
		<li id="theme-<?php echo $term->id;?>" class="button-secondary action">
			<?php echo $term->term;?>
			<input type="hidden" id="input-sort-theme-<?php echo $term->id; ?>" name="theme-sort-<?php echo $term->id; ?>" value="<?php echo $term->order; ?>" />
			<input type="hidden" id="input-group-theme-<?php echo $term->id; ?>" name="theme-group-<?php echo $term->id; ?>" value="<?php echo $term->substantive; ?>" />
		</li>
	<?php
			}
		}
	?>
		</ul>
	</div>

	<div class="substantives">
		<h2>Substantives</h2>
		<ul id="substantives" class="droptrue sortable">
	<?php
		foreach($themes as $term) {
			if($term->substantive == 1) {
	?>
		<li id="theme-<?php echo $term->id;?>" class="button-secondary action">
			<?php echo $term->term;?>
			<input type="hidden" id="input-sort-theme-<?php echo $term->id; ?>" name="theme-sort-<?php echo $term->id; ?>" value="<?php echo $term->order; ?>" />
			<input type="hidden" id="input-group-theme-<?php echo $term->id; ?>" name="theme-group-<?php echo $term->id; ?>" value="<?php echo $term->substantive; ?>" />
		</li>
	<?php
			}
		}
	?>
		</ul>
	</div>
	<br />
	<input name="actioned" type="submit" class="button-primary" value="<?php esc_attr_e('Save changes'); ?>" />
</form>
