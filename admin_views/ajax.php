<?php
//TODO: check_ajax_referer($nonce, $query_arg, $die) - http://codex.wordpress.org/Function_Reference/check_ajax_referer
add_action('wp_ajax_generate_terms_tree', array('TermsAjaxRequest', 'generate_terms_tree'));
add_action('wp_ajax_load_term_json', array('TermsAjaxRequest', 'load_term_json'));
add_action('wp_ajax_update_term', array('TermsAjaxRequest', 'update_term'));
add_action('wp_ajax_update_term_hierarchy', array('TermsAjaxRequest', 'update_term_hierarchy'));
add_action('wp_ajax_manipulate_term', array('TermsAjaxRequest', 'manipulate_term')); // cut/copy & paste as narrower term
add_action('wp_ajax_unlink_term', array('TermsAjaxRequest', 'unlink_term')); // unlink term as narrower
add_action('wp_ajax_create_term', array('TermsAjaxRequest', 'create_term'));
add_action('wp_ajax_synonym_autocomplete', array('TermsAjaxRequest', 'synonym_autocomplete'));


function _validation_errors($errors) {
	foreach($errors as $error) {
		echo $error . "\n";
	}
}

function _error_header() {
	header('HTTP/1.1 500 Internal Server Error');
}


class TermsAjaxRequest {


	function generate_terms_tree() {
		check_ajax_referer('ajax-informea-vocabulary');
		$ob = new TermsAjaxRequest();
		$ob->_inner_generate_terms_tree();
	}

	function _inner_generate_terms_tree() {

		header('Content-Type:application/xml');
		echo '<?xml version="1.0" encoding="iso-8859-1"?>';
		$db = new Thesaurus(NULL);
		$id = get_request_value('id');
		$noid = get_request_boolean('noid');
		$rootId = '0';
		$roots = array();
		if(!$id) {
			$id = '0';
			$roots = $db->get_top_concepts();
		} else {
			$arr = explode('_', $id);
			$rootId = $arr[1];
			$roots = $db->get_narrower_terms($arr[1]);
		}

		echo '<tree id="' . $id . '">' . "\n";
		// Lambda function - Recursively generate the sub-items
		$recursive_gen = function($parentId, $term) use(&$recursive_gen, $db, $noid) {
			$nodeId = $parentId . '_' . $term->id . '_' . mt_rand(1, 1999999999);
			echo '<item id="' . $nodeId . '" text="' . esc_attr($term->term) . '" tooltip="' . esc_attr($term->description) . ($noid ? '' : ' (term id:' . $term->id . ')') . '">' . "\n";
			echo '<userdata name="term_id">' . $term->id . '</userdata>';
			$narrower = $db->get_narrower_terms($term->id);
			foreach($narrower as $narrow) {
				echo $recursive_gen($term->id, $narrow);
			}
			echo "\n" . '</item>';
		};

		foreach($roots as $row) {
			echo $recursive_gen($rootId, $row);
		}
		echo '</tree>';
		die();
	}


	function load_term_json() {
		check_ajax_referer('ajax-informea-vocabulary');
		$id = get_request_value('id');
		if($id) {
			$db = new Thesaurus(NULL);
			header('Content-Type:application/json');
			$term = $db->get_term($id);
			$related = $db->get_related_terms($id);
			$synonyms = $db->get_synonyms($id);
			$ob = new StdClass();
			$ob->term = $term;
			$ob->related = $related;
			$ob->synonyms = $synonyms;
			echo json_encode($ob);
			die();
		} else {
			die('Missing mandatory term id from request');
		}
	}


	function update_term() {
		check_ajax_referer('ajax-informea-vocabulary');
		header('Content-Type:application/json');
		$db = new Thesaurus(NULL);
		if($db->validate_voc_edit_term()) {
			$db->voc_edit_term(false, true);
			$ob = new StdClass();
			$ob->success = true;
			$ob->treeNodeId = get_request_value('treeNodeId');
			echo json_encode($ob);
		} else {
			_validation_errors($db->errors);
		}
		die();
	}


