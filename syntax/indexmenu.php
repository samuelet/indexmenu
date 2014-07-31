<?php
/**
 * Info Indexmenu: Show a customizable and sortable index for a namespace.
 *
 * @license     GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author      Samuele Tognini <samuele@samuele.netsons.org>
 *
 */

if(!defined('DOKU_INC')) die();
if(!defined('INDEXMENU_IMG_ABSDIR')) define('INDEXMENU_IMG_ABSDIR', DOKU_PLUGIN."indexmenu/images");

require_once(DOKU_INC.'inc/search.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_indexmenu_indexmenu extends DokuWiki_Syntax_Plugin {

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
        $maxjs    = 0;
        $max      = 0;
        $jsajax   = '';
        $nss      = array();
        $skipns   = array();
        $skipfile = array();

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
            if(is_numeric($ns_opt[2])) $level = $ns_opt[2];
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
        } else $sort = 0;
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
                    if(isset($match_ojs[1])) $dir = $match_ojs[1];
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
            $hsort
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
     * @param   $mode   string          output format being rendered
     * @param   $renderer Doku_Renderer the current renderer object
     * @param   $data     array         data created by handler()
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
                if(!isset($data[0])) $data[0] = '..';
                $data[6]['nss'][]        = array(getNS($INFO['id']));
                $renderer->info['cache'] = FALSE;
            }

            if($data[1]['context']) {
                //resolve current id relative namespaces
                $data[0] = $this->_parse_ns($data[0], $INFO['id']);
                foreach($data[6]['nss'] as $key=> $value) {
                    $data[6]['nss'][$key][0] = $this->_parse_ns($value[0], $INFO['id']);
                }
                $renderer->info['cache'] = FALSE;
            }
            $n = $this->_indexmenu($data);
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
                $renderer->meta['indexmenu'] = TRUE;
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
    private function _indexmenu($myns) {
        global $conf;
        $ns          = $myns[0];
        $js_opts     = $myns[1]; //theme, identifier, nocookie, navbar, noscroll, maxjs, notoc, jsajax, context, nomenu
        $this->sort  = $myns[2];
        $this->msort = $myns[3];
        $this->rsort = $myns[4];
        $this->nsort = $myns[5];
        $opts        = $myns[6]; //level, nons, nopg, nss, max, js, skip_index, skip_file, headpage, hide_headpage
        $this->hsort = $myns[7];
        $data        = array();
        $js_name     = "indexmenu_".$js_opts['identifier'];
        $fsdir       = "/".utf8_encodeFN(str_replace(':', '/', $ns));
        if($this->sort || $this->msort || $this->rsort || $this->hsort) {
            $this->_search($data, $conf['datadir'], array($this, '_search_index'), $opts, $fsdir);
        } else {
            search($data, $conf['datadir'], array($this, '_search_index'), $opts, $fsdir);
        }
        if(!$data) return false;

        // javascript index
        $output_tmp = "";
        if($opts['js']) {
            $ns         = str_replace('/', ':', $ns);
            $output_tmp = $this->_jstree($data, $ns, $js_opts, $js_name, $opts['max']);

            //remove unwanted nodes from standard index
            $this->_clean_data($data);
        }

        // Nojs dokuwiki index
        //    extra div needed when index is first element in sidebar of dokuwiki template, template uses this to toggle sidebar
        //    the toggle interacts with hide needed for js option.
        $output = "\n";
        $output .= '<div><div id="nojs_'.$js_name.'" data-jsajax="'.utf8_encodeFN($js_opts['jsajax']).'" class="indexmenu_nojs">'."\n";
        $output .= html_buildlist($data, 'idx', array($this, "_html_list_index"), "html_li_index");
        $output .= "</div></div>\n";
        $output .= $output_tmp;
        return $output;
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
    private function _jstree($data, $ns, $js_opts, $js_name, $max) {
        global $conf;
        $hns = false;
        if(empty($data)) return false;

        //Render requested ns as root
        $headpage = $this->getConf('headpage');
        //if rootnamespace and headpage, then add startpage as headpage - TODO seems not logic, when desired use $conf[headpage]=:start: ??
        if(empty($ns) && !empty($headpage)) $headpage .= ','.$conf['start'];
        $title = $this->_getTitle($ns, $headpage, $hns);
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
        $json = new JSON();
        $out .= $js_name.".add('".idfilter(cleanID($ns), false)."',0,-1,".$json->encode($title);
        if($hns) $out .= ",'".idfilter(cleanID($hns), false)."'";
        $out .= ");\n";
        //add nodes
        $anodes = $this->_jsnodes($data, $js_name);
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
        if($js_opts['nomenu']) $out .= ",1";
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
     * @param int    $noajax  return as inline js (=1) or array for ajax response (=0)
     * @return array|bool returns array with
     *     - a string of the javascript nodes
     *     - and a string of space separated numbers of the opened nodes
     *    or false when no data provided
     */
    public function _jsnodes($data, $js_name, $noajax = 1) {
        if(empty($data)) return false;
        //Array of nodes to check
        $q = array('0');
        //Current open node
        $node  = 0;
        $out   = '';
        $extra = '';
        if($noajax) {
            $jscmd = $js_name.".add";
            $separator   = ";\n";
        } else {
            $jscmd = "new Array ";
            $separator   = ",";
        }
        $json = new JSON();
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
                if($item['open']) ($item['level'] < $data[$node]['level']) ? $node = $i : $extra .= "$i ";
                //insert node in last position
                array_push($q, $i);
            }
            $out .= $jscmd."('".idfilter($item['id'], false)."',$i,".$father.",".$json->encode($item['title']);
            //hns
            ($item['hns']) ? $out .= ",'".idfilter($item['hns'], false)."'" : $out .= ",0";
            ($item['type'] == 'd' || $item['type'] == 'l') ? $out .= ",1" : $out .= ",0";
            //MAX option
            ($item['type'] == 'l') ? $out .= ",1" : $out .= ",0";
            $out .= ")".$separator;
        }
        $extra = rtrim($extra, ' ');
        return array($out, $extra);
    }

    /**
     * Get namespace title, checking for headpages
     *
     * @author  Samuele Tognini <samuele@samuele.netsons.org>
     * @param string $ns namespace
     * @param string $headpage commaseparated headpages options and headpages
     * @param string $hns reference pageid of headpage, false when not existing
     * @return string when headpage & heading on: title of headpage, otherwise: namespace name
     */
    private function _getTitle($ns, $headpage, &$hns) {
        global $conf;
        $hns   = false;
        $title = noNS($ns);
        if(empty($headpage)) return $title;
        $ahp = explode(",", $headpage);
        foreach($ahp as $hp) {
            switch($hp) {
                case ":inside:":
                    $page = $ns.":".noNS($ns);
                    break;
                case ":same:":
                    $page = $ns;
                    break;
                //it's an inside start
                case ":start:":
                    $page = ltrim($ns.":".$conf['start'], ":");
                    break;
                //inside pages
                default:
                    $page = $ns.":".$hp;
            }
            //check headpage
            if(@file_exists(wikiFN($page)) && auth_quickaclcheck($page) >= AUTH_READ) {
                if($conf['useheading'] == 1 || $conf['useheading'] === 'navigation') {
                    $title_tmp = p_get_first_heading($page, FALSE);
                    if(!is_null($title_tmp)) $title = $title_tmp;
                }
                $title = htmlspecialchars($title, ENT_QUOTES);
                $hns   = $page;
                //headpage found, exit for
                break;
            }
        }
        return $title;
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
        //Just for old reelases compatibility
        if(empty($ns) || $ns == '..') $ns = ":..";
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
     * Callback that adds an item of namespace/page to the browsable index, if it fits in the specified options
     *
     * $opts['skip_index'] string regexp matching namespaceids to skip
     * $opts['skip_file']  string regexp matching pageids to skip
     * $opts['headpage']   string headpages options or pageids
     * $opts['level']      int    desired depth of main namespace, -1 = all levels
     * $opts['nss']        array with entries: array(namespaceid,level) specifying namespaces with their own level
     * $opts['nons']       bool   exclude namespace nodes
     * $opts['max']        int    If initially closed, the node at max level will retrieve all its child nodes through the AJAX mechanism
     * $opts['nopg']       bool   exclude page nodes
     * $opts['hide_headpage'] int don't hide (0) or hide (1)
     * $opts['js']         bool   use js-render
     *
     * @author  Andreas Gohr <andi@splitbrain.org>
     * modified by Samuele Tognini <samuele@samuele.netsons.org>
     * @param array  $data Already collected nodes
     * @param string $base Where to start the search, usually this is $conf['datadir']
     * @param string $file Current file or directory relative to $base
     * @param string $type Type either 'd' for directory or 'f' for file
     * @param int    $lvl  Current recursion depht
     * @param array  $opts Option array as given to search(), see above.
     * @return bool if this directory should be traversed (true) or not (false)
     */
    public function _search_index(&$data, $base, $file, $type, $lvl, $opts) {
        global $conf;
        $hns        = false;
        $isopen     = false;
        $title      = null;
        $skip_index = $opts['skip_index'];
        $skip_file  = $opts['skip_file'];
        $headpage   = $opts['headpage'];
        $id         = pathID($file);
        if($type == 'd') {
            // Skip folders in plugin conf
            foreach($skip_index as $skipi) {
                if(!empty($skipi) && preg_match($skipi, $id))
                    return false;
            }
            //check ACL (for sneaky_index namespaces too).
            if($conf['sneaky_index'] && auth_quickaclcheck($id.':') < AUTH_READ) return false;
            //Open requested level
            if($opts['level'] > $lvl || $opts['level'] == -1) $isopen = true;
            //Search optional namespaces
            if(!empty($opts['nss'])) {
                $nss = $opts['nss'];
                for($a = 0; $a < count($nss); $a++) {
                    if(preg_match("/^".$id."($|:.+)/i", $nss[$a][0], $match)) {
                        //It contains an optional namespace
                        $isopen = true;
                    } elseif(preg_match("/^".$nss[$a][0]."(:.*)/i", $id, $match)) {
                        //It's inside an optional namespace
                        if($nss[$a][1] == -1 || substr_count($match[1], ":") < $nss[$a][1]) {
                            $isopen = true;
                        } else {
                            $isopen = false;
                        }
                    }
                }
            }
            if($opts['nons']) {
                return $isopen;
            } elseif($opts['max'] > 0 && !$isopen && $lvl >= $opts['max']) {
                $isopen = false;
                //Stop recursive searching
                $return = false;
                //change type
                $type = "l";
            } elseif($opts['js']) {
                $return = true;
            } else {
                $return = $isopen;
            }
            //Set title and headpage
            $title = $this->_getTitle($id, $headpage, $hns);
            //link namespace nodes to start pages when excluding page nodes
            if(!$hns && $opts['nopg']) $hns = $id.":".$conf['start'];
        } else {
            //Nopg.Dont show pages
            if($opts['nopg']) return false;
            $return = true;
            //Nons.Set all pages at first level
            if($opts['nons']) $lvl = 1;
            //don't add
            if(substr($file, -4) != '.txt') return false;
            //check hiddens and acl
            if(isHiddenPage($id) || auth_quickaclcheck($id) < AUTH_READ) return false;
            //Skip files in plugin conf
            foreach($skip_file as $skipf) {
                if(!empty($skipf) && preg_match($skipf, $id))
                    return false;
            }
            //Skip headpages to hide
            if(!$opts['nons'] && !empty($headpage) && $opts['hide_headpage']) {
                //start page is in root
                if($id == $conf['start']) return false;
                $ahp = explode(",", $headpage);
                foreach($ahp as $hp) {
                    switch($hp) {
                        case ":inside:":
                            if(noNS($id) == noNS(getNS($id))) return false;
                            break;
                        case ":same:":
                            if(@is_dir(dirname(wikiFN($id))."/".utf8_encodeFN(noNS($id)))) return false;
                            break;
                        //it' s an inside start
                        case ":start:":
                            if(noNS($id) == $conf['start']) return false;
                            break;
                        default:
                            if(noNS($id) == cleanID($hp)) return false;
                    }
                }
            }

            //Set title
            if($conf['useheading'] == 1 || $conf['useheading'] === 'navigation') {
                $title = p_get_first_heading($id, FALSE);
            }
            if(is_null($title)) $title = noNS($id);
            $title = htmlspecialchars($title, ENT_QUOTES);
        }

        $item         = array(
            'id'     => $id,
            'type'   => $type,
            'level'  => $lvl,
            'open'   => $isopen,
            'title'  => $title,
            'hns'    => $hns,
            'file'   => $file,
            'return' => $return
        );
        $item['sort'] = $this->_setorder($item);
        $data[]       = $item;
        return $return;
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
                if($item['open']) $tagid .= ' open';
            }

            if($markCurrentPage) $ret .= '<span class="curid">';
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

    /**
     * callback that recurse directory
     *
     * This function recurses into a given base directory
     * and calls the supplied function for each file and directory
     *
     * Similar to search() of inc/search.php, but has extended sorting options
     *
     * @param   array     $data The results of the search are stored here
     * @param   string    $base Where to start the search
     * @param   callback  $func Callback (function name or array with object,method)
     * @param   array     $opts List of indexmenu options
     * @param   string    $dir  Current directory beyond $base
     * @param   int       $lvl  Recursion Level
     *
     * @author  Andreas Gohr <andi@splitbrain.org>
     * @author  modified by Samuele Tognini <samuele@samuele.netsons.org>
     */
    public function _search(&$data, $base, $func, $opts, $dir = '', $lvl = 1) {
        $dirs      = array();
        $files     = array();
        $files_tmp = array();
        $dirs_tmp  = array();
        $count = count($data);

        //read in directories and files
        $dh = @opendir($base.'/'.$dir);
        if(!$dh) return;
        while(($file = readdir($dh)) !== false) {
            //skip hidden files and upper dirs
            if(preg_match('/^[\._]/', $file)) continue;
            if(is_dir($base.'/'.$dir.'/'.$file)) {
                $dirs[] = $dir.'/'.$file;
                continue;
            }
            $files[] = $dir.'/'.$file;
        }
        closedir($dh);

        //Collect and sort dirs
        if($this->nsort) {
            //collect the wanted directories in dirs_tmp
            foreach($dirs as $dir) {
                call_user_func_array($func, array(&$dirs_tmp, $base, $dir, 'd', $lvl, $opts));
            }
            //sort directories
            usort($dirs_tmp, array($this, "_cmp"));
            //add and search each directory
            foreach($dirs_tmp as $dir) {
                $data[] = $dir;
                if($dir['return']) {
                    $this->_search($data, $base, $func, $opts, $dir['file'], $lvl + 1);
                }
            }
        } else {
            //sort by page name
            sort($dirs);
            //collect directories
            foreach($dirs as $dir) {
                if(call_user_func_array($func, array(&$data, $base, $dir, 'd', $lvl, $opts))) {
                    $this->_search($data, $base, $func, $opts, $dir, $lvl + 1);
                }
            }
        }

        //Collect and sort files
        foreach($files as $file) {
            call_user_func_array($func, array(&$files_tmp, $base, $file, 'f', $lvl, $opts));
        }
        usort($files_tmp, array($this, "_cmp"));

        //count added items
        $added = count($data) - $count;

        if($added === 0 && empty($files_tmp)) {
            //remove empty directory again, only if it has not a headpage associated
            $v = end($data);
            if(!$v['hns']) array_pop($data);
        } else {
            //add files to index
            $data = array_merge($data, $files_tmp);
        }
    }

    /**
     * callback that sorts nodes
     *
     * @param array $a first node as array with 'sort' entry
     * @param array $b second node as array with 'sort' entry
     * @return int if less than zero 1st node is less than 2nd, otherwise equal respectively larger
     */
    private function _cmp($a, $b) {
        if($this->rsort) {
            return strnatcasecmp($b['sort'], $a['sort']);
        } else {
            return strnatcasecmp($a['sort'], $b['sort']);
        }
    }

    /**
     * Add sort information to item.
     *
     * @author  Samuele Tognini <samuele@samuele.netsons.org>
     *
     * @param array $item
     * @return bool|int|mixed|string
     */
    private function _setorder($item) {
        global $conf;

        $sort = false;
        $page = false;
        if($item['type'] == 'd' || $item['type'] == 'l') {
            //Fake order info when nsort is not requested
            ($this->nsort) ? $page = $item['hns'] : $sort = 0;
        }
        if($item['type'] == 'f') $page = $item['id'];
        if($page) {
            if($this->hsort && noNS($item['id']) == $conf['start']) $sort = 1;
            if($this->msort) $sort = p_get_metadata($page, $this->msort);
            if(!$sort && $this->sort) {
                switch($this->sort) {
                    case 't':
                        $sort = $item['title'];
                        break;
                    case 'd':
                        $sort = @filectime(wikiFN($page));
                        break;
                }
            }
        }
        if($sort === false) $sort = noNS($item['id']);
        return $sort;
    }
} //Indexmenu class end  
