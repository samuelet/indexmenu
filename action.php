<?php
/**
 * Indexmenu Action Plugin:   Indexmenu Component.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Samuele Tognini <samuele@netsons.org>
 */

if(!defined('DOKU_INC')) die();

class action_plugin_indexmenu extends DokuWiki_Action_Plugin {

    /**
     * plugin should use this method to register its handlers with the dokuwiki's event controller
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object.
     */
    function register(&$controller) {
        if($this->getConf('only_admins')) $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE', $this, '_checkperm');
        if($this->getConf('page_index') != '') $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, '_loadindex');
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER',  $this, '_extendJSINFO');
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, '_purgecache');
        if($this->getConf('show_sort')) $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, '_showsort');
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, '_ajax_call');
    }

    /**
     * Check if user has permission to insert indexmenu
     *
     * @author Samuele Tognini <samuele@netsons.org>
     *
     * @param Doku_Event $event
     * @param mixed $param not defined
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
     * @author Samuele Tognini <samuele@netsons.org>
     * @author Gerrit Uitslag <klapinklapin@gmail.com>
     *
     * @param Doku_Event $event
     * @param mixed $param not defined
     */
    function _extendJSINFO(&$event, $param) {
        global $INFO, $JSINFO;
        $JSINFO['isadmin'] = (int) $INFO['isadmin'];
        $JSINFO['isauth'] = (int) $INFO['userinfo'];
    }

    /**
     * Check for pages changes and eventually purge cache.
     *
     * @author Samuele Tognini <samuele@netsons.org>
     *
     * @param Doku_Event $event
     * @param mixed $param not defined
     */
    function _purgecache(&$event, $param) {
        global $ID;
        global $conf;
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
     * @author Samuele Tognini <samuele@netsons.org>
     *
     * @param Doku_Event $event
     * @param mixed $param not defined
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
     * @author Samuele Tognini <samuele@netsons.org>
     *
     * @param Doku_Event $event
     * @param mixed $param not defined
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
     * Print a list of local themes
     *
     * @author Samuele Tognini <samuele@netsons.org>
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
     * Handles ajax requests for indexmenu
     *
     * @param Doku_Event $event
     * @param mixed $param not defined
     */
    function _ajax_call(&$event, $param) {
        if($event->data !== 'indexmenu') {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        global $INPUT;

        $data = array();
        switch($INPUT->str('req')) {
            case 'local':
                //list themes
                $data = $this->_getlocalThemes();

                break;
         /* case 'toc':
                //print toc preview
                if(isset($_REQUEST['id'])) print $this->print_toc($_REQUEST['id']);
                break;
            case 'index':
                //print index
                if(isset($_REQUEST['idx'])) print $this->print_index($_REQUEST['idx']);
                break;        */
        }

        require_once DOKU_INC.'inc/JSON.php';
        $json = new JSON();
        header('Content-Type: application/json');
        echo ''.$json->encode($data).'';
    }
}

