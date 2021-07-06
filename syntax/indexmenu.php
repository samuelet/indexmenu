<?php
/**
 * Info Indexmenu: Show a customizable and sortable index for a namespace.
 *
 * @license     GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author      Samuele Tognini <samuele@samuele.netsons.org>
 *
 */

if(!defined('INDEXMENU_IMG_ABSDIR')) define('INDEXMENU_IMG_ABSDIR', DOKU_PLUGIN."indexmenu/images");

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_indexmenu_indexmenu extends DokuWiki_Syntax_Plugin {

    /**
     * @var bool|string sort by t=title, d=date of creation
     */
    var $sort = false;
    var $msort = false;
    var $rsort = false;
    var $nsort = false;
    var $hsort = false;

    /**
     * What kind of syntax are we?
     */
    public function getType() {
        return 'substition';
    }

    /**
     * Behavior regarding the paragraph
     */
    public function getPType() {
        return 'block';
    }

    /**
     * Where to sort in?
     */
    public function getSort() {
        return 138;
    }

    /**
     * Connect pattern to lexer
     *
     * @param string $mode
     */
    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{indexmenu>.+?}}', $mode, 'plugin_indexmenu_indexmenu');
    }

    /**
     * Handler to prepare matched data for the rendering process
     *
     * @param   string       $match   The text matched by the patterns
     * @param   int          $state   The lexer state for the match
     * @param   int          $pos     The character position of the matched text
     * @param   Doku_Handler $handler The Doku_Handler object
     * @return  array Return an array with all data you want to use in render
     */
    public function handle($match, $state, $pos, Doku_Handler $handler) {
        $theme    = 'default';
        $level    = -1;
        $gen_id   = 'random';
        $maxjs    = 1;
        $max      = 0;
        $jsajax   = '';
        $nss      = array();
        $skipns   = array();
        $skipfile = array();
        $jsversion = 0; //0:both, 1:dTree, 2:Fancytree

        $defaultsstr = $this->getConf('defaultoptions');
        $defaults = explode(' ', $defaultsstr);

        $match = substr($match, 12, -2);
        //split namespace,level,theme
        list($nsstr, $optsstr) = explode('|', $match, 2);
        //split options
        $opts = explode(' ', $optsstr);

        //Context option
        $context = $this->hasOption($defaults, $opts, 'context');

        //split optional namespaces
        $nss_temp = preg_split("/ /u", $nsstr, -1, PREG_SPLIT_NO_EMPTY);
        //Array optional namespace => level
        for($i = 1; $i < count($nss_temp); $i++) {
            $nsss = preg_split("/#/u", $nss_temp[$i]);
            if(!$context) {
                $nsss[0] = $this->_parse_ns($nsss[0]);
            }
            $nss[] = array($nsss[0], (is_numeric($nsss[1])) ? $nsss[1] : $level);
        }
        //split main requested namespace
        if(preg_match('/(.*)#(\S*)/u', $nss_temp[0], $ns_opt)) {
            //split level
            $ns = $ns_opt[1];
            if(is_numeric($ns_opt[2])) {
                $level = $ns_opt[2];
            }
        } else {
            $ns = $nss_temp[0];
        }
        if(!$context) {
            $ns = $this->_parse_ns($ns);
        }

        //nocookie option (disable for uncached pages)
        $nocookie = $context || $this->hasOption($defaults, $opts, 'nocookie');
        //noscroll option
        $noscroll = $this->hasOption($defaults, $opts, 'noscroll');
        //Open at current namespace option
        $navbar = $this->hasOption($defaults, $opts, 'navbar');
        //no namespaces  options
        $nons = $this->hasOption($defaults, $opts, 'nons');
        //no pages option
        $nopg = $this->hasOption($defaults, $opts, 'nopg');
        //disable toc preview
        $notoc = $this->hasOption($defaults, $opts, 'notoc');
        //disable the right context menu
        $nomenu = $this->hasOption($defaults, $opts, 'nomenu');
        //Main sort method
        $tsort = $this->hasOption($defaults, $opts, 'tsort');
        $dsort = $this->hasOption($defaults, $opts, 'dsort');
        if($tsort) {
            $sort = 't';
        } elseif($dsort) {
            $sort = 'd';
        } else {
            $sort = 0;
        }
        //sort directories in the same way as files
        $nsort = $this->hasOption($defaults, $opts, 'nsort');
        //sort headpages up
        $hsort = $this->hasOption($defaults, $opts, 'hsort');
        //Metadata sort method
        if($msort = $this->hasOption($defaults, $opts, 'msort')) {
            $msort = 'indexmenu_n';
        } elseif($value = $this->getOption($defaultsstr, $optsstr, '/msort#(\S+)/u')) {
            $msort = str_replace(':', ' ', $value);
        }
        //reverse sort
        $rsort = $this->hasOption($defaults, $opts, 'rsort');

        if($sort) $jsajax .= "&sort=" . $sort;
        if($msort) $jsajax .= "&msort=" . $msort;
        if($rsort) $jsajax .= "&rsort=1";
        if($nsort) $jsajax .= "&nsort=1";
        if($hsort) $jsajax .= "&hsort=1";
        if($nopg) $jsajax .= "&nopg=1";

        //javascript option
        $dir = '';
        //check defaults for js,js#theme, #theme
        if(!$js = in_array('js', $defaults)) {
            if(preg_match('/(?:^|\s)(js)?#(\S*)/u', $defaultsstr, $match_djs) > 0) {
                if(!empty($match_djs[1])) $js = true;
                if(isset($match_djs[2])) $dir = $match_djs[2];
            }
        }
        //check opts for nojs,#theme or js,js#theme
        if($js) {
            if(in_array('nojs', $opts)) {
                $js = false;
            } else {
                if(preg_match('/(?:^|\s)(?:js)?#(\S*)/u', $optsstr, $match_ojs) > 0) {
                    if(isset($match_ojs[1])) $dir = $match_ojs[1];
                }
            }
        } else {
            if($js = in_array('js', $opts)) {
                //use theme from the defaults
            } else {
                if(preg_match('/(?:^|\s)js#(\S*)/u', $optsstr, $match_ojs) > 0) {
                    $js = true;
                    if(isset($match_ojs[1])) {
                        $dir = $match_ojs[1];
                    }
                }
            }
        }

        if($js) {
            //exist theme?
            if(!empty($dir) && is_dir(INDEXMENU_IMG_ABSDIR . "/" . $dir)) {
                $theme = $dir;
            }

            //id generation method
            $gen_id = $this->getOption($defaultsstr, $optsstr, '/id#(\S+)/u');

            //max option
            if($maxmatches = $this->getOption($defaultsstr, $optsstr, '/max#(\d+)($|\s+|#(\d+))/u', true)) {
                $max = $maxmatches[1];
                if($maxmatches[3]) {
                    $jsajax .= "&max=" . $maxmatches[3];
                }
                //disable cookie to avoid javascript errors
                $nocookie = true;
            } else {
                $max = 0;
            }

            //max js option
            if($maxjsvalue = $this->getOption($defaultsstr, $optsstr, '/maxjs#(\d+)/u')) {
                $maxjs = $maxjsvalue;
            }
            // @deprecated july 2021 -- allow (temporary) switching between versions of the js treemenu
            $treenew = $this->hasOption($defaults, $opts, 'treenew'); //overrides old and both
            $treeold = $this->hasOption($defaults, $opts, 'treeold'); //overrides both
            $treeboth = $this->hasOption($defaults, $opts, 'treeboth');
            $jsversion = $treenew ? 2 : ($treeold ? 1 : ($treeboth ? 0 : $jsversion));
        }
        if(is_numeric($gen_id)) {
            $identifier = $gen_id;
        } elseif($gen_id == 'ns') {
            $identifier = sprintf("%u", crc32($ns));
        } else {
            $identifier = uniqid(rand());
        }

        //skip namespaces in index
        $skipns[] = $this->getConf('skip_index');
        if(preg_match('/skipns[\+=](\S+)/u', $optsstr, $sns) > 0) {
            //first sign is: '+' (parallel to conf) or '=' (replace conf)
            $action = $sns[0][6];
            $index = 0;
            if($action == '+') {
                $index = 1;
            }
            $skipns[$index] = $sns[1];
            $jsajax .= "&skipns=" . utf8_encodeFN(($action == '+' ? '+' : '=') . $sns[1]);
        }
        //skip file
        $skipfile[] = $this->getConf('skip_file');
        if(preg_match('/skipfile[\+=](\S+)/u', $optsstr, $sf) > 0) {
            //first sign is: '+' (parallel to conf) or '=' (replace conf)
            $action = $sf[0][8];
            $index = 0;
            if($action == '+') {
                $index = 1;
            }
            $skipfile[$index] = $sf[1];
            $jsajax .= "&skipfile=" . utf8_encodeFN(($action == '+' ? '+' : '=') . $sf[1]);
        }

        //js options
        $js_opts = compact('theme', 'identifier', 'nocookie', 'navbar', 'noscroll', 'maxjs', 'notoc', 'jsajax', 'context', 'nomenu');

        return array(
            $ns,
            $js_opts,
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
                'headpage'      => $this->getConf('headpage'),
                'hide_headpage' => $this->getConf('hide_headpage')
            ),
            $hsort,
            $jsversion
        );
    }

    /**
     * Looks if the default options and syntax options has the requested option
     *
     * @param array  $defaultsopts array of default options
     * @param array  $opts         array of options provided via syntax
     * @param string $optionname   name of requested option
     * @return bool has optionname?
     */
    private function hasOption($defaultsopts, $opts, $optionname) {
        $name = $optionname;
        if(substr($optionname, 0, 2) == 'no') {
            $inversename = substr($optionname, 2);
        } else {
            $inversename = 'no' . $optionname;
        }

        if(in_array($name, $defaultsopts)) {
            return !in_array($inversename, $opts);
        } else {
            return in_array($name, $opts);
        }
    }

    /**
     * Looks for the value of the requested option in the default options and syntax options
     *
     * @param string $defaultsstr     default options string
     * @param string $optsstr         syntax options string
     * @param string $matchpattern    pattern to search for
     * @param bool   $multiplematches if multiple returns array, otherwise the first match
     * @return string|array
     */
    private function getOption($defaultsstr, $optsstr, $matchpattern, $multiplematches = false) {
        if(preg_match($matchpattern, $optsstr, $match_o) > 0) {
            if($multiplematches) {
                return $match_o;
            } else {
                return $match_o[1];
            }
        } elseif(preg_match($matchpattern, $defaultsstr, $match_d) > 0) {
            if($multiplematches) {
                return $match_d;
            } else {
                return $match_d[1];
            }
        }
        return false;
    }

    /**
     * Handles the actual output creation.
     *
     * @param string $mode output format being rendered
     * @param Doku_Renderer $renderer the current renderer object
     * @param array $data data created by handler()
     * @return  boolean                 rendered correctly?
     */
    public function render($mode, Doku_Renderer $renderer, $data) {
        global $ACT;
        global $conf;
        global $INFO;
        if($mode == 'xhtml') {
            /** @var Doku_Renderer_xhtml $renderer */
            if($ACT == 'preview') {
                //Check user permission to display indexmenu in a preview page
                if($this->getConf('only_admins') &&
                    $conf['useacl'] &&
                    $INFO['perm'] < AUTH_ADMIN
                )
                    return false;
                //disable cookies
                $data[1]['nocookie'] = true;
            }
            //Navbar with nojs
            if($data[1]['navbar'] && !$data[6]['js']) {
                if(!isset($data[0])) {
                    $data[0] = '..';
                }
                $data[6]['nss'][]        = array(getNS($INFO['id']));
                $renderer->info['cache'] = false;
            }

            if($data[1]['context']) {
                //resolve current id relative namespaces
                $data[0] = $this->_parse_ns($data[0], $INFO['id']);
                foreach($data[6]['nss'] as $key=> $value) {
                    $data[6]['nss'][$key][0] = $this->_parse_ns($value[0], $INFO['id']);
                }
                $renderer->info['cache'] = false;
            }
            $n = $this->buildHtmlIndexmenu($data);
            if(!@$n) {
                $n = $this->getConf('empty_msg');
                $n = str_replace('{{ns}}', cleanID($data[0]), $n);
                $n = p_render('xhtml', p_get_instructions($n), $info);
            }
            $renderer->doc .= $n;
            return true;

        } else if($mode == 'metadata') {
            /** @var Doku_Renderer_metadata $renderer */
            if(!($data[1]['navbar'] && !$data[6]['js']) && !$data[1]['context']) {
                //this is an indexmenu page that needs the PARSER_CACHE_USE event trigger;
                $renderer->meta['indexmenu'] = true;
            }
            $renderer->doc .= ((empty($data[0])) ? $conf['title'] : nons($data[0]))." index\n\n";
            unset($renderer->persistent['indexmenu']);
            return true;

        } else {
            return false;
        }
    }

    /**
     * Return the index
     *
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     *
     * This function is a simple hack of Dokuwiki @see html_index($ns)
     * @author Andreas Gohr <andi@splitbrain.org>
     *
     * @param array $myns the options for indexmenu
     * @return bool|string return html for a nojs index and when enabled the js rendered index, otherwise false
     */
    private function buildHtmlIndexmenu($myns) {
        global $conf;
        $ns          = $myns[0];
        $js_opts     = $myns[1]; //theme, identifier, nocookie, navbar, noscroll, maxjs, notoc, jsajax, context, nomenu
        $this->sort  = $myns[2];
        $this->msort = $myns[3];
        $this->rsort = $myns[4];
        $this->nsort = $myns[5];
        $opts        = $myns[6]; //level, nons, nopg, nss, max, js, skip_index, skip_file, headpage, hide_headpage
        $this->hsort = $myns[7];
        $jsversion   = $myns[8];// @deprecated temporary
        $js_name     = "indexmenu_".$js_opts['identifier'];

        $search = new \dokuwiki\plugin\indexmenu\Search($this->sort, $this->msort, $this->rsort, $this->nsort, $this->hsort);
        $data = $search->search($ns, $conf['datadir'], $opts);

        if(!$data) return false;

        // javascript index
        $output_js = "";
        if($opts['js']) {
            $ns         = str_replace('/', ':', $ns);
            $output_js = '';

            // $jsversion: 0:both, 1:dTree, 2:Fancytree
            if($jsversion < 2) {
                $output_js .= $this->builddTree($data, $ns, $js_opts, $js_name, $opts['max']);
            }
            if($jsversion !== 1) {
                $fancytreeData = $search->buildFancytreeData($data);
                $output_js .= $this->buildFancyTree($fancytreeData, $js_name);
            }

            //remove unwanted nodes from standard index
            $this->_clean_data($data);
        }
        $output = "\n";
        $output .= $this->buildNoJStree($data, $js_name, $js_opts['jsajax']);
        $output .=  $output_js;
        return $output;
    }

    private function buildNoJStree($data, $js_name, $jsajax) {
        // Nojs dokuwiki index
        //    extra div needed when index is first element in sidebar of dokuwiki template, template uses this to toggle sidebar
        //    the toggle interacts with hide needed for js option.
        return '<div>'
            . '<div id="nojs_'.$js_name.'" data-jsajax="'.utf8_encodeFN($jsajax).'" class="indexmenu_nojs">'."\n"
            . html_buildlist($data, 'idx', array($this, "_html_list_index"), "html_li_index")
            . '</div>'
            . '</div>'."\n";
    }

    private function buildFancyTree($fancytreeData, $js_name) {
        $options = [
            'contextmenu' => false,
            'url' => ''
        ];
        return '<div id="tree2_'.$js_name.'" class="indexmenu_js2" data-type="json">'.json_encode($fancytreeData).'</div>'
        . '<div id="tree22_'.$js_name.'" class="indexmenu_js2" data-options=\''.json_encode($options).'\'></div>';
    }

    /**
     * Build the browsable index of pages using javascript
     *
     * @author  Samuele Tognini <samuele@samuele.netsons.org>
     * @author  Rene Hadler
     *
     * @param array  $data    array with items of the tree
     * @param string $ns      requested namespace
     * @param array  $js_opts options for javascript renderer
     * @param string $js_name identifier for this index
     * @param int    $max     the node at $max level will retrieve all its child nodes through the AJAX mechanism
     * @return bool|string returns inline javascript or false
     */
    private function builddTree($data, $ns, $js_opts, $js_name, $max) {
        global $conf;
        $hns = false;
        if(empty($data)) return false;

        //Render requested ns as root
        $headpage = $this->getConf('headpage');
        //if rootnamespace and headpage, then add startpage as headpage - TODO seems not logic, when desired use $conf[headpage]=:start: ??
        if(empty($ns) && !empty($headpage)) {
            $headpage .= ','.$conf['start'];
        }
        $search = new \dokuwiki\plugin\indexmenu\Search(false, false, false, false, false);
        $title = $search->_getTitle($ns, $headpage, $hns);
        if(empty($title)) {
            if(empty($ns)){
                $title = htmlspecialchars($conf['title'], ENT_QUOTES);
            } else{
                $title = $ns;
            }
        }
        // inline javascript
        $out = "<script type='text/javascript' charset='utf-8'>\n";
        $out .= "<!--//--><![CDATA[//><!--\n";
        $out .= "var $js_name = new dTree('".$js_name."','".$js_opts['theme']."');\n";
        //javascript config options
        $sepchar = idfilter(':', false);
        $out .= "$js_name.config.urlbase='".substr(wl(":"), 0, -1)."';\n";
        $out .= "$js_name.config.sepchar='".$sepchar."';\n";
        if($js_opts['notoc'])          $out .= "$js_name.config.toc=false;\n";
        if($js_opts['nocookie'])       $out .= "$js_name.config.useCookies=false;\n";
        if($js_opts['noscroll'])       $out .= "$js_name.config.scroll=false;\n";
        if($js_opts['maxjs'] > 0)      $out .= "$js_name.config.maxjs=".$js_opts['maxjs'].";\n";
        if(!empty($js_opts['jsajax'])) $out .= "$js_name.config.jsajax='".utf8_encodeFN($js_opts['jsajax'])."';\n";

        //add root node
        $out .= $js_name.".add('".idfilter(cleanID($ns), false)."',0,-1,".json_encode($title);
        if($hns) {
            $out .= ",'".idfilter(cleanID($hns), false)."'";
        }
        $out .= ");\n";
        //add nodes
        $anodes = $this->builddTreeNodes($data, $js_name);
        $out .= $anodes[0];
        //write to document
        $out .= "document.write(".$js_name.");\n";
        //initialize index
        $out .= "jQuery(function(){".$js_name.".init(";
        $out .= (int) is_file(INDEXMENU_IMG_ABSDIR.'/'.$js_opts['theme'].'/style.css').",";
        $out .= (int) $js_opts['nocookie'].",";
        $out .= '"'.$anodes[1].'",';
        $out .= (int) $js_opts['navbar'].",";
        $out .= (int) $max;
        if($js_opts['nomenu']) {
            $out .= ",1";
        }
        $out .= ");});\n";

        $out .= "//--><!]]>\n";
        $out .= "</script>\n";
        return $out;
    }

    /**
     * Return array of javascript nodes and nodes to open.
     *
     * @author  Samuele Tognini <samuele@samuele.netsons.org>
     * @param array  $data    array with items of the tree
     * @param string $js_name identifier for this index
     * @param boolean $noajax  return as inline js (=true) or array for ajax response (=false)
     * @return array|bool returns array with
     *     - a string of the javascript nodes
     *     - and a string of space separated numbers of the opened nodes
     *    or false when no data provided
     */
    public function builddTreeNodes($data, $js_name, $noajax = true) {
        if(empty($data)) return false;
        //Array of nodes to check
        $q = array('0');
        //Current open node
        $node  = 0;
        $out   = '';
        $opennodes = '';
        if($noajax) {
            $jscmd = $js_name.".add";
            $separator   = ";\n";
        } else {
            $jscmd = "new Array ";
            $separator   = ",";
        }

        foreach($data as $i=> $item) {
            $i++;
            //Remove already processed nodes (greater level = lower level)
            while($item['level'] <= $data[end($q) - 1]['level']) {
                array_pop($q);
            }

            //till i found its father node
            if($item['level'] == 1) {
                //root node
                $father = '0';
            } else {
                //Father node
                $father = end($q);
            }
            //add node and its options
            if($item['type'] == 'd') {
                //Search the lowest open node of a tree branch in order to open it.
                if($item['open']) {
                    if($item['level'] < $data[$node]['level']) {
                        $node = $i;
                    } else {
                        $opennodes .= "$i ";
                    }
                }
                //insert node in last position
                array_push($q, $i);
            }
            $out .= $jscmd."('".idfilter($item['id'], false)."',$i,".$father.",".json_encode($item['title']);
            //hns
            if($item['hns']) {
                $out .= ",'".idfilter($item['hns'], false)."'";
            } else {
                $out .= ",0";
            }
            if($item['type'] == 'd' || $item['type'] == 'l') {
                $out .= ",1";
            } else {
                $out .= ",0";
            }
            //MAX option
            if($item['type'] == 'l') {
                $out .= ",1";
            }else{
                $out .= ",0";
            }
            $out .= ")".$separator;
        }
        $opennodes = rtrim($opennodes, ' ');
        return array($out, $opennodes);
    }

    /**
     * Parse namespace request
     *
     * @author  Samuele Tognini <samuele@samuele.netsons.org>
     * @param string $ns namespaceid
     * @param bool   $id page id to resolve $ns relative to.
     * @return string id of namespace
     */
    public function _parse_ns($ns, $id = FALSE) {
        if(!$id) {
            global $ID;
            $id = $ID;
        }
        //Just for old releases compatibility
        if(empty($ns) || $ns == '..') {
            $ns = ":..";
        }
        return resolve_id(getNS($id), $ns);
    }

    /**
     * Clean index data from unwanted nodes in nojs mode.
     *
     * @author  Samuele Tognini <samuele@samuele.netsons.org>
     * @param array $data nodes of the tree
     * @return void
     */
    private function _clean_data(&$data) {
        foreach($data as $i=> $item) {
            //closed node
            if($item['type'] == "d" && !$item['open']) {
                $a     = $i + 1;
                $level = $data[$i]['level'];
                //search and remove every lower and closed nodes
                while($data[$a]['level'] > $level && !$data[$a]['open']) {
                    unset($data[$a]);
                    $a++;
                }
            }
        }
    }


    /**
     * Callback Index item formatter
     *
     * User function for @see html_buildlist()
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     * @author Rik Blok
     *
     * @param array $item item described by array with at least the entries
     *          - id    page id/namespace id
     *          - type  'd', 'l'(directory which is not yet opened) or 'f'
     *          - open  is node open
     *          - title title of link
     *          - hns   page id of headpage of the namespace or false
     * @return string html of the content of a list item
     */
    public function _html_list_index($item) {
        global $INFO;
        $ret = '';

        //namespace
        if($item['type'] == 'd' || $item['type'] == 'l') {
            $markCurrentPage = false;

            $link = $item['id'];
            $more = 'idx='.$item['id'];
            //namespace link
            if($item['hns']) {
                $link  = $item['hns'];
                $tagid = "indexmenu_idx_head";
                $more  = '';
                //current page is shown?
                $markCurrentPage = $this->getConf('hide_headpage') && $item['hns'] == $INFO['id'];
            } else {
                //namespace without headpage
                $tagid = "indexmenu_idx";
                if($item['open']) {
                    $tagid .= ' open';
                }
            }

            if($markCurrentPage) {
                $ret .= '<span class="curid">';
            }
            $ret .= '<a href="'.wl($link, $more).'" class="'.$tagid.'">';
            $ret .= $item['title'];
            $ret .= '</a>';
            if($markCurrentPage) $ret .= '</span>';
        } else {
            //page link
            $ret .= html_wikilink(':'.$item['id']);
        }
        return $ret;
    }

} //Indexmenu class end
