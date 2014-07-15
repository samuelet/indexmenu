<?php

require_once DOKU_INC.'inc/parser/xhtml.php';

/**
 * @group plugin_indexmenu
 */
class indexmenu_syntax_indexmenu_test extends DokuWikiTest {

    private $exampleIndex;

    public function setup() {
        global $conf;
        $this->pluginsEnabled[] = 'indexmenu';
        parent::setup();

        //$conf['plugin']['indexmenu']['headpage'] = '';
        //$conf['plugin']['indexmenu']['hide_headpage'] = false;

        //saveWikiText('titleonly:sub:test', "====== Title ====== \n content", 'created');
        //saveWikiText('test', "====== Title ====== \n content", 'created');
        //idx_addPage('titleonly:sub:test');
        //idx_addPage('test');
    }

    function __construct() {
        $this->exampleIndex = "{{indexmenu>:}}";
    }

    /**
     * Create from list of values the output array of handle()
     *
     * @param array $values
     * @return array aligned similar to output of handle()
     */
    function createData($values) {

        list($ns, $theme, $identifier, $nocookie, $navbar, $noscroll, $maxjs, $notoc, $jsajax, $context, $nomenu,
            $sort, $msort, $rsort, $nsort, $level, $nons, $nopg, $nss, $max, $js, $skipns, $skipfile, $hsort,
            $headpage, $hide_headpage) = $values;

        return array(
            $ns,
            Array(
                'theme'      => $theme,
                'identifier' => $identifier,
                'nocookie'   => $nocookie,
                'navbar'     => $navbar,
                'noscroll'   => $noscroll,
                'maxjs'      => $maxjs,
                'notoc'      => $notoc,
                'jsajax'     => $jsajax,
                'context'    => $context,
                'nomenu'     => $nomenu,
            ),
            $sort,
            $msort,
            $rsort,
            $nsort,
            array(
                'level'         => $level,
                'nons'          => $nons,
                'nopg'          => $nopg,
                'nss'           => $nss,
                'max'           => $max,
                'js'            => $js,
                'skip_index'    => $skipns,
                'skip_file'     => $skipfile,
                'headpage'      => $headpage,
                'hide_headpage' => $hide_headpage
            ),
            $hsort
        );
    }

