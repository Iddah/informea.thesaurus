<?php
class ThesaurusAdmin {

	function router() {
		$success = False;
		$page_data = new Thesaurus(NULL);
		$act = get_request_value('act');
		$actioned = get_request_value('actioned');
		$save = get_request_value('save');
		$delete = get_request_value('delete');

		if($act == 'voc_manage_terms') {
			return include(dirname(__FILE__) . '/admin_views/voc_manage_terms.html.php');
		}

		if($act == 'voc_add_term') {
			if($actioned && $page_data->validate_voc_add_term()) {
				if($page_data->security_check('informea-admin_voc_add_term')) {
					$last_insert_id = $page_data->voc_add_term();
				} else {
					die('Security error');
				}
			}
			return include(dirname(__FILE__) . '/admin_views/voc_add_term.php');
		}


		if($act == 'voc_relationships') {
			if($save && $page_data->validate_voc_edit_term()) {
				if($page_data->security_check('informea-admin_voc_relationships')) {
					$page_data->voc_edit_term();
				} else {
					die('Security error');
				}
			}
			if($delete) {
				if($page_data->voc_delete_term()) {
					unset($_POST['id_term']);
					return include(dirname(__FILE__) . '/admin_views/index.php');
				}
			}
			return include(dirname(__FILE__) . '/admin_views/voc_edit_term.php');
		}


		if($act == 'voc_clone_term') {
			if($actioned && $page_data->validate_voc_clone_term()) {
				if($page_data->security_check('informea-admin_voc_clone_term')) {
					$last_insert_id = $page_data->voc_clone_term();
				} else {
					die('Security error');
				}
			}
			return include(dirname(__FILE__) . '/admin_views/voc_clone.php');
		}

		if($act == 'voc_source') {
			if($actioned && $page_data->validate_voc_sources()) {
				$page_data->voc_sources();
			}
			return include(dirname(__FILE__) . '/admin_views/voc_sources.php');
		}

		if($act == 'voc_group_themes') {
			if($actioned) {
				$page_data->voc_save_theme_sort_group();
			}
			return include(dirname(__FILE__) . '/admin_views/voc_group_themes.php');
		}
		if($act == 'voc_merge_terms') {
			if($actioned) {
				$page_data->voc_merge_terms();
			}
			return include(dirname(__FILE__) . '/admin_views/voc_merge_terms.php');
		}
		// Delete terms relationships in main terms page (x delete button)
		if($act == 'treaty_vocabulary') {
			$page_data->unlink_term_treaty();
		}
		if($act == 'treaty_article_vocabulary') {
			$page_data->unlink_term_article();
		}
		if($act == 'treaty_article_paragraph_vocabulary') {
			$page_data->unlink_term_paragraph();
		}
		if($act == 'decision_vocabulary') {
			$page_data->unlink_term_decision();
		}
		// Default action
		return include(dirname(__FILE__) . '/admin_views/index.php');
	}
}
