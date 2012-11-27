<?php if($page_data->actioned) { ?>
<div class="updated settings-error" id="setting-error-settings_updated">
	<?php if($page_data->success) {?>
		<p><strong>Term was successfully updated!</strong></p>
	<?php } else {?>
		<p><strong>Error editing term!</strong>
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
