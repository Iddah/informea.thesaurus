<?php
/*
Plugin Name: InforMEA Thesaurus
Plugin URI: http://informea.org
Description: Manage vocabulary inside InforMEA website. This plugin is part of the InforMEA project
Version: 1.0
Author: Cristian Romanescu <cristian.romanescu _at_ eaudeweb.ro>, Eau de Web
Author URI: http://www.eaudeweb.ro
License: Apache
*/

// Ajax required for tree operations inside administrationa area
require_once(dirname(__FILE__) . '/formvalidator.php');
require_once (dirname(__FILE__) . '/admin_views/ajax.php');
require_once(dirname(__FILE__) . '/admin.php');


// Ajax functions
add_action('wp_ajax_nopriv_suggest_terms', array('Thesaurus', 'ajax_suggest_terms'));
add_action('wp_ajax_suggest_terms', array('Thesaurus', 'ajax_suggest_terms'));

add_action('wp_ajax_generate_terms_tree_public', array('Thesaurus', '_inner_generate_terms_tree'));
add_action('wp_ajax_nopriv_generate_terms_tree_public', array('Thesaurus', '_inner_generate_terms_tree'));

// External dependency - http://svn.eaudeweb.ro/informea/trunk/www/wp-content/plugins/informea/pages/page_base.class.php
require_once (WP_PLUGIN_DIR . '/informea/pages/page_base.class.php');

class Thesaurus extends imea_page_base_page {

	private $id_term = NULL;

	public $term = null;
	public $related = array();

	function __construct($id_term = null, $arr_parameters = array()) {
		parent::__construct($arr_parameters);
		$this->id_term = $id_term;
		if($id_term !== null) {
			$this->term = $this->get_term($id_term);
			$this->_get_details();
		}
	}


