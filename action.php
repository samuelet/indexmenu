<?php
/**
 * Indexmenu Action Plugin:   Indexmenu Component.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Samuele Tognini <samuele@samuele.netsons.org>
 */

use dokuwiki\plugin\indexmenu\Search;

/**
 * Class action_plugin_indexmenu
 */
class action_plugin_indexmenu extends DokuWiki_Action_Plugin {

    /**
     * plugin should use this method to register its handlers with the dokuwiki's event controller
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object.
     */
    public function register(Doku_Event_Handler $controller) {
        if($this->getConf('only_admins')) {
            $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE', $this, '_checkperm');
        }
        if($this->getConf('page_index') != '') {
            $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, '_loadindex');
        }
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, '_extendJSINFO');
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, '_purgecache');
        if($this->getConf('show_sort')) {
            $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, '_showsort');
        }
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, '_ajax_call');
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'getDataFancyTree');
    }

    /**
     * Check if user has permission to insert indexmenu
     *
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     *
     * @param Doku_Event $event
     * @param mixed      $param not defined
     */
    public function _checkperm(Doku_Event $event, $param) {
        global $INFO;
        if(!$INFO['isadmin']) {
            $event->data[0][1] = preg_replace("/{{indexmenu(|_n)>.+?}}/", "", $event->data[0][1]);
        }
    }

    /**
     * Add additional info to $JSINFO
     *
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     * @author Gerrit Uitslag <klapinklapin@gmail.com>
     *
     * @param Doku_Event $event
     * @param mixed      $param not defined
     */
    public function _extendJSINFO(Doku_Event $event, $param) {
        global $INFO, $JSINFO;
        $JSINFO['isadmin'] = (int) $INFO['isadmin'];
        $JSINFO['isauth']  = (int) $INFO['userinfo'];
    }

    /**
     * Check for pages changes and eventually purge cache.
     *
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     *
     * @param Doku_Event $event
     * @param mixed      $param not defined
     */
    public function _purgecache(Doku_Event $event, $param) {
        global $ID;
        global $conf;
        /** @var cache_parser $cache */
        $cache = &$event->data;

        if(!isset($cache->page)) return;
        //purge only xhtml cache
        if($cache->mode != "xhtml") return;
        //Check if it is an indexmenu page
        if(!p_get_metadata($ID, 'indexmenu')) return;

        $aclcache = $this->getConf('aclcache');
        if($conf['useacl']) {
            $newkey = false;
            if($aclcache == 'user') {
                //Cache per user
                if($_SERVER['REMOTE_USER']) $newkey = $_SERVER['REMOTE_USER'];
            } else if($aclcache == 'groups') {
                //Cache per groups
                global $INFO;
                if($INFO['userinfo']['grps']) $newkey = implode('#', $INFO['userinfo']['grps']);
            }
            if($newkey) {
                $cache->key .= "#".$newkey;
                $cache->cache = getCacheName($cache->key, $cache->ext);
            }
        }
        //Check if a page is more recent than purgefile.
        if(@filemtime($cache->cache) < @filemtime($conf['cachedir'].'/purgefile')) {
            $event->preventDefault();
            $event->stopPropagation();
            $event->result = false;
        }
    }

    /**
     * Render a defined page as index.
     *
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     *
     * @param Doku_Event $event
     * @param mixed      $param not defined
     */
    public function _loadindex(Doku_Event $event, $param) {
        if('index' != $event->data) return;
        if(!file_exists(wikiFN($this->getConf('page_index')))) return;
        global $lang;
        print '<h1><a id="index" name="index">'.$lang['btn_index']."</a></h1>\n";
        print p_wiki_xhtml($this->getConf('page_index'));
        $event->preventDefault();
        $event->stopPropagation();

    }

    /**
     * Display the indexmenu sort number.
     *
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     *
     * @param Doku_Event $event
     * @param mixed      $param not defined
     */
    public function _showsort(Doku_Event $event, $param) {
        global $ID, $ACT, $INFO;
        if($INFO['isadmin'] && $ACT == 'show') {
            if($n = p_get_metadata($ID, 'indexmenu_n')) {
                ptln('<div class="info">');
                ptln($this->getLang('showsort').$n);
                ptln('</div>');
            }
        }
    }

    /**
     * Handles ajax requests for indexmenu
     *
     * @param Doku_Event $event
     * @param mixed      $param not defined
     */
    public function _ajax_call(Doku_Event $event, $param) {
        if($event->data !== 'indexmenu') {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        global $INPUT;
        switch($INPUT->str('req')) {
            case 'local':
                //list themes
                $this->getlocalThemes();
                break;

            case 'toc':
                //print toc preview
                if($INPUT->has('id')) print $this->print_toc($INPUT->str('id'));
                break;

            case 'index':
                //retrieval of data of the extra nodes for the indexmenu (if ajax loading set with max#m(#n)
                if($INPUT->has('idx')) print $this->print_index($INPUT->str('idx'));
                break;

            case 'fancytree':
                //2022-04-27: data for new index build with Fancytree
                $this->getDataFancyTree($event);
                break;
        }
    }

    public function getDataFancyTree(Doku_Event $event) {
        global $INPUT, $INFO;
//        if($event->data !== 'indexmenunew') {
//            return;
//        }
//        if($INPUT->str('req') !== 'fancytree') {
//            return;
//        }
//        //no other ajax call handlers needed
//        $event->stopPropagation();
//        $event->preventDefault();

//        $idxm     = new syntax_plugin_indexmenu_indexmenu();
//        $ns       = $idxm->parseNs(rawurldecode($ns)); // why not assuming a 'key' is offered?
        $ns = $INPUT->str('ns','', true);
        $ns = rtrim($ns,':'); //key of directory has extra : on the end
        $level    = -1; //opened levels. -1=all levels open
        $max      = 1; //levels to load by lazyloading. Before the default was 0. CHANGED to 1.
        $skipFile = [];
        $skipNs   = [];

        if($INPUT->int('max') > 0) {
            $max   = $INPUT->int('max'); // max#n#m, if init: #n, otherwise #m
            $level = $max;
        }
        if($INPUT->int('level',-10, true) >= -1) {
            $level = $INPUT->int('level');
        }
        $isInit = $INPUT->bool('init', false, true);

        $currentPage = $INPUT->str('currentpage','', true);
        if($isInit) { //TODO attention, depends on logic that js is only 1 if init
            $subnss = $INPUT->arr('subnss');
            $debug1=var_export($subnss,true);
            // if 'navbar' enabled add current ns to list
            if($INPUT->bool('navbar', false, true)) {
                $subnss[] = [getNS($currentPage)];
            }
            $debug2=var_export($subnss,true);
            // alternative, via javascript.. https://wwwendt.de/tech/fancytree/doc/jsdoc/Fancytree.html#loadKeyPath
        } else {
            $subnss = $INPUT->str('subnss', '', true);
            $subnss = [[cleanID($subnss), 1]];
        }

        $skipf = $INPUT->str('skipfile', '', true); // utf8_decodeFN($_REQUEST['skipfile']);
        $skipFile[] = $this->getConf('skip_file');
        if(isset($skipf)) {
            $index = 0;
            if($skipf[1] == '+') {
                $index = 1;
            }
            $skipFile[$index] = substr($skipf, 1);
        }
        $skipn = $INPUT->str('skipns', '', true); //utf8_decodeFN($_REQUEST['skipns']);
        $skipNs[] = $this->getConf('skip_index');
        if(isset($skipn)) {
            $index = 0;
            if($skipn[1] == '+') {
                $index = 1;
            }
            $skipNs[$index] = substr($skipn, 1);
        }

        $opts = array(
            'level'         => $level, //only set for init, lazy requests equal to max
            'nons'          => $INPUT->bool('nons', false, true), //only needed for init
            'nopg'          => $INPUT->bool('nopg', false, true),
            'subnss'        => $subnss, //init with complex array, only current ns if lazy
            'max'           => $max,
            'js'            => false, //DEPRECATED (for dTree: only init true, lazy requests false.) NOW not used, so false.
            'skipns'    => $skipNs,  //preprocessed to string, only part from syntax
            'skipfile'     => $skipFile, //preprocessed to string, only part from syntax
            'headpage'      => $this->getConf('headpage'),
            'hide_headpage' => $this->getConf('hide_headpage'),
        );

        $sort = [
            'sort' => $INPUT->str('sort', false, true),
            'msort' => $INPUT->str('msort', false, true),
            'rsort' => $INPUT->bool('rsort', false, true),
            'nsort' => $INPUT->bool('nsort', false, true),
            'hsort' => $INPUT->bool('hsort', false, true)
        ];

        $search = new Search($sort);
        $data = $search->search($ns, $opts);
        $fancytreeData = $search->buildFancytreeData($data, $isInit, $currentPage);

        if($isInit) {
            //for lazy loading are other items than children not supported.
            $fancytreeData['opts'] = $opts;
            $fancytreeData['sort'] = $sort;
            $fancytreeData['navbar'] = $INPUT->bool('navbar', false, true);
            $fancytreeData['debug1'] = $debug1;
            $fancytreeData['debug2'] = $debug2;

        } else {
            $fancytreeData[0]['opts'] = $opts;
            $fancytreeData[0]['sort'] = $sort;
        }

        header('Content-Type: application/json');
        echo json_encode($fancytreeData);

    }

    /**
     * Print a list of local themes
     *
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     * @author Gerrit Uitslag <klapinklapin@gmail.com>
     */
    private function getlocalThemes() {
        header('Content-Type: application/json');

        $themebase = 'lib/plugins/indexmenu/images';

        $handle = @opendir(DOKU_INC.$themebase);
        $themes = array();
        while(false !== ($file = readdir($handle))) {
            if(is_dir(DOKU_INC.$themebase.'/'.$file)
                && $file != "."
                && $file != ".."
                && $file != "repository"
                && $file != "tmp"
                && $file != ".svn"
            ) {
                $themes[] = $file;
            }
        }
        closedir($handle);
        sort($themes);

        echo json_encode([
            'themebase' => $themebase,
            'themes'    => $themes
        ]);
    }

    /**
     * Print a toc preview
     *
     * @param string $id
     * @return string
     *
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    private function print_toc($id) {
        $id = cleanID($id);
        if(auth_quickaclcheck($id) < AUTH_READ) return '';

        $meta = p_get_metadata($id);
        $toc  = $meta['description']['tableofcontents'] ?? [];

        if(count($toc) > 1) {
            //display ToC of two or more headings
            $out = $this->render_toc($toc);
        } else {
            //display page abstract
            $out = $this->render_abstract($id, $meta);
        }
        return $out;
    }

    /**
     * Return the TOC rendered to XHTML
     *
     * @param $toc
     * @return string
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @author Gerrit Uitslag <klapinklapin@gmail.com>
     */
    private function render_toc($toc) {
        global $lang;
        $out = '<div class="tocheader">'.DOKU_LF;
        $out .= $lang['toc'];
        $out .= '</div>'.DOKU_LF;
        $out .= '<div class="indexmenu_toc_inside">'.DOKU_LF;
        $out .= html_buildlist($toc, 'toc', array($this, '_indexmenu_list_toc'), 'html_li_default', true);
        $out .= '</div>'.DOKU_LF;
        return $out;
    }

    /**
     * Return the page abstract rendered to XHTML
     *
     * @param $id
     * @param array $meta by reference
     * @return string
     */
    private function render_abstract($id, &$meta) {
        $out = '<div class="tocheader">'.DOKU_LF;
        $out .= '<a href="'.wl($id).'">';
        $out .= ($meta['title']) ? htmlspecialchars($meta['title']) : htmlspecialchars(noNS($id));
        $out .= '</a>'.DOKU_LF;
        $out .= '</div>'.DOKU_LF;
        if($meta['description']['abstract']) {
            $out .= '<div class="indexmenu_toc_inside">'.DOKU_LF;
            $out .= p_render('xhtml', p_get_instructions($meta['description']['abstract']), $info);
            $out .= '</div>'.DOKU_LF.'</div>'.DOKU_LF;
        }
        return $out;
    }

    /**
     * Callback for html_buildlist
     *
     * @param $item
     * @return string
     */
    function _indexmenu_list_toc($item) {
        $id = cleanID($_REQUEST['id']);

        if(isset($item['hid'])) {
            $link = '#'.$item['hid'];
        } else {
            $link = $item['link'];
        }

        //prefix anchers with page id
        if($link[0] == '#') {
            $link = wl($id, $link, false, '');
        }
        return '<a href="'.$link.'">'.hsc($item['title']).'</a>';
    }

    /**
     * Print index nodes
     *
     * @param $ns
     * @return string
     *
     * @author Rene Hadler <rene.hadler@iteas.at>
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    private function print_index($ns) {
        global $conf, $INPUT;
        $idxm     = new syntax_plugin_indexmenu_indexmenu();
        $ns       = $idxm->parseNs(rawurldecode($ns));
        $level    = -1;
        $max      = 0;
        $data     = array();
        $skipfile = array();
        $skipns   = array();

        if($INPUT->int('max') > 0) {
            $max   = $INPUT->int('max');
            $level = $max;
        }
        $nss = $INPUT->str('nss','', true);
        $sort['sort'] = $INPUT->str('sort', '', true);
        $sort['msort'] = $INPUT->str('msort', '', true);
        $sort['rsort'] = $INPUT->bool('rsort', false, true);
        $sort['nsort'] = $INPUT->bool('nsort', false, true);
        $sort['hsort'] = $INPUT->bool('hsort', false, true);
        $search = new Search($sort);
        $fsdir       = "/".utf8_encodeFN(str_replace(':', '/', $ns));

        $skipf = utf8_decodeFN($_REQUEST['skipfile']);
        $skipfile[] = $this->getConf('skip_file');
        if(isset($skipf)) {
            $index = 0;
            if($skipf[1] == '+') {
                $index = 1;
            }
            $skipfile[$index] = substr($skipf, 1);
        }
        $skipn = utf8_decodeFN($_REQUEST['skipns']);
        $skipns[] = $this->getConf('skip_index');
        if(isset($skipn)) {
            $index = 0;
            if($skipn[1] == '+') {
                $index = 1;
            }
            $skipns[$index] = substr($skipn, 1);
        }

        $opts = array(
            'level'         => $level,
            'nons'          => $INPUT->bool('nons', false, true),
            'nss'           => array(array($nss, 1)),
            'max'           => $max,
            'js'            => false,
            'nopg'          => $INPUT->bool('nopg', false, true),
            'skipns'    => $skipns,
            'skipfile'     => $skipfile,
            'headpage'      => $idxm->getConf('headpage'),
            'hide_headpage' => $idxm->getConf('hide_headpage')
        );
        if($sort['sort'] || $sort['msort'] || $sort['rsort'] || $sort['hsort']) {
            $search->customSearch($data, $conf['datadir'], array($search, 'searchIndexmenuItems'), $opts, $fsdir);
        } else {
            search($data, $conf['datadir'], array($search, 'searchIndexmenuItems'), $opts, $fsdir);
        }

        $out = '';
        if($_REQUEST['nojs']) {
            $out_tmp = html_buildlist($data, 'idx', array($idxm, "formatIndexmenuItem"), "html_li_index");
            $out .= preg_replace('/<ul class="idx">(.*)<\/ul>/s', "$1", $out_tmp);
        } else {
            $nodes = $idxm->builddTreeNodes($data, '', false);
            $out   = "ajxnodes = [";
            $out .= rtrim($nodes[0], ",");
            $out .= "];";
        }
        return $out;
    }
}