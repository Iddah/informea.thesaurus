<?php
$statistics = $page_data->get_terms_usage_statistics();

/* Used to expand example: ?page=thesaurus&id_treaty=3&id_term=180 */
$id_treaty = get_request_value('id_treaty');
$id_term = get_request_value('id_term');
$id_decision = get_request_value('id_decision');
?>
<style type="text/css">
	form {
		display: inline;
		padding: 0;
		margin: 0;
	}
	input {
		margin: 0;
		padding: 0;
		display: inline;
	}
</style>
<script src="<?php bloginfo('template_directory'); ?>/scripts/jquery-min.js"></script>
<script type="text/javascript">
var img_expand = '<?php bloginfo('template_directory'); ?>/images/expand.gif';
var img_collapse = '<?php bloginfo('template_directory'); ?>/images/collapse.gif';

function expand(id, prefix) {
	var element = $('#' + prefix + '-' + id);
	var show = element.css('display');
	if(show == 'none') {
		element.show('fast');
		$('#sh-' + prefix + '-' + id).attr('src', img_collapse);
	} else {
		element.hide('fast');
		$('#sh-' + prefix + '-' + id).attr('src', img_expand);
	}
}
</script>
<div class="wrap">
	<div id="breadcrumb">
		You are here: <a href="<?php echo bloginfo('url');?>/wp-admin/admin.php?page=thesaurus">Vocabulary</a>
	</div>
	<div id="icon-tools" class="icon32"><br></div>
	<h2>Manage vocabulary of terms</h2>
	<p>
		Manage the vocabulary of terms.
		You can add new terms, edit or delete existing terms and define relationships between terms such as defining broader, narrower, related terms etc.
	</p>
	<h3>Actions</h3>
	<ul>
		<li><a href="<?php echo get_bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus&act=voc_manage_terms">Manage terms relationships</a></li>
		<li><a href="<?php echo get_bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus&act=voc_add_term">Add new term</a></li>
		<li><a href="<?php echo get_bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus&act=voc_relationships">Edit terms</a></li>
		<li><a href="<?php echo get_bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus&act=voc_source" title="View/Edit the vocabulary sources. These define the sources from where terms are collected">Manage vocabulary sources</a></li>
		<li><a href="<?php echo get_bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus&act=voc_group_themes">Group &amp; sort themes</a></li>
		<li><a href="<?php echo get_bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus&act=voc_merge_terms">Merge two terms into one</a></li>
		<li><a href="<?php echo get_bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus&act=voc_clone_term">Clone term</a></li>
	</ul>

	<h3>Terms usage statistics</h3>
	<p>
		<em>Note: Terms with no definition are marked in red</em>
	</p>
	<p>
		Below you can see the usage of terms as they were used to tag treaties and decisions.
		<br />
		For treaty the count covers both articles and paragraphs. For decisions the count covers both decisions and paragraphs.
		<br />

	</p>
	<table class="widefat fixed">
		<thead>
			<tr>
				<th width="4%">Edit</th>
				<th>Term</th>
				<th>Treaties</th>
				<th>Decisions</th>
			</tr>
		</thead>
		<tbody>
		<?php
			$count = 0;
			foreach($statistics as $row) {
		?>
		<tr<?php echo ($count % 2 == 0) ? ' class="alternate"' : ''; ?>">
			<td><a name="anchor-term-<?php echo $row->id ?>" href="<?php bloginfo('url');?>/wp-admin/admin.php?page=thesaurus&act=voc_relationships&id_term=<?php echo $row->id;?>">Edit</a></td>
			<td<?php echo ($row->description === NULL || $row->description == '') ? ' style="color:red"' : '';?>><?php echo $row->term; ?></td>
			<td>
				<?php echo $row->count_treaty; ?>
				<?php if($row->count_treaty > 0) { ?>
					<?php if($row->id == $id_term && !empty($id_treaty)) { ?>
					<a class="expand-collapse" href="javascript:expand('<?php echo $row->id;?>', 'treaty');"><img id="sh-treaty-<?php echo $row->id;?>" src="<?php bloginfo('template_directory'); ?>/images/collapse.gif" /></a>
					<div id="treaty-<?php echo $row->id;?>">
					<?php } else { ?>
					<a class="expand-collapse" href="javascript:expand('<?php echo $row->id;?>', 'treaty');"><img id="sh-treaty-<?php echo $row->id;?>" src="<?php bloginfo('template_directory'); ?>/images/expand.gif" /></a>
					<div id="treaty-<?php echo $row->id;?>" style="display: none">
					<?php } ?>
						<?php
							foreach($row->treaties as $treaty) {
						?>
							<h3>
								<?php
									if($treaty->tagged) {
								?>
									<a href="<?php bloginfo('url');?>/treaties/<?php echo $treaty->id; ?>" title="This treaty is tagged with '<?php echo esc_attr($row->term); ?>'"><?php echo $treaty->short_title; ?></a>
									<a href="<?php bloginfo('url');?>/wp-admin/admin.php?page=informea_treaties&act=treaty_edit_treaty&id=<?php echo $treaty->id;?>"
										title="Edit this treaty"><img src="<?php bloginfo('template_directory'); ?>/images/edit.png" /></a>
									<form action="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus#anchor-term-<?php echo $row->id ?>" method="post" onsubmit="return confirm('Are you sure you want to remove this relationship?');">
										<input type="hidden" name="act" value="treaty_vocabulary" />
										<input type="hidden" name="id_term" value="<?php echo $row->id; ?>" />
										<input type="hidden" name="id_treaty" value="<?php echo $treaty->id; ?>" />
										<input type="hidden" name="term" value="<?php echo $row->term; ?>" />
										<input type="hidden" name="treaty" value="<?php echo $treaty->short_title; ?>" />
										<input type="image" src="<?php bloginfo('template_directory'); ?>/images/delete.png" title="Remove the term from treaty" />
									</form>
								<?php } else { ?>
									<?php echo $treaty->short_title; ?>
									<a href="<?php bloginfo('url');?>/treaties/<?php echo $treaty->id; ?>" title="View treaty. This treaty is not tagged, but below are its articles and/or paragraphs tagged with this term"><img src="<?php bloginfo('template_directory'); ?>/images/eye.png" /></a>
								<?php } ?>
							</h3>
							<?php if(count($treaty->articles)) { ?>
							<table>
								<tr>
									<th>Article</th>
									<th>Paragraphs</th>
								</tr>
								<?php foreach($treaty->articles as $article) { ?>
								<tr>
									<td>
										<?php if($article->tagged) { ?>
										<a href="<?php bloginfo('url');?>/treaties/<?php echo $treaty->id; ?>?id_treaty_article=<?php echo $article->id; ?>#article_<?php echo $article->id; ?>" title="Article is tagged with '<?php echo esc_attr($row->term); ?>'"><?php echo $article->official_order . ' ' . $article->title; ?></a>
										<a href="<?php bloginfo('url');?>/wp-admin/admin.php?page=informea_treaties&act=treaty_edit_article&id_treaty=<?php echo $treaty->id;?>&id_treaty_article=<?php echo $article->id; ?>" title="Edit this article"><img src="<?php bloginfo('template_directory'); ?>/images/edit.png" /></a>
										<form action="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus#anchor-term-<?php echo $row->id ?>" method="post" onsubmit="return confirm('Are you sure you want to remove this relationship?');">
											<input type="hidden" name="act" value="treaty_article_vocabulary" />
											<input type="hidden" name="id_term" value="<?php echo $row->id; ?>" />
											<input type="hidden" name="id_treaty" value="<?php echo $treaty->id; ?>" />
											<input type="hidden" name="term" value="<?php echo $row->term; ?>" />
											<input type="hidden" name="treaty" value="<?php echo $treaty->short_title; ?>" />
											<input type="hidden" name="id_article" value="<?php echo $article->id; ?>" />
											<input type="hidden" name="article" value="<?php echo $article->official_order . ' ' . $article->title; ?>" />
											<input type="image" src="<?php bloginfo('template_directory'); ?>/images/delete.png" title="Remove the term from article" />
										</form>
									<?php } else { ?>
										<?php echo $article->official_order . ' ' . $article->title; ?>
										<a title="View article. This article is not tagged, but has paragraphs tagged with this term" href="<?php bloginfo('url');?>/treaties/<?php echo $treaty->id; ?>?id_treaty_article=<?php echo $article->id; ?>#article_<?php echo $article->id; ?>"><img src="<?php bloginfo('template_directory'); ?>/images/eye.png" /></a>
									<?php } ?>
									</td>
									<td>
										<?php foreach($article->paragraphs as $paragraph) { ?>
											<a href="<?php bloginfo('url');?>/treaties/<?php echo $treaty->id; ?>?id_treaty_article=<?php echo $article->id; ?>#article_<?php echo $article->id; ?>_paragraph_<?php echo $paragraph->id; ?>" title="Paragraph is tagged with '<?php echo esc_attr($row->term); ?>'"><?php echo $paragraph->order; ?></a>
											<a href="<?php bloginfo('url');?>/wp-admin/admin.php?page=informea_treaties&act=treaty_edit_article_paragraph&id_treaty=<?php echo $treaty->id;?>&id_treaty_article=<?php echo $article->id; ?>&id_treaty_article_paragraph=<?php echo $paragraph->id; ?>"
												title="Edit this paragraph"><img src="<?php bloginfo('template_directory'); ?>/images/edit.png" /></a>
											<form action="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus#anchor-term-<?php echo $row->id ?>" method="post" onsubmit="return confirm('Are you sure you want to remove this relationship?');">
												<input type="hidden" name="act" value="treaty_article_paragraph_vocabulary" />
												<input type="hidden" name="id_term" value="<?php echo $row->id; ?>" />
												<input type="hidden" name="id_treaty" value="<?php echo $treaty->id; ?>" />
												<input type="hidden" name="term" value="<?php echo $row->term; ?>" />
												<input type="hidden" name="treaty" value="<?php echo $treaty->short_title; ?>" />
												<input type="hidden" name="id_paragraph" value="<?php echo $paragraph->id; ?>" />
												<input type="hidden" name="article" value="<?php echo $article->official_order . ' ' . $article->title; ?>" />
												<input type="hidden" name="paragraph" value="<?php echo $paragraph->order; ?>" />
												<input type="image" src="<?php bloginfo('template_directory'); ?>/images/delete.png" title="Remove the term from paragraph <?php echo $paragraph->id; ?>" />
											</form>
										<?php } ?>
									</td>
								</tr>
								<?php } ?>
							</table>
							<?php } ?>
						<?php
							}
						?>
					</div>
				<?php } ?>
			</td>
			<td>
				<?php echo $row->count_decision; ?>
				<?php if($row->count_decision > 0) { ?>
					<?php if($row->id == $id_term && !empty($id_decision)) { ?>
					<a class="expand-collapse" href="javascript:expand('<?php echo $row->id;?>', 'decision');"><img id="sh-decision-<?php echo $row->id;?>" src="<?php bloginfo('template_directory'); ?>/images/collapse.gif" /></a>
					<div id="decision-<?php echo $row->id;?>" style="display: block;">
					<?php } else { ?>
					<a class="expand-collapse" href="javascript:expand('<?php echo $row->id;?>', 'decision');"><img id="sh-decision-<?php echo $row->id;?>" src="<?php bloginfo('template_directory'); ?>/images/expand.gif" /></a>
					<div id="decision-<?php echo $row->id;?>" style="display: none;">
					<?php } ?>
						<?php
							foreach($row->treaty_decisions as $treaty) {
						?>
							<h3>
								<?php echo $treaty->short_title; ?>
								<a title="View treaty. This treaty has decision tagged with this term" href="<?php bloginfo('url');?>/treaties/<?php echo $treaty->id; ?>">
									<img src="<?php bloginfo('template_directory'); ?>/images/eye.png" />
								</a>
							</h3>
							<table>
								<tr>
									<th>&nbsp;</th>
									<th>Number</th>
									<th>Title</th>
								</tr>
								<?php
									foreach($treaty->decisions as $decision) {
								?>
								<tr>
									<td>
										<a href="<?php bloginfo('url');?>/wp-admin/admin.php?page=informea_decisions&act=decision_edit_tags&id_treaty=<?php echo $treaty->id;?>&id_decision=<?php echo $decision->id; ?>"
											title="Edit tagging for this decision"><img src="<?php bloginfo('template_directory'); ?>/images/edit.png" /></a>
										<form action="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=thesaurus#anchor-term-<?php echo $row->id ?>" method="post" onsubmit="return confirm('Are you sure you want to remove this relationship?');">
											<input type="hidden" name="act" value="decision_vocabulary" />
											<input type="hidden" name="id_term" value="<?php echo $row->id; ?>" />
											<input type="hidden" name="id_decision" value="<?php echo $decision->id; ?>" />
											<input type="hidden" name="term" value="<?php echo $row->term; ?>" />
											<input type="hidden" name="decision" value="<?php echo $decision->short_title . ' - ' . $decision->long_title; ?>" />
											<input type="image" src="<?php bloginfo('template_directory'); ?>/images/delete.png" title="Remove the term from decision" />
										</form>
									</td>
									<td><?php echo $decision->number; ?></td>
									<td><?php echo $decision->short_title . ' - ' . $decision->long_title; ?></td>
								</tr>
								<?php } ?>
							</table>
						<?php } ?>
					</div>
				<?php } ?>
			</td>
		</tr>
		<?php
				$count++;
			}
		?>
		</tbody>
	</table>
</div>
