<?php
/**
 * Indexmenu Action Plugin:   Indexmenu Component.
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Samuele Tognini <samuele@netsons.org>
 */
 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
 
class action_plugin_indexmenu extends DokuWiki_Action_Plugin {
 
  /**
   * return some info
   */
  function getInfo(){
    return array(
		 'author' => 'Samuele Tognini',
		 'email'  => 'samuele@netsons.org',
		 'date'   => rtrim(io_readFile(DOKU_PLUGIN.'indexmenu/VERSION.txt')),
		 'name'   => 'Indexmenu (action plugin component)',
		 'desc'   => 'Indexmenu action functions.',
		 'url'    => 'http://wiki.splitbrain.org/plugin:indexmenu',
		 );
  }
    
  /*
   * plugin should use this method to register its handlers with the dokuwiki's event controller
   */
  function register(&$controller) {
    if ($this->getConf('only_admins')) $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE',  $this, '_checkperm');
    if ($this->getConf('page_index') != '') $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, '_loadindex');
    $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_hookjs');
    $controller->register_hook('PARSER_CACHE_USE', 'BEFORE',  $this, '_purgecache');
    if ($this->getConf('show_sort')) $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, '_showsort');
  }

  /**
   * Check if user has permission to insert indexmenu
   *
   * @author Samuele Tognini <samuele@netsons.org>
   */
  function _checkperm(&$event, $param) {
    if ($this->_notadmin()) {
      $event->data[0][1]= preg_replace("/{{indexmenu(|_n)>.+?}}/","",$event->data[0][1]);
    }
  }

  /**
   * Hook js script into page headers.
   *
   * @author Samuele Tognini <samuele@netsons.org>
   */
  function _hookjs(&$event, $param) {
    global $ID;
    global $INFO;
    $jsmenu=DOKU_BASE."lib/plugins/indexmenu/jsmenu/";

    if ($INFO['userinfo']['grps']) {
      $jsmenu .= ($this->_notadmin()) ? "usrmenu.js" : "admmenu.js";
    } else {
      $jsmenu .= "menu.js";
    }

    $event->data["script"][] = array (  "type" => "text/javascript",
				        "charset" => "utf-8",
				        "_data" => "",
				        "src" =>  $jsmenu
				        );
    
    $event->data["script"][] = array (	"type" => "text/javascript",
					"charset" => "utf-8",
					"_data" => "",
					"src" => DOKU_BASE."lib/plugins/indexmenu/indexmenu.js"
					);
 
    $event->data["script"][] = array (	"type" => "text/javascript",
					"charset" => "utf-8",
					"_data" => "var indexmenu_ID='".idfilter($ID)."'"
					);
  }

  /**
   * Check for pages changes and eventually purge cache.
   *
   * @author Samuele Tognini <samuele@netsons.org>
   */
  function _purgecache(&$event, $param) {
    global $ID;
    global $conf;
    $cache = &$event->data;

    if (!isset($cache->page)) return;
    //purge only xhtml cache
    if ($cache->mode != "xhtml") return;
    //Check if it is an indexmenu page
    if (!p_get_metadata($ID,'indexmenu')) return;
    $aclcache=$this->getConf('aclcache');
    if ($conf['useacl']) {
      $newkey=false;
      if ($aclcache == 'user') {
	//Cache per user
	if ($_SERVER['REMOTE_USER']) $newkey=$_SERVER['REMOTE_USER'];
      } else if ($aclcache == 'groups') {
	//Cache per groups
	global $INFO;
	if ($INFO['userinfo']['grps']) $newkey=implode('#',$INFO['userinfo']['grps']);	
      }
      if ($newkey) {
        $cache->key .= "#".$newkey;
	$cache->cache = getCacheName($cache->key, $cache->ext);
      }
    }
    //Check if a page is more recent than purgefile.
    if (@filemtime($cache->cache) < @filemtime($conf['cachedir'].'/purgefile')) {
      $event->preventDefault();
      $event->stopPropagation();
      $event->result = false;
    }
  }

  /**
   * Render a defined page as index.
   *
   * @author Samuele Tognini <samuele@netsons.org>
   */
  function _loadindex(&$event, $param) {
    if ('index' != $event->data) return;
    if (!file_exists(wikiFN($this->getConf('page_index')))) return;
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
   */
  function _showsort(&$event, $param) {
    global $ID,$ACT;
    if ($ACT != 'show' || $this->_notadmin()) return;
    if ($n=p_get_metadata($ID,'indexmenu_n')) {
      ptln('<div class="info">');
      ptln($this->getLang('showsort').$n);
      ptln('</div>');
    }
  }

  /**
   * Check if user is administrator..
   *
   * @author Samuele Tognini <samuele@netsons.org>
   */
  function _notadmin() {
    global $conf;
    global $INFO;

    if ($conf['useacl'] && $INFO['perm'] < AUTH_ADMIN) {
      return true;
    }
    return false;
  }
}