	/**
	 * Called when drag-n-drop happens in the terms tree, to mark terms
	 * as 'narrower', respectively 'broader' to each other.
	 */
	function update_term_hierarchy() {
		check_ajax_referer('ajax-informea-vocabulary');
		header('Content-Type:text/plain');
		$db = new Thesaurus(NULL);

		$validator = new FormValidator();
		$validator->addValidation("child", "req", "Fatal: Missing child term");
		$validator->addValidation("newParent", "req", "Fatal: Missing new parent term");
		$validator->addValidation("oldParent", "req", "Fatal: Missing target old parent term");
		$valid = $validator->ValidateForm();
		$errors = $validator->GetErrors();
		if(count($errors) == 0) {
			$child = get_request_int('child');
			$newParent = get_request_int('newParent');
			$oldParent = get_request_int('oldParent');
			try {
				$db->update_term_hierarchy($child, $oldParent, $newParent);
				echo 'Term was successfully updated!';
			} catch(Exception $e) {
				_error_header();
				echo $e->getMessage();
				echo "\n";
				echo 'Term could not be updated. Please contact technical support.';
			}
		} else {
			_validation_errors($errors);
		}
		die();
	}


	function manipulate_term() {
		check_ajax_referer('ajax-informea-vocabulary');
		header('Content-Type:text/plain');
		$term = get_request_int('term');
		$oldParent = get_request_int('oldParent');
		$newParent = get_request_int('newParent');
		$op = get_request_value('op');
		$db = new Thesaurus(NULL);

		$validator = new FormValidator();
		$validator->addValidation("term", "req", "Fatal: Missing term");
		$validator->addValidation("newParent", "req", "Fatal: Missing new parent term");
		$validator->addValidation("oldParent", "req", "Fatal: Missing target old parent term");
		$validator->addValidation("op", "req", "Fatal: Missing operation");
		$valid = $validator->ValidateForm();
		$errors = $validator->GetErrors();
		if(count($errors) == 0) {
			try {
				$db->update_term_hierarchy($term, $oldParent, $newParent, 'copy_term' == $op);
				echo 'Term was successfully updated!';
			} catch(Exception $e) {
				_error_header();
				echo $e->getMessage();
				echo "\n";
				echo 'Term could not be updated. Please contact technical support.';
			}
		} else {
			_validation_errors($errors);
		}
		die();
	}


	function unlink_term() {
		check_ajax_referer('ajax-informea-vocabulary');
		header('Content-Type:text/plain');
		$term = get_request_int('term');
		$parent = get_request_int('parent');
		$db = new Thesaurus(NULL);

		$validator = new FormValidator();
		$validator->addValidation("term", "req", "Fatal: Missing term");
		$validator->addValidation("parent", "req", "Fatal: Missing parent term");
		$valid = $validator->ValidateForm();
		$errors = $validator->GetErrors();
		if(count($errors) == 0) {
			try {
				$db->unlink_term($term, $parent);
				echo 'Term was successfully unlinked!';
			} catch(Exception $e) {
				_error_header();
				echo $e->getMessage();
				echo "\n";
				echo 'Term could not be updated. Please contact technical support.';
			}
		} else {
			_validation_errors($errors);
		}
		die();
	}


	function create_term() {
		check_ajax_referer('ajax-informea-vocabulary');
		header('Content-Type:application/json');
		$db = new Thesaurus(NULL);
		if($db->validate_voc_add_term()) {
			$id_term = $db->voc_add_term();
			$ob = new StdClass();
			$ob->success = true;
			$ob->id = $id_term;
			$ob->treeNodeId = get_request_value('treeNodeId');
			echo json_encode($ob);
		} else {
			_validation_errors($db->errors);
		}
		die();
	}


	function synonym_autocomplete() {
		check_ajax_referer('ajax-informea-vocabulary');
		header('Content-Type:application/json');
		$id_concept = get_request_int('id');
		$key = get_request_value('key');
		$db = new Thesaurus(NULL);
		$synonyms = $db->synonym_autocomplete($id_concept, $key);
		$arr = array();
		foreach($synonyms as $term) {
			$ob = new StdClass();
			$ob->id = $term->id;
			$ob->synonym = $term->synonym;
			$ob->label = $term->synonym;
			$arr[] = $ob;
		}
		$ret = new StdClass();
		$ret->data = $arr;
		echo json_encode($arr);
		die();
	}
}
?>
