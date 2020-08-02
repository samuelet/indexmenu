<?php
/**
 * AJAX Backend for indexmenu
 *
 * @author Samuele Tognini <samuele@samuele.netsons.org>
 * @license     GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

//fix for Opera XMLHttpRequests
if(!count($_POST) && @$HTTP_RAW_POST_DATA) {
    parse_str($HTTP_RAW_POST_DATA, $_POST);
}

if(!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/auth.php');
if(!defined('INDEXMENU_IMG_ABSDIR')) define('INDEXMENU_IMG_ABSDIR', DOKU_PLUGIN."indexmenu/images");
//close session
session_write_close();

$ajax_indexmenu = new ajax_indexmenu_plugin;
$ajax_indexmenu->render();

/**
 * Class ajax_indexmenu_plugin
 */
class ajax_indexmenu_plugin {
    /**
     * Output
     *
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     */
    function render() {
        $req  = $_REQUEST['req'];
        $succ = false;
        //send the zip
        if($req == 'send' and isset($_REQUEST['t'])) {
            include(DOKU_PLUGIN.'indexmenu/inc/repo.class.php');
            $repo = new repo_indexmenu_plugin;
            $succ = $repo->send_theme($_REQUEST['t']);
        }
        if($succ) return;

        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        header('Pragma: public');
        switch($req) {
            case 'local':  //required for admin.php
                //list themes
                print $this->local_themes();
                break;
        }
    }

    /**
     * Print a list of local themes
     * TODO: delete this funstion; copy of this function is already in action.php
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     */
    function local_themes() {
        $list   = 'indexmenu,'.DOKU_URL.",lib/plugins/indexmenu/images,";
        $data   = array();
        $handle = @opendir(INDEXMENU_IMG_ABSDIR);
        while(false !== ($file = readdir($handle))) {
            if(is_dir(INDEXMENU_IMG_ABSDIR.'/'.$file)
                && $file != "."
                && $file != ".."
                && $file != "repository"
                && $file != "tmp"
                && $file != ".svn"
            ) {
                $data[] = $file;
            }
        }
        closedir($handle);
        sort($data);
        $list .= implode(",", $data);
        return $list;
    }
}
