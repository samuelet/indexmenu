<?php
/**
 * Indexmenu Action Plugin:   Indexmenu Component.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Samuele Tognini <samuele@samuele.netsons.org>
 */

if(!defined('DOKU_INC')) die();

class action_plugin_indexmenu extends DokuWiki_Action_Plugin {

    /**
     * plugin should use this method to register its handlers with the dokuwiki's event controller
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object.
     */
    function register(Doku_Event_Handler $controller) {
        if($this->getConf('only_admins')) $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE', $this, '_checkperm');
        if($this->getConf('page_index') != '') $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, '_loadindex');
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, '_extendJSINFO');
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, '_purgecache');
        if($this->getConf('show_sort')) $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, '_showsort');
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, '_ajax_call');
    }

    /**
     * Check if user has permission to insert indexmenu
     *
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     *
     * @param Doku_Event $event
     * @param mixed      $param not defined
     */
    function _checkperm(&$event, $param) {
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
    function _extendJSINFO(&$event, $param) {
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
    function _purgecache(&$event, $param) {
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
    function _loadindex(&$event, $param) {
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
    function _showsort(&$event, $param) {
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
    function _ajax_call(&$event, $param) {
        if($event->data !== 'indexmenu') {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        switch($_REQUEST['req']) {
            case 'local':
                //list themes
                header('Content-Type: application/json');

                $data = $this->_getlocalThemes();

                require_once DOKU_INC.'inc/JSON.php';
                $json = new JSON();
                echo ''.$json->encode($data).'';
                break;

            case 'toc':
                //print toc preview
                if(isset($_REQUEST['id'])) print $this->print_toc($_REQUEST['id']);
                break;

            case 'index':
                //print index
                if(isset($_REQUEST['idx'])) print $this->print_index($_REQUEST['idx']);
                break;
        }

    }

    /**
     * Print a list of local themes
     *
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     * @author Gerrit Uitslag <klapinklapin@gmail.com>
     */
    private function _getlocalThemes() {
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

        return array(
            'themebase' => $themebase,
            'themes'    => $themes
        );

    }

    /**
     * Print a toc preview
     *
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function print_toc($id) {
        require_once(DOKU_INC.'inc/parser/xhtml.php');
        $id = cleanID($id);
        if(auth_quickaclcheck($id) < AUTH_READ) return '';

        $meta = p_get_metadata($id);
        $toc  = $meta['description']['tableofcontents'];

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
     * @author Andreas Gohr <andi@splitbrain.org>
     * @author Gerrit Uitslag <klapinklapin@gmail.com>
     */
    function render_toc($toc) {
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
     */
    function render_abstract($id, &$meta) {
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
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     * @author Andreas Gohr <andi@splitbrain.org>
     * @author Rene Hadler <rene.hadler@iteas.at>
     */
    function print_index($ns) {
        require_once(DOKU_PLUGIN.'indexmenu/syntax/indexmenu.php');
        global $conf;
        $idxm     = new syntax_plugin_indexmenu_indexmenu();
        $ns       = $idxm->_parse_ns(rawurldecode($ns));
        $level    = -1;
        $max      = 0;
        $data     = array();
        $skipfile = array();
        $skipns   = array();

        if($_REQUEST['max'] > 0) {
            $max   = $_REQUEST['max'];
            $level = $max;
        }
        $nss         = ($_REQUEST['nss']) ? cleanID($_REQUEST['nss']) : '';
        $idxm->sort  = $_REQUEST['sort'];
        $idxm->msort = $_REQUEST['msort'];
        $idxm->rsort = $_REQUEST['rsort'];
        $idxm->nsort = $_REQUEST['nsort'];
        $idxm->hsort = $_REQUEST['hsort'];
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
            'nons'          => $_REQUEST['nons'],
            'nss'           => array(array($nss, 1)),
            'max'           => $max,
            'js'            => false,
            'nopg'          => $_REQUEST['nopg'],
            'skip_index'    => $skipns,
            'skip_file'     => $skipfile,
            'headpage'      => $idxm->getConf('headpage'),
            'hide_headpage' => $idxm->getConf('hide_headpage')
        );
        if($idxm->sort || $idxm->msort || $idxm->rsort || $idxm->hsort) {
            $idxm->_search($data, $conf['datadir'], array($idxm, '_search_index'), $opts, $fsdir);
        } else {
            search($data, $conf['datadir'], array($idxm, '_search_index'), $opts, $fsdir);
        }

        $out = '';
        if($_REQUEST['nojs']) {
            require_once(DOKU_INC.'inc/html.php');
            $out_tmp = html_buildlist($data, 'idx', array($idxm, "_html_list_index"), "html_li_index");
            $out .= preg_replace('/<ul class="idx">(.*)<\/ul>/s', "$1", $out_tmp);
        } else {
            $nodes = $idxm->_jsnodes($data, '', 0);
            $out   = "ajxnodes = [";
            $out .= rtrim($nodes[0], ",");
            $out .= "];";
        }
        return $out;
    }
}
