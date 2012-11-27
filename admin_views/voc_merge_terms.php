<?php
	$terms = $page_data->get_voc_concept_informea();
?>
<div class="wrap">
	<div id="breadcrumb">
		You are here:
		<a href="<?php echo bloginfo('url');?>/wp-admin/admin.php?page=thesaurus">Vocabulary</a>
		&raquo;
		<a href="<?php echo bloginfo('url');?>/wp-admin/admin.php?page=thesaurus&act=voc_merge_terms">Merge two terms into one</a>
	</div>
	<div id="icon-tools" class="icon32"><br></div>
	<h2>Merge two terms into one</h2>
	<p>
		From this page you can merge two terms into one. Here are the steps:
		<ol>
			<li>
				From this page select the <strong>merged</strong> and the <strong>destination</strong> term and press <strong>Merge</strong>.
				<em style="text-decoration: underline">Destination term will replace merged term and remove it entirely from database!</em>
			</li>
			<li>You can go to the <strong>destination</strong> term edit page and rename it, create associations etc.</li>
		</ol>
	</p>
	<?php if($page_data->actioned) { ?>
	<div class="updated settings-error" id="setting-error-settings_updated">
		<?php if($page_data->success) {?>
			<p><strong>Terms were successfully merged!</p>
		<?php } ?>
		<?php if(!$page_data->success) {?>
			<p><strong>Error merging terms!</strong>
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
		<input type="hidden" name="page" value="thesaurus" />
		<input type="hidden" name="act" value="voc_merge_terms" />
		Merged term (to be removed)
		<select name="merged">
			<?php foreach($terms as $term) { ?>
			<option value="<?php echo $term->id;?>"><?php echo $term->term; ?></option>
			<?php } ?>
		</select>
		<em>You can only merge terms from InforMEA vocabulary</em>
		<br />
		<br />
		Destination term (to replace with)
		<select name="destination">
			<?php foreach($terms as $term) { ?>
			<option value="<?php echo $term->id;?>"><?php echo $term->term; ?></option>
			<?php } ?>
		</select>
		<em>Note: Terms must be different!</em>
		<br />
		<input class="button-primary" type="submit" name="actioned" value="Merge!" />
	</form>
</div>