    /**
     * Parse the syntax to options
     * expect: different combinations with or without js option, covers recognizing all syntax options
     */
    function testHandle() {
        global $conf;

        $plugin = new syntax_plugin_indexmenu_indexmenu();

        $null   = new Doku_Handler();
        $result = $plugin->handle($this->exampleIndex, 0, 40, $null);

        $idcalculatedfromns = sprintf("%u", crc32(''));
        $tests              = array(
            //root ns (empty is not recognized..)
            array(
                'syntax'=> "{{indexmenu>:}}",
                'data'  => array(
                    '', 'default', 'random', false, false, false, 0, false, '', false, false,
                    0, false, false, false, -1, false, false, array(), 0, false, array(''), array(''), false,
                    ":start:,:same:,:inside:", 1
                )
            ),
            //root ns, #levels=1, js renderer
            array(
                'syntax'=> "{{indexmenu>#1|js}}",
                'data'  => array(
                    '', 'default', 'random', false, false, false, 0, false, '', false, false,
                    0, false, false, false, 1, false, false, array(), 0, true, array(''), array(''), false,
                    ":start:,:same:,:inside:", 1
                )
            ),
            //root ns, #levels=2, all not js specific options  (nocookie is from context)
            array(
                'syntax'=> "{{indexmenu>#2 test#6|navbar context tsort dsort msort hsort rsort nsort nons nopg}}",
                'data'  => array(
                    '', 'default', 'random', true, true, false, 0, false, '&sort=t&msort=indexmenu_n&rsort=1&nsort=1&hsort=1&nopg=1', true, false,
                    't', 'indexmenu_n', true, true, 2, true, true, array(array('test', 6)), 0, false, array(''), array(''), true,
                    ":start:,:same:,:inside:", 1
                )
            ),
            //root ns, #levels=2, js renderer, all not js specific options
            array(
                'syntax'=> "{{indexmenu>#2 test#6|navbar js#bj_ubuntu.png context tsort dsort msort hsort rsort nsort nons nopg}}",
                'data'  => array(
                    '', 'bj_ubuntu.png', 'random', true, true, false, 0, false, '&sort=t&msort=indexmenu_n&rsort=1&nsort=1&hsort=1&nopg=1', true, false,
                    't', 'indexmenu_n', true, true, 2, true, true, array(array('test', 6)), 0, true, array(''), array(''), true,
                    ":start:,:same:,:inside:", 1
                ),
            ),
            //root ns, #levels=1, all options
            array(
                'syntax'=> "{{indexmenu>#1|navbar context nocookie noscroll notoc nomenu dsort msort#date:modified hsort rsort nsort nons nopg max#2#4 maxjs#3 id#54321}}",
                'data'  => array(
                    '', 'default', 'random', true, true, true, 0, true, '&sort=d&msort=date modified&rsort=1&nsort=1&hsort=1&nopg=1', true, true,
                    'd', 'date modified', true, true, 1, true, true, array(), 0, false, array(''), array(''), true,
                    ":start:,:same:,:inside:", 1
                )
            ),
            //root ns, #levels=1, js renderer, all options
            array(
                'syntax'=> "{{indexmenu>#1|js#bj_ubuntu.png navbar context nocookie noscroll notoc nomenu dsort msort#date:modified hsort rsort nsort nons nopg max#2#4 maxjs#3 id#54321}}",
                'data'  => array(
                    '', 'bj_ubuntu.png', 54321, true, true, true, 3, true, '&sort=d&msort=date modified&rsort=1&nsort=1&hsort=1&nopg=1&max=4', true, true,
                    'd', 'date modified', true, true, 1, true, true, array(), 2, true, array(''), array(''), true,
                    ":start:,:same:,:inside:", 1
                )
            ),
            //root ns, #levels=1, skipfile and ns

            array(
                'syntax'=> "{{indexmenu>#1 test|skipfile+/(^myusers:spaces$|privatens:userss)/ skipns=/(^myusers:spaces$|privatens:users)/ id#ns}}",
                'data'  => array(
                    '', 'default', 'random', false, false, false, 0, false, '&skipns=%3D/%28%5Emyusers%3Aspaces%24%7Cprivatens%3Ausers%29/&skipfile=%2B/%28%5Emyusers%3Aspaces%24%7Cprivatens%3Auserss%29/', false, false,
                    0, false, false, false, 1, false, false, array(array('test', -1)), 0, false, array('/(^myusers:spaces$|privatens:users)/'), array('', '/(^myusers:spaces$|privatens:userss)/'), false,
                    ":start:,:same:,:inside:", 1
                )
            ),
            //root ns, #levels=1, js renderer, skipfile and ns
            array(
                'syntax'=> "{{indexmenu>#1 test|js skipfile=/(^myusers:spaces$|privatens:userss)/ skipns+/(^myusers:spaces$|privatens:userssss)/ id#ns}}",
                'data'  => array(
                    '', 'default', 0, false, false, false, 0, false, '&skipns=%2B/%28%5Emyusers%3Aspaces%24%7Cprivatens%3Auserssss%29/&skipfile=%3D/%28%5Emyusers%3Aspaces%24%7Cprivatens%3Auserss%29/', false, false,
                    0, false, false, false, 1, false, false, array(array('test', -1)), 0, true, array('', '/(^myusers:spaces$|privatens:userssss)/'), array('/(^myusers:spaces$|privatens:userss)/'), false,
                    ":start:,:same:,:inside:", 1
                )
            )
        );

        foreach($tests as $test) {
            $null   = new Doku_Handler();
            $result = $plugin->handle($test['syntax'], 0, 40, $null);

            //copy unique generated number, which is about 23 characters
            $len_id = strlen($result[1]['identifier']);
            if(!is_numeric($test['data'][2]) && ($len_id > 20||$len_id<=23)) {
                $test['data'][2] = $result[1]['identifier'];
            }
            $data = $this->createData($test['data']);

            $this->assertEquals($data, $result, 'Data array corrupted');
        }
    }

    /**
     * Rendering for nonexisting namespace
     * expect: no paragraph due to no message set
     * expect: one paragraph, since message set
     * expect: contains namespace which replaced {{ns}}
     * expect: message contained rendered italic syntax
     */
    function testRenderEmptymsg() {
        global $conf;

        $noexistns        = 'nonexisting:namespace';
        $emptyindexsyntax = "{{indexmenu>$noexistns}}";

        $xhtml  = new Doku_Renderer_xhtml();
        $plugin = new syntax_plugin_indexmenu_indexmenu();

        $null   = new Doku_Handler();
        $result = $plugin->handle($emptyindexsyntax, 0, 10, $null);

        //no empty message
        $plugin->render('xhtml', $xhtml, $result);
        $doc = phpQuery::newDocument($xhtml->doc);
        $this->assertEquals(0, pq('p', $doc)->length);

        // Fill in empty message
        $conf['plugin']['indexmenu']['empty_msg'] = 'This namespace is //empty//: {{ns}}';
        $plugin->render('xhtml', $xhtml, $result);
        $doc = phpQuery::newDocument($xhtml->doc);

        $this->assertEquals(1, pq('p', $doc)->length);
        $this->assertEquals(1, pq("p:contains($noexistns)")->length);
        $this->assertEquals(1, pq("p em")->length);
    }

}