	function _get_details() {
		global $wpdb;
		$all = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM voc_relation
					JOIN voc_relation_type ON voc_relation.relation = voc_relation_type.id
					JOIN voc_concept ON voc_relation.target_term = voc_concept.id
				WHERE id_concept = %d ORDER BY popularity DESC",
				$this->id_term
			)
		);
		foreach($all as $rel) {
			$this->related[$rel->identification][] = $rel;
		}
	}


	/**
	 * Return the list of themes from the vocabulary
	 */
	function get_themes() {
		global $wpdb;
		$substantive = $wpdb->get_results('SELECT * FROM voc_concept WHERE id_source = 9 AND top_concept = 1 AND substantive = 1 ORDER BY `order` ASC');
		$generic = $wpdb->get_results('SELECT * FROM voc_concept WHERE id_source = 9 top_concept = 1 AND substantive = 0 ORDER BY `order` ASC');
		return array('substantive' => $substantive, 'generic' => $generic);
	}

	function get_top_concepts() {
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM voc_concept WHERE id_source = 9 AND top_concept = 1 ORDER BY `order` ASC ");
	}

	/**
	 * @return Array Terms from InforMEA vocabulary
	 */
	function get_themes_generic() {
		global $wpdb;
		return $wpdb->get_results('SELECT * FROM voc_concept WHERE id_source = 9 AND top_concept = 1 AND substantive = 0 ORDER BY `term` ASC');
	}


	/**
	 * @return Array Terms from InforMEA vocabulary
	 */
	function get_themes_substantives() {
		global $wpdb;
		return $wpdb->get_results('SELECT * FROM voc_concept WHERE id_source = 9 AND top_concept = 1 AND substantive = 1 ORDER BY `term` ASC');
	}


	function _inner_generate_terms_tree() {
		header('Content-Type:application/xml');
		echo '<?xml version="1.0" encoding="iso-8859-1"?>';
		$db = new Thesaurus(NULL);
		$rootId = '0';
		// Lambda function - Recursively generate the sub-items
		$recursive_gen = function($parentId, $term, $depth) use (&$recursive_gen, $db) {
			$nodeId = $parentId . '_' . $term->id . '_' . mt_rand(1, 1999999999);
			$popularity = esc_attr($term->popularity);
			$min_font_size = 1.5;
			$max_font_size = 1.5;

			$term_font_size = $popularity / 100;
			$term_font_size_after = $popularity % 100;
			if($term_font_size < $min_font_size) {
				$term_font_size = $min_font_size;
			}elseif($term_font_size > $max_font_size){
				$term_font_size = $max_font_size;
			}
			echo '<item id="' . $nodeId . '" text="' . esc_attr($term->term) . '"
				tooltip="' . esc_attr($term->description) . '"
				style="font-size:' . esc_attr($term_font_size - (0.1 * $depth)) . 'em; line-height: ' . esc_attr($term_font_size * 10) . 'px;">' . "\n";
			echo '<userdata name="term_id">' . $term->id . '</userdata>';
			$narrower = $db->get_narrower_terms($term->id);
			foreach($narrower as $narrow) {
				echo $recursive_gen($term->id, $narrow, $depth + 1);
			}
			echo "\n" . '</item>';
		};


		echo '<tree id="0">' . "\n";
		echo '<item id="substantives" text="Substantive terms" tooltip="Substantive terms common to MEAs" style="font-size:1.5em; line-height: 18px;">';
		$roots = $db->get_themes_substantives();
		foreach($roots as $row) {
			echo $recursive_gen($rootId, $row, 0);
		}
		echo '</item>';

		echo '<item id="generic" text="Generic terms" tooltip="Generic terms common to MEAs" style="font-size:1.5em; line-height: 18px;">';
		$roots = $db->get_themes_generic();
		foreach($roots as $row) {
			echo $recursive_gen($rootId, $row, 0);
		}
		echo '</item>';

		echo '</tree>';
		die();
	}

	/**
	 * @deprecated
	 */
	function _inner_generate_terms_tree_deprecated() {
		header('Content-Type:application/xml');
		echo '<?xml version="1.0" encoding="iso-8859-1"?>';
		$db = new Thesaurus(NULL);
		$substantives = get_request_boolean('substantives');
		$rootId = '0';
		$roots = array();
		$id = '0';

		if(!empty($substantives)) {
			$roots = $db->get_themes_substantives();
		} else {
			$roots = $db->get_themes_generic();
		}

		echo '<tree id="' . $id . '">' . "\n";
		// Lambda function - Recursively generate the sub-items
		$recursive_gen = function($parentId, $term, $depth) use (&$recursive_gen, $db) {
			$nodeId = $parentId . '_' . $term->id . '_' . mt_rand(1, 1999999999);
			$popularity = esc_attr($term->popularity);
			$min_font_size = 1.8;
			$max_font_size = 1.8;

			$term_font_size = $popularity / 100;
			$term_font_size_after = $popularity % 100;
			if($term_font_size < $min_font_size) {
				$term_font_size = $min_font_size;
			}elseif($term_font_size > $max_font_size){
				$term_font_size = $max_font_size;
			}
			echo '<item id="' . $nodeId . '" text="' . esc_attr($term->term) . '"
				tooltip="' . esc_attr($term->description) . '"
				style="font-size:' . esc_attr($term_font_size - (0.1 * $depth)) . 'em; line-height: ' . esc_attr($term_font_size * 10) . 'px;">' . "\n";
			echo '<userdata name="term_id">' . $term->id . '</userdata>';
			$narrower = $db->get_narrower_terms($term->id);
			foreach($narrower as $narrow) {
				echo $recursive_gen($term->id, $narrow, $depth + 1);
			}
			echo "\n" . '</item>';
		};

		foreach($roots as $row) {
			echo $recursive_gen($rootId, $row, 0);
		}
		echo '</tree>';
		die();
	}


	function expand_theme_terms($id_theme) {
		global $wpdb;
		// Retrieve full sub-tree of narrower terms
		$arr = array( $id_theme );
		$rows = $this->_rec_get_subterms( $arr );
		if(count($rows)) {
			$p = implode(',', $rows);
			if($p) {
				$ret = $wpdb->get_results("SELECT * FROM voc_concept WHERE id_source = 9 AND id IN ($p) = 1 ORDER BY term");
				return $ret;
			}
		}
		return array();
	}


	function _rec_get_subterms(&$root_nodes) {
		//var_dump($root_nodes);
		if(count($root_nodes)) {
			global $wpdb;
			$p = implode(',', $root_nodes);

			$sql = "SELECT b.target_term FROM voc_concept a
						INNER JOIN voc_relation b on b.id_concept = a.id
						INNER JOIN voc_relation_type c on c.id = b.relation
						WHERE a.id_source = 9 AND a.id IN ($p) AND c.identification = 'narrower'";
			//echo $sql . '<br />';
			$rows = $wpdb->get_results($sql);
			$ret = array();
			foreach( $rows as $row ) {
				$ret[] = $row->target_term;
			}
			$nrows = $this->_rec_get_subterms($ret);
			foreach( $rows as $row ) {
				$nrows[] = $row->target_term;
			}
			if(count($nrows)) {
				$ret = array_merge($ret, $nrows);
			}
			return array_unique($ret);
		}
	}

	/**
	 * Overriding
	 * @imea_page_base_page::is_index
	 */
	function is_index() {
		return $this->id_term == NULL;
	}

	function is_tabular_view() {
		return 'tabular' == get_request_variable('expand', 'str');
	}

	function get_alphabet_letters() {
		global $wpdb;
		$sql = "SELECT DISTINCT(UPPER(SUBSTR(term, 1, 1))) as letter FROM voc_concept WHERE id_source = 9 ORDER BY letter";
		return $wpdb->get_results($sql);
	}


	function index_alphabetical() {
		global $wpdb;
		$ret = array();
		$letters = $this->get_alphabet_letters();
		foreach($letters as $ob) {
			$sql = $wpdb->prepare("SELECT * FROM voc_concept WHERE id_source = 9 AND UPPER(SUBSTR(term, 1, 1)) = %s ORDER BY term", $ob->letter);
			$ret[$ob->letter] = $wpdb->get_results($sql);
		}
		return $ret;
	}

	function inc_popularity() {
		global $wpdb;
		if (!is_int($this->id_term)) {
			return;
		}

		$sql = $wpdb->prepare('SELECT popularity, rec_updated FROM voc_concept WHERE id = %d', $this->id_term);
		$result = $wpdb->get_row($sql);

		$popularity = intval($result->popularity);
		$rec_updated = $result->rec_updated;

		$wpdb->update('voc_concept', array(
				'popularity' => $popularity + 1,
				'rec_updated' => $rec_updated
			),
			array('id' => $this->id_term)
		);
	}


	/**
	 * @return id_term was set on GET but with invalid ID
	 */
	function is_404() {
		global $wp_query, $page_data;
		if(is_request_variable('id_term') && $page_data->term === NULL) {
			$wp_query->set_404();
			require TEMPLATEPATH.'/404.php';
			return TRUE;
		}
		return FALSE;
	}


	/**
	 * Append country to page title
	 * Called statically by wordpress framework
	 */
	function informea_page_title() {
		global $id_term, $page_data;
		if($id_term !== NULL) {
			return "{$page_data->term->term} | ";
		}
		return '';
	}


	/**
	 * Called statically by wordpress framework
	 */
	function breadcrumbtrail() {
		global $post, $id_term, $page_data;
		$tpl = " &raquo; <a href='%s'%s>%s</a>";
		$ret = '';
		if($post !== NULL) {
			if($id_term !== NULL) {
				$ret = sprintf($tpl, get_permalink(), '', $post->post_title);
				$ret .= " &raquo; <span class='current'>{$page_data->term->term}</span>";
			} else {
				$ret = " &raquo; <span class='current'>{$post->post_title}</span>";
			}
		}

		return $ret;
	}

	function get_synonyms($id_concept = NULL, $filter = NULL) {
		global $wpdb;
		$sql = "SELECT * FROM voc_synonym WHERE 1 = 1 ";
		if($id_concept) {
			$sql .= " AND id_concept=$id_concept ";
		}
		if($filter) {
			$sql .= " AND synonym LIKE '%$filter%' ";
		}
		$sql .= " GROUP BY synonym ORDER BY synonym";
		return $wpdb->get_results($sql);
	}


	function get_terms_list() {
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM voc_concept WHERE id_source = 9 ORDER BY term");
	}


	function csv_format_cell($value, $quot = '"', $sep = ',') {
		$value = str_replace($quot, $quot.$quot, $value);
		if (strchr($value, $sep) !== FALSE || strchr($value, $quot) !== FALSE || strchr($value, "\n") !== FALSE) {
			$value = $quot . $value . $quot;
		}
		return $value;
	}

	function csv_write_row($term, $level, $max_level, $sep = ',') {
		$ret = '';
		for($i = 0; $i < $level; $i++) { $ret .= $sep; } // Prefix padding
		$ret .= $this->csv_format_cell($term->term, $sep);
		for($i = 0; $i < $max_level - $level; $i++) { $ret .= $sep; } // Suffix padding

		$related_terms_str = '';
		$related_terms = $this->get_related_terms($term->term);
		$c = count($related_terms);
		foreach($related_terms as $idx => $related_term) {
			if($idx > 0) { $related_terms_str .= ' '; }
			$related_terms_str .= $related_term->term;
			if($idx < $c - 1) { $related_terms_str .= $sep; }
		}
		$related_terms_str = str_replace('"', '""', $related_terms_str);

		$syonyms_terms_str = '';
		$synonyms = $this->get_synonyms($term->id);
		$c = count($synonyms);
		foreach($synonyms as $idx => $synonym) {
			if($idx > 0) { $syonyms_terms_str .= ' '; }
			$syonyms_terms_str .= $synonym->synonym;
			if($idx < $c - 1) { $syonyms_terms_str .= $sep; }
		}
		$syonyms_terms_str = str_replace('"', '""', $syonyms_terms_str);

		$ret .= $term->tag . $sep . '"' . $syonyms_terms_str . '"' . $sep . '"' . $related_terms_str . '"' . $sep . "\n"; // TODO
		return $ret;
	}


	function get_related_terms($id) {
		global $wpdb;
		$sql = $wpdb->prepare("
			SELECT * FROM voc_concept WHERE id IN (
				SELECT a.target_term FROM voc_relation a
					INNER JOIN voc_relation_type b ON a.relation = b.id
					WHERE id_source = 9 AND a.id_concept = %s AND b.identification = 'related')
		", $id);
		return $wpdb->get_results($sql);
	}


	function get_narrower_recursive($parent, $level, $callback) {
		global $wpdb;
		$sql = "SELECT * FROM voc_concept WHERE id IN
				(
					SELECT b.target_term
						FROM voc_concept a
						INNER JOIN voc_relation b on b.id_concept = a.id
						INNER JOIN voc_relation_type c on c.id = b.relation
						WHERE id_source = 9 AND a.id = {$parent->id} AND c.identification = 'narrower'
				);";
		$children = $wpdb->get_results($sql);
		foreach($children as $child) {
			$callback($child, $level + 1);
			$this->get_narrower_recursive($child, $level + 1, $callback);
		}
	}


	/**
	 * Retrieve the terms narrower to this term.
	 * @param $termId ID of the term
	 * @return List with IDs of the narrower terms
	 */
	function get_narrower_terms($termId) {
		global $wpdb;
		$sql = $wpdb->prepare("
			SELECT * FROM voc_concept WHERE id IN (
				SELECT a.target_term FROM voc_relation a
					INNER JOIN voc_relation_type b ON a.relation = b.id
					WHERE id_source = 9 AND a.id_concept = $termId AND b.identification = 'narrower')
		");
		return $wpdb->get_results($sql);
	}


	/**
	 * Retrieve the count of narrower terms of a term.
	 * @param $termId ID of the term
	 * @return int
	 */
	function has_narrower_terms($termId) {
		global $wpdb;
		$sql = $wpdb->prepare("
			SELECT COUNT(*) FROM voc_concept WHERE id IN (
				SELECT a.target_term FROM voc_relation a
					INNER JOIN voc_relation_type b ON a.relation = b.id
					WHERE id_source = 9 AND a.id_concept = $termId AND b.identification = 'narrower')
		");
		return $wpdb->get_var($sql);
	}


	/**
	 * Retrieve the terms broader to this term.
	 * @param $term ID of the term
	 * @return List with IDs of the broader terms
	 */
	function get_broader_terms($term) {
		global $wpdb;
		$sql = $wpdb->prepare("SELECT a.target_term FROM voc_relation a INNER JOIN voc_relation_type b ON a.relation = b.id WHERE a.id_concept = ${term} AND b.identification = 'broader'");
		return $wpdb->get_col($sql);
	}


	/**
	 * Access voc_source
	 * @return Rows from the table
	 */
	function get_voc_source() {
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM voc_source ORDER BY name");
	}


	function get_term($id) {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare('SELECT * FROM voc_concept WHERE id = %d', intval($id)));
	}


	/**
	 * Access voc_concept
	 * @param $filter - if numeric (or numeric string)
	 * @return Rows from the table
	 */
	function get_voc_concept($exclude = NULL, $id_source = 9) {
		global $wpdb;
		$id_source = intval($id_source);
		$sql = "SELECT * FROM voc_concept WHERE id_source = $id_source ";
		if(!empty($exclude) && is_numeric($exclude)) {
			$sql .= ' AND id <> ' . intval($exclude);
		}
		$sql .= ' ORDER BY term';
		// var_dump($sql);
		return $wpdb->get_results($sql);
	}


	/**
	 * Access informea vocabulary - discarding terms from other sources
	 * @param $exclude - if numeric (or numeric string)
	 * @return Rows from the table
	 */
	function get_voc_concept_informea($exclude = NULL) {
		return $this->get_voc_concept($exclude);
	}


	function suggest_vocabulary_terms($filter = NULL) {
		global $wpdb;
		if(!empty($filter)) {
			$sql = "SELECT * FROM (
						SELECT id, term as term FROM voc_concept WHERE id_source = 9 AND term like '%$filter%'
							UNION
						SELECT id_concept as id, synonym as term FROM voc_synonym WHERE synonym like '%$filter%'
					) a ORDER BY term";
		} else {
			$sql = "SELECT * FROM (
						SELECT id, term as term FROM voc_concept WHERE id_source = 9
							UNION
						SELECT id_concept as id, synonym as term FROM voc_synonym
					) a ORDER BY term";
		}
		return $wpdb->get_results($sql);
	}


	/**
	 * Retrieve the usage of terms in treaties, articles.
	 * @return  Array with term -> total_treaty | total_decision
	 */
	function get_terms_usage_statistics() {
		global $wpdb;
		$ret = array();

		$sql = "SELECT * FROM voc_concept WHERE id_source = 9 ORDER BY term";
		$rows = $wpdb->get_results($sql);
		foreach($rows as $row) {
			$ob = new StdClass;
			$ob->id = $row->id;
			$ob->term = $row->term;
			$ob->description = $row->description;
			$ob->count_treaty = 0;
			$ob->count_decision = 0;
			$ob->treaties = array();
			$ob->treaty_decisions = array();
			$ret[$row->id] = $ob;
		}

		// ai_treaty - count tags for treaty
		$sql = "SELECT a.id, a.term, a.description, c.id as id_treaty, c.short_title, c.logo_medium
					FROM voc_concept a
					INNER JOIN ai_treaty_vocabulary b on a.id = b.id_concept
					INNER JOIN ai_treaty c on b.id_treaty = c.id";
		$rows = $wpdb->get_results($sql);
		foreach($rows as $row) {
			$ob = $ret[$row->id];
			$ob->count_treaty += 1;
			if(!isset($ob->treaties[$row->id_treaty])) {
				$treaty = new StdClass();
				$treaty->id = $row->id_treaty;
				$treaty->short_title = $row->short_title;
				$treaty->logo_medium = $row->logo_medium;
				$treaty->tagged = TRUE;
				$treaty->articles = array();
				$ob->treaties[$row->id_treaty] = $treaty;
			}
		}

		// ai_treaty_article - count tags for treaty articles
		$sql = "SELECT a.id, a.term, a.description, c.id as id_article, c.official_order, c.title as article_title, d.id as id_treaty, d.short_title, d.logo_medium
					FROM voc_concept a
					INNER JOIN ai_treaty_article_vocabulary b on a.id = b.id_concept
					INNER JOIN ai_treaty_article c on b.id_treaty_article = c.id
					INNER JOIN ai_treaty d on c.id_treaty = d.id";
		$rows = $wpdb->get_results($sql);
		foreach($rows as $row) {
			$ob = $ret[$row->id];
			$ob->count_treaty += 1;

			$treaty = NULL;
			if(!isset($ob->treaties[$row->id_treaty])) {
				$treaty = new StdClass();
				$treaty->id = $row->id_treaty;
				$treaty->short_title = $row->short_title;
				$treaty->logo_medium = $row->logo_medium;
				$treaty->articles = array();
				$treaty->tagged = FALSE;
				$ob->treaties[$row->id_treaty] = $treaty;
			}
			$treaty = $ob->treaties[$row->id_treaty];

			$article = new StdClass();
			$article->id = $row->id_article;
			$article->title = $row->article_title;
			$article->official_order = $row->official_order;
			$article->tagged = TRUE;
			$article->paragraphs = array();
			$treaty->articles[] = $article;
		}
		// ai_treaty_article_paragraph - count tags for paragraphs from treaty articles
		$sql = "SELECT a.id, a.term, c.`order` as paragraph_order, c.id as id_paragraph, d.id as id_article, d.official_order, d.title as article_title, e.id as id_treaty, e.short_title, e.logo_medium
					FROM voc_concept a
					INNER JOIN ai_treaty_article_paragraph_vocabulary b on a.id = b.id_concept
					INNER JOIN ai_treaty_article_paragraph c on b.id_treaty_article_paragraph = c.id
					INNER JOIN ai_treaty_article d on c.id_treaty_article = d.id
					INNER JOIN ai_treaty e on d.id_treaty = e.id
					ORDER BY c.`order`";
		$rows = $wpdb->get_results($sql);
		foreach($rows as $row) {
			$ob = $ret[$row->id];
			$ob->count_treaty += 1;
			if(!isset($ob->treaties[$row->id_treaty])) {
				$treaty = new StdClass();
				$treaty->id = $row->id_treaty;
				$treaty->short_title = $row->short_title;
				$treaty->logo_medium = $row->logo_medium;
				$treaty->articles = array();
				$treaty->tagged = FALSE;
				$ob->treaties[$row->id_treaty] = $treaty;
			}
			$treaty = $ob->treaties[$row->id_treaty];

			if(!isset($treaty->articles[$row->id_article])) {
				$article = new StdClass();
				$article->id = $row->id_article;
				$article->title = $row->article_title;
				$article->official_order = $row->official_order;
				$article->paragraphs = array();
				$article->tagged = FALSE;
				$treaty->articles[$row->id_article] = $article;
			}
			$article = $treaty->articles[$row->id_article];

			// Put paragraphs into articles
			$para = new StdClass();
			$para->id = $row->id_paragraph;
			$para->order = $row->paragraph_order;
			$article->paragraphs[] = $para;
		}

		// tagged decisions
		$sql = "SELECT a.id, a.term, a.description, c.id as id_decision, c.short_title as decision_short_title, c.long_title as decision_long_title, c.number,
					d.id as id_treaty, d.short_title as treaty_title, d.logo_medium
					FROM voc_concept a
					INNER JOIN ai_decision_vocabulary b on a.id = b.id_concept
					INNER JOIN ai_decision c on b.id_decision = c.id
					INNER JOIN ai_treaty d on c.id_treaty = d.id";
		$rows = $wpdb->get_results($sql);
		foreach($rows as $row) {
			$ob = $ret[$row->id];
			if(!isset($ob->treaty_decisions[$row->id_treaty])) {
				$treaty = new StdClass();
				$treaty->id = $row->id_treaty;
				$treaty->short_title = $row->treaty_title;
				$treaty->logo_medium = $row->logo_medium;
				$treaty->decisions = array();
				$ob->treaty_decisions[$row->id_treaty] = $treaty;
			}
			$treaty = $ob->treaty_decisions[$row->id_treaty];
			$decision = new StdClass();
			$decision->id = $row->id_decision;
			$decision->short_title = $row->decision_short_title;
			$decision->long_title = $row->decision_long_title;
			$decision->number = $row->number;
			$treaty->decisions[$decision->id] = $decision;
			$ob->count_decision += 1;
		}

		// tagged decisions paragraphs - TODO not tested (no data)
		$sql = "SELECT a.id, a.term, c.id as id_paragraph, c.`order`, a.description,
						d.id as id_decision, d.short_title as decision_short_title, d.long_title as decision_long_title, d.number,
						e.id as id_treaty, e.short_title as treaty_title, e.logo_medium
					FROM voc_concept a
					INNER JOIN ai_decision_paragraph_vocabulary b on a.id = b.id_concept
					INNER JOIN ai_decision_paragraph c on b.id_decision_paragraph = c.id
					INNER JOIN ai_decision d on c.id_decision = c.id
					INNER JOIN ai_treaty e on d.id_treaty = d.id";
		$rows = $wpdb->get_results($sql);
		foreach($rows as $row) {
			$ob = $ret[$row->id];
			if(!isset($ob->treaty_decisions[$row->id_treaty])) {
				$treaty = new StdClass();
				$treaty->id = $row->id_treaty;
				$treaty->short_title = $row->treaty_title;
				$treaty->logo_medium = $row->logo_medium;
				$treaty->decisions = array();
				$ob->treaty_decisions[$row->id_treaty] = $treaty;
			}
			$treaty = $ob->treaty_decisions[$row->id_treaty];
			$decision = new StdClass();
			$decision->id = $row->id_decision;
			$decision->short_title = $row->decision_short_title;
			$decision->long_title = $row->decision_long_title;
			$decision->number = $row->number;
			$treaty->decisions[$decision->id] = $decision;
			$ob->count_decision += 1;
		}
		return $ret;
	}


	/**
	 * Validate the voc_relationships form
	 * @return TRUE If form successfully validated
	 */
	function validate_voc_edit_term() {
		$this->actioned = TRUE;
		$val = new FormValidator();
		$val->addValidation("term", "req", "Term cannot be empty");
		$val->addValidation("id_term", "req", "Please select the term from drop-down list");
		$val->addValidation("id_term", "num", "Please select the term from drop-down list");
		$valid = $val->ValidateForm();
		if(!$valid) {
			$this->errors = $val->GetErrors();
		}
		return $valid;
	}


	/**
	 * Update the term (details and its relationships)
	 * @return TRUE if successfully added
	 */
	function voc_edit_term($update_hierarchy = true, $update_related = false) {
		global $wpdb;
		global $current_user;
		$user = $current_user->user_login;
		$this->actioned = TRUE;

		@mysql_query("BEGIN", $wpdb->dbh);
		try {
			$id_term = get_request_int('id_term');

			if($update_hierarchy) {
				$id_broader = $wpdb->get_var("SELECT id FROM voc_relation_type WHERE identification = 'broader'");
				$id_narrower = $wpdb->get_var("SELECT id FROM voc_relation_type WHERE identification = 'narrower'");

				// Remove previous relationships
				$wpdb->query("DELETE FROM voc_relation WHERE id_concept = $id_term AND relation = $id_broader");

				// Save broader terms
				$broader = get_request_value('broader', array(), false);
				if(!empty($broader)) {
					foreach($broader as $term) {
						$wpdb->query(
							$wpdb->prepare(
								"INSERT INTO voc_relation (id_concept, target_term, relation, rec_created, rec_author, rec_updated, rec_updated_author) VALUES ( %d, %d, %d, %s, %s, %s, %s )",
								array($id_term, $term, $id_broader, date('Y-m-d H:i:s', strtotime("now")), $user, date('Y-m-d H:i:s', strtotime("now")), $user)
							)
						);
						// Broader terms automatically have this term as narrower.
						$wpdb->query(
							$wpdb->prepare(
								"REPLACE INTO voc_relation (id_concept, target_term, relation, rec_created, rec_author, rec_updated, rec_updated_author) VALUES ( %d, %d, %d, %s, %s, %s, %s )",
								array($term, $id_term, $id_narrower, date('Y-m-d H:i:s', strtotime("now")), $user, date('Y-m-d H:i:s', strtotime("now")), $user)
							)
						);
					}
				}
			}
			// Related terms
			if($update_related) {
				$id_related = $wpdb->get_var("SELECT id FROM voc_relation_type WHERE identification = 'related'");
				// Remove previous relationships
				$wpdb->query("DELETE FROM voc_relation WHERE id_concept = $id_term AND relation = $id_related");

				$related = get_request_value('related', array(), false);
				foreach($related as $term) {
					$wpdb->query(
						$wpdb->prepare(
							"INSERT INTO voc_relation (id_concept, target_term, relation, rec_created, rec_author, rec_updated, rec_updated_author) VALUES ( %d, %d, %d, %s, %s, %s, %s )",
							array($id_term, $term, $id_related, date('Y-m-d H:i:s', strtotime("now")), $user, date('Y-m-d H:i:s', strtotime("now")), $user)
						)
					);
				}
			}

			// Update the term details
			$top_concept = get_request_boolean('top_concept');
			$term = stripslashes(get_request_value('term'));
			$this->success = $wpdb->update('voc_concept', array(
					'term' => $term,
					'description' => stripslashes(get_request_value('description')),
					'reference_url' => stripslashes(get_request_value('reference_url')),
					'tag' => stripslashes(get_request_value('tag')),
					'geg_tools_url' => get_request_value('geg_tools_url'),
					'id_source' => stripslashes(get_request_int('id_source')),
					'top_concept' => $top_concept,
					'rec_updated' => date('Y-m-d H:i:s', strtotime("now")),
					'rec_updated_author' => $user
				),
				array('id' => $id_term)
			);

			// Update term synonyms
			// Remove previous relationships
			$wpdb->query("DELETE FROM voc_synonym WHERE id_concept = $id_term");
			$synonyms = get_request_value('synonyms', array(), false);
			foreach($synonyms as $synonym) {
				$success = $wpdb->insert('voc_synonym',
					array(
						'id_concept' => $id_term,
						'synonym' => $synonym,
						'rec_author' => $user
					)
				);
			}

			// Update the relation with other vocabularies
			//// GEG Tools
			$identif_geg = $wpdb->get_var("SELECT id FROM voc_relation_type WHERE identification = 'geg_synonym'");
			$geg = get_request_value('geg');
			$wpdb->query("DELETE FROM voc_relation WHERE id_concept = $id_term AND relation = $identif_geg");
			if(!empty($geg)) {
				$success = $wpdb->insert(
					'voc_relation',
					array('id_concept' => $id_term, 'target_term' => intval($geg),
						'relation' => $identif_geg, 'rec_author' => $user,
						'rec_updated' => date('Y-m-d H:i:s', strtotime("now"))
					)
				);
			}
			//// GEMET
			$identif_gemet = $wpdb->get_var("SELECT id FROM voc_relation_type WHERE identification = 'gemet_synonym'");
			$gemet = get_request_value('gemet');
			$wpdb->query("DELETE FROM voc_relation WHERE id_concept = $id_term AND relation = $identif_gemet");
			if(!empty($gemet)) {
				$success = $wpdb->insert(
					'voc_relation',
					array('id_concept' => $id_term, 'target_term' => intval($gemet),
						'relation' => $identif_gemet, 'rec_author' => $user,
						'rec_updated' => date('Y-m-d H:i:s', strtotime("now"))
					)
				);
			}
			//// Ecolex
			$identif_ecolex = $wpdb->get_var("SELECT id FROM voc_relation_type WHERE identification = 'ecolex_synonym'");
			$ecolex = get_request_value('ecolex');
			$wpdb->query("DELETE FROM voc_relation WHERE id_concept = $id_term AND relation = $identif_ecolex");
			if(!empty($ecolex)) {
				$success = $wpdb->insert(
					'voc_relation',
					array('id_concept' => $id_term, 'target_term' => intval($ecolex),
						'relation' => $identif_ecolex, 'rec_author' => $user,
						'rec_updated' => date('Y-m-d H:i:s', strtotime("now"))
					)
				);
			}

			// Log the action
			// TODO: Could add more details about the term, what was updated etc.
			$url = 	sprintf('%s/terms/%d', get_bloginfo('url'), $id_term);
			$this->add_activity_log('update', 'vocabulary', "Updated term details for '{$term}'", null, $url);

			@mysql_query("COMMIT", $wpdb->dbh);
			return TRUE;
		} catch (Exception $e) {
			$this->success = FALSE;
			@mysql_query("ROLLBACK", $wpdb->dbh);
			return FALSE;
		}
	}


	/**
	 * Validate the voc_clone_term form
	 * @return TRUE If form successfully validated
	 */
	function validate_voc_clone_term() {
		global $wpdb;
		$this->actioned = TRUE;
		$val = new FormValidator();
		$val->addValidation("term", "req", "Cloned term cannot be empty");
		$val->addValidation("id_cloned", "req", "Please select the term to be cloned from the drop-down list");
		$valid = $val->ValidateForm();
		if(!$valid) {
			$this->errors = $val->GetErrors();
		}
		// Check for duplicate term
		$term = stripslashes(get_request_value('term'));
		$ret = $wpdb->get_var("SELECT id FROM voc_concept WHERE term = '$term'");
		if(!empty($ret)) {
			$valid = FALSE;
			$this->errors['duplicate'] = "This term has already been defined";
		}
		return $valid;
	}

	function voc_clone_term() {
		global $wpdb;
		global $current_user;
		@mysql_query("BEGIN", $wpdb->dbh);
		$user = $current_user->user_login;
		$ret = NULL;

		$id_cloned = get_request_int('id_cloned');
		$cloned = $this->get_term($id_cloned);

		$term = stripslashes(trim($_POST['term']));
		$rec_created = date('Y-m-d H:i:s', strtotime("now"));
		$description = get_request_value('description');
		if(empty($description)) {
			$description = $cloned->description;
		}
		$description .= " (Cloned from {$cloned->term})";

		$this->success = $wpdb->insert('voc_concept', array(
				'term' => $term,
				'description' => $description,
				'reference_url' => $cloned->reference_url,
				'tag' => $cloned->tag,
				'id_source' => $cloned->id_source,
				'top_concept' => $cloned->top_concept,
				'order' => $cloned->order,
				'substantive' => $cloned->substantive,
				'rec_author' => $user,
				'rec_created' => $rec_created,
			)
		);
		if($this->success) {
			$ret = $new_id = $wpdb->insert_id;

			// clone ai_decision_paragraph_vocabulary
			$ids = $wpdb->get_col("SELECT id_decision_paragraph FROM ai_decision_paragraph_vocabulary WHERE id_concept = $id_cloned");
			foreach($ids as $id) {
				$wpdb->insert('ai_decision_paragraph_vocabulary', array(
						'id_decision_paragraph' => $id,
						'id_concept' => $new_id,
						'rec_author' => $user,
						'rec_created' => $rec_created
					)
				);
			}

			// clone ai_decision_vocabulary
			$ids = $wpdb->get_col("SELECT id_decision FROM ai_decision_vocabulary WHERE id_concept = $id_cloned");
			foreach($ids as $id) {
				$wpdb->insert('ai_decision_vocabulary', array(
						'id_decision' => $id,
						'id_concept' => $new_id,
						'rec_author' => $user,
						'rec_created' => $rec_created
					)
				);
			}

			// clone ai_treaty_article_paragraph_vocabulary
			$ids = $wpdb->get_col("SELECT id_treaty_article_paragraph FROM ai_treaty_article_paragraph_vocabulary WHERE id_concept = $id_cloned");
			foreach($ids as $id) {
				$wpdb->insert('ai_treaty_article_paragraph_vocabulary', array(
						'id_treaty_article_paragraph' => $id,
						'id_concept' => $new_id,
						'rec_author' => $user,
						'rec_created' => $rec_created
					)
				);
			}

			// clone ai_treaty_article_vocabulary
			$ids = $wpdb->get_col("SELECT id_treaty_article FROM ai_treaty_article_vocabulary WHERE id_concept = $id_cloned");
			foreach($ids as $id) {
				$wpdb->insert('ai_treaty_article_vocabulary', array(
						'id_treaty_article' => $id,
						'id_concept' => $new_id,
						'rec_author' => $user,
						'rec_created' => $rec_created
					)
				);
			}

			// clone ai_treaty_vocabulary
			$ids = $wpdb->get_col("SELECT id_treaty FROM ai_treaty_vocabulary WHERE id_concept = $id_cloned");
			foreach($ids as $id) {
				$wpdb->insert('ai_treaty_vocabulary', array(
						'id_treaty' => $id,
						'id_concept' => $new_id,
						'rec_author' => $user,
						'rec_created' => $rec_created
					)
				);
			}
			// clone voc_relation
			$relations = $wpdb->get_results("SELECT * FROM voc_relation WHERE id_concept = $id_cloned");
			foreach($relations as $relation) {
				$wpdb->insert('voc_relation', array(
						'id_concept' => $new_id,
						'target_term' => $relation->target_term,
						'relation' => $relation->relation,
						'rec_author' => $user,
						'rec_created' => $rec_created
					)
				);
			}
			$relations = $wpdb->get_results("SELECT * FROM voc_relation WHERE target_term = $id_cloned");
			foreach($relations as $relation) {
				$wpdb->insert('voc_relation', array(
						'id_concept' => $relation->id_concept,
						'target_term' => $new_id,
						'relation' => $relation->relation,
						'rec_author' => $user,
						'rec_created' => $rec_created
					)
				);
			}

			// clone voc_synonym
			$synonyms = $wpdb->get_col("SELECT synonym FROM voc_synonym WHERE id_concept = $id_cloned");
			foreach($synonyms as $synonym) {
				$wpdb->insert('voc_synonym', array(
						'id_concept' => $new_id,
						'synonym' => $synonym
					)
				);
			}

			@mysql_query("COMMIT", $wpdb->dbh);
		} else {
			@mysql_query("ROLLBACK", $wpdb->dbh);
		}
		return $ret;
	}

	/**
	 * Update an term hierarchy with its related terms
	 */
	function update_term_hierarchy($child, $oldParent, $newParent, $preserveOldRelations = FALSE) {
		global $wpdb;
		global $current_user;

		@mysql_query("BEGIN", $wpdb->dbh);

		try {
			if(!$preserveOldRelations) {
				// Remove relation $child - $oldParent
				$wpdb->query("DELETE FROM voc_relation WHERE id_concept = $child AND target_term = $oldParent AND relation = 1"); //broader TODO: CHECK
				// Remove relation $oldParent - $child
				$wpdb->query("DELETE FROM voc_relation WHERE id_concept = $oldParent AND target_term = $child AND relation = 2"); //narrower TODO: CHECK
			}

			// Inser new relation $child - $newParent
			$success = $wpdb->insert('voc_relation',
				array(
					'id_concept' => $child,
					'target_term' => $newParent,
					'relation' => 1,
					'rec_created' => date('Y-m-d H:i:s', strtotime("now")),
					'rec_author' => $current_user->user_login,
				)
			);

			if(!$success) {
				throw new Exception("ERROR: Updating term");
			}

			$success = $wpdb->insert('voc_relation',
				array(
					'id_concept' => $newParent,
					'target_term' => $child,
					'relation' => 2,
					'rec_created' => date('Y-m-d H:i:s', strtotime("now")),
					'rec_author' => $current_user->user_login,
				)
			);

			if(!$success) {
				throw new Exception("ERROR: Updating term");
			}

			// Log the operation in the main log
			$ob_child = $this->get_term($child);
			$ob_oldParent = $this->get_term($oldParent);
			$ob_newParent = $this->get_term($newParent);
			$url = sprintf('%s/terms/%d', get_bloginfo('url'), $child);
			if(!$preserveOldRelations) {
				$this->add_activity_log('update', 'vocabulary', "Updated term hierarchy: '{$ob_child->term}' is now narrower to '{$ob_newParent->term}'. Was previously narrower to '{$ob_oldParent->term}'", null, $url);
			} else {
				$this->add_activity_log('update', 'vocabulary', "Updated term hierarchy: '{$ob_child->term}' is now also narrower to '{$ob_newParent->term}'.", null, $url);
			}
		} catch(Exception $ex) {
			@mysql_query("ROLLBACK", $wpdb->dbh);
			throw $ex;
		}
		@mysql_query("COMMIT", $wpdb->dbh);
	}


	/**
	 * Unmark term as being narrower to other term
	 */
	function unlink_term($child, $parent) {
		global $wpdb;
		global $current_user;

		@mysql_query("BEGIN", $wpdb->dbh);
		try {
			// Remove relation $child - $oldParent
			$wpdb->query("DELETE FROM voc_relation WHERE id_concept = $child AND target_term = $parent AND relation = 1"); //broader TODO: CHECK
			// Remove relation $oldParent - $child
			$wpdb->query("DELETE FROM voc_relation WHERE id_concept = $parent AND target_term = $child AND relation = 2"); //narrower TODO: CHECK

			// Log the operation in the main log
			$ob_child = $this->get_term($child);
			$parent = $this->get_term($parent);
			$url = sprintf('%s/terms/%d', get_bloginfo('url'), $child);
			$this->add_activity_log('update', 'vocabulary', "Unlinked term '{$ob_child->term}' as now narrower to '{$parent->term}'.", null, $url);
		} catch(Exception $ex) {
			@mysql_query("ROLLBACK", $wpdb->dbh);
			throw $ex;
		}
		@mysql_query("COMMIT", $wpdb->dbh);
	}


	function ajax_suggest_terms() {
		$page_data = new Thesaurus(NULL);
		$key = get_request_value('key');
		$terms = $page_data->suggest_vocabulary_terms($key);
		$arr = array();
		foreach($terms as $term) {
			$arr[] = array('id' => $term->id, 'term' => $term->term);
		}
		header('Content-Type:application/json');
		echo json_encode($arr);
		die();
	}


	/** !!!!!!!!!!!!!!!!!!!!!! ADMINISTRATION AREA SPECIFIC !!!!!!!!!!!!!!!!!!!!!! */

	/**
	 */
	function get_geg_focus_terms() {
		global $wpdb;
		return $wpdb->get_results("SELECT a.* FROM voc_concept a INNER JOIN voc_source b ON a.id_source = b.id WHERE b.name='GEG'");
	}

	function get_ecolex_terms() {
		global $wpdb;
		return $wpdb->get_results("SELECT a.* FROM voc_concept a INNER JOIN voc_source b ON a.id_source = b.id WHERE b.name='ECOLEX'");
	}

	function get_gemet_terms() {
		global $wpdb;
		return $wpdb->get_results("SELECT a.* FROM voc_concept a INNER JOIN voc_source b ON a.id_source = b.id WHERE b.name='GEMET'");
	}

	function get_term_geg_synonym($id_term) {
		global $wpdb;
		$identif = $wpdb->get_var("SELECT id FROM voc_relation_type WHERE identification = 'geg_synonym'");
		return $wpdb->get_row("SELECT * FROM voc_concept WHERE id = (SELECT a.target_term FROM voc_relation a WHERE a.id_concept = $id_term AND a.relation = $identif)");
	}

	function get_term_ecolex_synonym($id_term) {
		global $wpdb;
		$identif = $wpdb->get_var("SELECT id FROM voc_relation_type WHERE identification = 'ecolex_synonym'");
		return $wpdb->get_row("SELECT * FROM voc_concept WHERE id = (SELECT a.target_term FROM voc_relation a WHERE a.id_concept = $id_term AND a.relation = $identif)");
	}

	function get_term_gemet_synonym($id_term) {
		global $wpdb;
		$identif = $wpdb->get_var("SELECT id FROM voc_relation_type WHERE identification = 'gemet_synonym'");
		return $wpdb->get_row("SELECT * FROM voc_concept WHERE id = (SELECT a.target_term FROM voc_relation a WHERE a.id_concept = $id_term AND a.relation = $identif)");
	}



	/**
	 * Validate the voc_add_term form
	 * @return TRUE If form successfully validated
	 */
	function validate_voc_add_term() {
		global $wpdb;
		$this->actioned = TRUE;

		$val = new FormValidator();
		$val->addValidation("term", "req", "Please fill in the term");
		$val->addValidation("id_source", "req", "Please select the source");
		$val->addValidation("id_source", "num", "Source must be numeric!");
		$valid = $val->ValidateForm();
		$this->errors = $val->GetErrors();
		// Check for duplicate term
		$term = stripslashes(get_request_value('term'));
		$ret = $wpdb->get_var("SELECT id FROM voc_concept WHERE term = '$term'");
		if(!empty($ret)) {
			$valid = FALSE;
			$this->errors['Duplicate'] = "This term has already been defined";
		}
		return $valid;
	}


	/**
	 * Insert new term into the database
	 * @return TRUE if successfully added
	 */
	function voc_add_term() {
		global $wpdb;
		global $current_user;
		@mysql_query("BEGIN", $wpdb->dbh);
		$user = $current_user->user_login;
		$ret = NULL;
		$top_concept = get_request_boolean('top_concept');
		$term = stripslashes(trim($_POST['term']));

		$rec_created = date('Y-m-d H:i:s', strtotime("now"));

		$this->success = $wpdb->insert('voc_concept', array(
				'term' => $term,
				'description' => stripslashes(get_request_value('description')),
				'reference_url' => stripslashes(get_request_value('reference_url')),
				'tag' => stripslashes(get_request_value('tag')),
				'geg_tools_url' => get_request_value('geg_tools_url'),
				'id_source' => get_request_int('id_source'),
				'top_concept' => $top_concept,
				'rec_author' => $user,
				'rec_created' => $rec_created,
			)
		);
		$ret = $this->insert_id = $wpdb->insert_id;

		if($this->success) {
			$related_terms = get_request_value('related', array(), false);
			foreach($related_terms as $related_term) {
				$success = $wpdb->insert('voc_relation',
					array(
						'id_concept' => $this->insert_id,
						'target_term' => $related_term,
						'relation' => 3,
						'rec_created' => $rec_updated,
						'rec_author' => $user,
					)
				);
			}

			if(!$top_concept) { // If not theme, then mark its broader term
				$broader = get_request_int('broader');
				if(!empty($broader)) {
					$success = $wpdb->insert('voc_relation',
						array(
							'id_concept' => $this->insert_id, 'target_term' => $broader, 'relation' => 1,
							'rec_created' => $rec_created, 'rec_author' => $user,
						)
					);
					$success = $wpdb->insert('voc_relation',
						array(
							'id_concept' => $broader, 'target_term' => $this->insert_id, 'relation' => 2,
							'rec_created' => $rec_created, 'rec_author' => $user,
						)
					);
				}
			}

			// Add the synonyms
			$synonyms = get_request_value('synonyms', array(), false);
			foreach($synonyms as $synonym) {
				$success = $wpdb->insert('voc_synonym',
					array(
						'id_concept' => $this->insert_id,
						'synonym' => $synonym,
						'rec_author' => $user
					)
				);
			}

			// Log the action
			$url = 	sprintf('%s/terms/%d', get_bloginfo('url'), $this->insert_id);
			$this->add_activity_log('insert', 'vocabulary', "Created new term <strong>{$term}</strong> into vocabulary", null, $url);
			@mysql_query("COMMIT", $wpdb->dbh);
		} else {
			@mysql_query("ROLLBACK", $wpdb->dbh);
			$this->success = FALSE;
			$this->errors = array('DB' => $wpdb->last_error);
		}
		return $ret;
	}


	// Return suggestions which are not already linked to the term
	function synonym_autocomplete($id_concept = NULL, $filter = NULL) {
		global $wpdb;
		$sql = 'SELECT * FROM voc_synonym WHERE 1 = 1 ';
		if($id_concept) {
			$sql .= ' AND id_concept <> ' . $id_concept;
		}
		if($filter) {
			$sql .= " AND synonym LIKE '%$filter%'";
		}
		$sql .= ' GROUP BY synonym ORDER BY synonym';
		return $wpdb->get_results($sql);
	}


	/**
	 * Delete term from database
	 */
	function voc_delete_term() {
		if($this->security_check('informea-admin_voc_relationships')) {
			global $wpdb;
			$ret = FALSE;
			$id_term = get_request_int('id_term');
			$t = $this->get_term($id_term);
			@mysql_query("BEGIN", $wpdb->dbh);

			$wpdb->query("DELETE FROM ai_decision_paragraph_vocabulary WHERE id_concept = $id_term");
			$wpdb->query("DELETE FROM ai_decision_vocabulary WHERE id_concept = $id_term");
			$wpdb->query("DELETE FROM ai_treaty_article_paragraph_vocabulary WHERE id_concept = $id_term");
			$wpdb->query("DELETE FROM ai_treaty_article_vocabulary WHERE id_concept = $id_term");
			$wpdb->query("DELETE FROM ai_treaty_vocabulary WHERE id_concept = $id_term");
			$wpdb->query("DELETE FROM voc_relation WHERE id_concept = $id_term");
			$sql = $wpdb->prepare('DELETE FROM voc_concept WHERE id = %d', $id_term);
			$ret = $wpdb->query($sql);
			// Log the action
			$this->add_activity_log('delete', 'vocabulary', "Deleted term <strong>{$t->term}</strong> from vocabulary");

			@mysql_query("COMMIT", $wpdb->dbh);
			return $ret;
		}
	}

	function voc_save_theme_sort_group() {
		global $wpdb;
		$themes = $this->get_top_concepts();
		$this->actioned = TRUE;
		foreach($themes as $term) {
			$order = get_request_value('theme-sort-' . $term->id, 0);
			$group = get_request_value('theme-group-' . $term->id, 0);
			$wpdb->update('voc_concept', array('substantive' => $group, 'order' => $order), array('id' => $term->id));
		}
		$this->success = TRUE;
	}

	/**
	 * Validate the voc_sources form
	 * @return TRUE If form successfully validated
	 */
	function validate_voc_sources() {
		global $wpdb;
		$this->actioned = TRUE;
		if($this->security_check('informea-admin_voc_source')) {
			$val = new FormValidator();
			$val->addValidation("name", "req", "Please enter source name");
			$valid = $val->ValidateForm();
			if(!$valid) {
				$this->errors = $val->GetErrors();
			}
			// Check for duplicates
			$name = stripslashes(get_request_value('name'));
			$ret = $wpdb->get_var("SELECT id FROM voc_source WHERE name = '$name'");
			if(!empty($ret)) {
				$valid = FALSE;
				$this->errors['Duplicate'] = "A source with this name already exists";
			}
			return $valid;
		}
		return FALSE;
	}

	/**
	 * Create source (details and its relationships)
	 * @return TRUE if successfully added
	 */
	function voc_sources() {
		global $wpdb;
		global $current_user;
		$user = $current_user->user_login;
		$this->actioned = TRUE;
		$name = stripslashes(get_request_value('name'));
		$url = stripslashes(get_request_value('url'));
		$this->success = $wpdb->insert('voc_source',
			array(
				'name' => $name,
				'url' => $url,
				'rec_author' => $user,
				'rec_created' => date('Y-m-d H:i:s', strtotime("now"))
			)
		);
		if($this->success) {
			$this->insert_id = $wpdb->insert_id;
			// Log the action
			$url = 	sprintf('%s/%s', get_bloginfo('url'), 'wp-admin/admin.php?page=thesaurus&act=voc_source');
			$this->add_activity_log('insert', 'vocabulary', "Created new vocabulary source <strong>{$name}</strong>", null, $url);
		} else {
			$this->success = FALSE;
			$this->errors = array('DB' => $wpdb->last_error);
		}
	}

	/**
	 * Merge two terms into one
	 */
	function voc_merge_terms() {
		global $wpdb;
		global $current_user;
		$user = $current_user->user_login;
		$this->actioned = TRUE;
		$this->success = FALSE;

		$merged = $this->get_term(intval(get_request_value('merged')));
		$destination = $this->get_term(intval(get_request_value('destination')));

		if($merged->id == $destination->id) {
			$this->errors = array('Invalid values' => 'The terms are identical, are you really sure you know what are you doing?');
			return;
		}

		@mysql_query("BEGIN", $wpdb->dbh);
		// ai_decision_vocabulary
		$rows = $wpdb->get_results("SELECT * FROM ai_decision_vocabulary WHERE id_concept = {$merged->id}");
		foreach($rows as $row) {
			$wpdb->query(
				$wpdb->prepare(
					"REPLACE INTO ai_decision_vocabulary (id_decision, id_concept, rec_created, rec_author, rec_updated, rec_updated_author) VALUES ( %d, %d, %s, %s, %s, %s )",
					array($row->id_decision, $destination->id, date('Y-m-d H:i:s', strtotime("now")), $user, date('Y-m-d H:i:s', strtotime("now")), $user)
				)
			);
		}
		$wpdb->query("DELETE FROM ai_decision_vocabulary WHERE id_concept = {$merged->id}");

		// ai_decision_paragraph_vocabulary - TODO not tested
		$rows = $wpdb->get_results("SELECT * FROM ai_decision_paragraph_vocabulary WHERE id_concept = {$merged->id}");
		foreach($rows as $row) {
			$wpdb->query(
				$wpdb->prepare(
					"REPLACE INTO ai_decision_paragraph_vocabulary (id_decision_paragraph, id_concept, rec_created, rec_author, rec_updated, rec_updated_author) VALUES ( %d, %d, %s, %s, %s, %s )",
					array($row->id_decision_paragraph, $destination->id, date('Y-m-d H:i:s', strtotime("now")), $user, date('Y-m-d H:i:s', strtotime("now")), $user)
				)
			);
		}
		$wpdb->query("DELETE FROM ai_decision_paragraph_vocabulary WHERE id_concept = {$merged->id}");

		// ai_treaty_article_paragraph_vocabulary
		$rows = $wpdb->get_results("SELECT * FROM ai_treaty_article_paragraph_vocabulary WHERE id_concept = {$merged->id}");
		foreach($rows as $row) {
			$wpdb->query(
				$wpdb->prepare(
					"REPLACE INTO ai_treaty_article_paragraph_vocabulary (id_treaty_article_paragraph, id_concept, rec_created, rec_author, rec_updated, rec_updated_author) VALUES ( %d, %d, %s, %s, %s, %s )",
					array($row->id_treaty_article_paragraph, $destination->id, date('Y-m-d H:i:s', strtotime("now")), $user, date('Y-m-d H:i:s', strtotime("now")), $user)
				)
			);
		}
		$wpdb->query("DELETE FROM ai_treaty_article_paragraph_vocabulary WHERE id_concept = {$merged->id}");

		// ai_treaty_article_vocabulary
		$rows = $wpdb->get_results("SELECT * FROM ai_treaty_article_vocabulary WHERE id_concept = {$merged->id}");
		foreach($rows as $row) {
			$wpdb->query(
				$wpdb->prepare(
					"REPLACE INTO ai_treaty_article_vocabulary (id_treaty_article, id_concept, rec_created, rec_author, rec_updated, rec_updated_author) VALUES ( %d, %d, %s, %s, %s, %s )",
					array($row->id_treaty_article, $destination->id, date('Y-m-d H:i:s', strtotime("now")), $user, date('Y-m-d H:i:s', strtotime("now")), $user)
				)
			);
		}
		$wpdb->query("DELETE FROM ai_treaty_article_vocabulary WHERE id_concept = {$merged->id}");

		// ai_treaty_vocabulary
		$rows = $wpdb->get_results("SELECT * FROM ai_treaty_vocabulary WHERE id_concept = {$merged->id}");
		foreach($rows as $row) {
			$wpdb->query(
				$wpdb->prepare(
					"REPLACE INTO ai_treaty_vocabulary (id_treaty, id_concept, rec_created, rec_author, rec_updated, rec_updated_author) VALUES ( %d, %d, %s, %s, %s, %s )",
					array($row->id_treaty, $destination->id, date('Y-m-d H:i:s', strtotime("now")), $user, date('Y-m-d H:i:s', strtotime("now")), $user)
				)
			);
		}
		$wpdb->query("DELETE FROM ai_treaty_vocabulary WHERE id_concept = {$merged->id}");
		$wpdb->query("DELETE FROM voc_relation WHERE id_concept = {$merged->id} OR target_term = {$merged->id}");
		$wpdb->query("DELETE FROM voc_concept WHERE id = {$merged->id}");

		$this->success = TRUE;
		@mysql_query("COMMIT", $wpdb->dbh);
		$url = sprintf('%s/terms/%d', get_bloginfo('url'), $destination->id);
		$this->add_activity_log('update', 'vocabulary', "Merged term <strong>{$merged->term}</strong> into <strong>{$destination->term}</strong>", $user, $url);
	}


	/**
	 * Access ai_treaty_vocabulary
	 * @param $id_treaty ID of the treaty
	 * @return all keywords associated with a treaty
	 */
	function get_keywords_for_treaty($id_treaty) {
		global $wpdb;
		return $wpdb->get_col($wpdb->prepare('SELECT id_concept FROM ai_treaty_vocabulary WHERE id_treaty = %d;', intval($id_treaty)));
	}


}
