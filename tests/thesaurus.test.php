<?php

require_once WP_PLUGIN_DIR . '/imea_ai/imea.php';
require_once WP_PLUGIN_DIR . '/informea/imea.php';
require_once WP_PLUGIN_DIR . '/thesaurus/thesaurus.php';

class thesaurus_test extends InforMEABaseTest {


    function test_find_term() {
        $this->create_term();
        $ob = new Thesaurus();

        $term = $ob->find_term(" test \n");
        $this->assertNotNull($term);
        $this->assertEquals(1, $term->id);

        $term = $ob->find_term("\t  \n  tESt \n");
        $this->assertNotNull($term);
        $this->assertEquals(1, $term->id);
    }


    function test_create_term() {
        $voc_source = $this->create_voc_source();

        $d1 = array(
            'term' => " test\n",
            'description' => 'description',
            'reference_url' => 'reference_url',
            'tag' => 'test',
            'id_source' => $voc_source->id,
            'top_concept' => 1,
            'popularity' => 22,
            'order' => 32,
            'substantive' => 1,
            'geg_tools_url' => 'geg_tools_url'
        );
        $ob = new Thesaurus();
        $o1 = $ob->create_term($d1);

        $this->assertNotNull($o1);
        $this->assertEquals(1, $o1->id);

        $this->assertEquals('test', $o1->term);
        $this->assertEquals('description', $o1->description);
        $this->assertEquals('reference_url', $o1->reference_url);
        $this->assertEquals('test', $o1->tag);
        $this->assertEquals(1, $o1->id_source);
        $this->assertEquals(1, $o1->top_concept);
        $this->assertEquals(22, $o1->popularity);
        $this->assertEquals(32, $o1->order);
        $this->assertEquals(1, $o1->substantive);
        $this->assertEquals('geg_tools_url', $o1->geg_tools_url);
    }


    /**
     * Test should fail since we are passing invalid term
     *
     * @expectedException InforMEAException
     */
    function test_create_term_invalid_term() {
        $ob = new Thesaurus();
        $ob->create_term(array());
    }

    /**
     * Test should fail since we are passing invalid term
     *
     * @expectedException InforMEAException
     */
    function test_create_term_invalid_id_source() {
        $ob = new Thesaurus();
        $ob->create_term(array('term' => 'test'));
    }


    function test_get_source_by_name() {
        $this->create_voc_source();

        $ob = new Thesaurus();
        $src = $ob->get_source_by_name('tEsT');
        $this->assertNotNull($src);
        $this->assertEquals(1, $src->id);
        $this->assertEquals('TEST', $src->name);
    }
}