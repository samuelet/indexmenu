<?php

namespace dokuwiki\plugin\indexmenu;

class Search
{
    /**
     * @var bool|string sort by t=title, d=date of creation
     */
    var $sort = false;
    var $msort = false;
    var $rsort = false;
    var $nsort = false;
    var $hsort = false;

    public function __construct($sort, $msort,$rsort,$nsort,$hsort)
    {
        $this->sort = $sort;
        $this->msort = $msort;
        $this->rsort = $rsort;
        $this->nsort = $nsort;
        $this->hsort = $hsort;
    }

    public function buildFancytreeData($data) {
        if(empty($data)) return false;

        $nodes['children'] = [];
        $this->makeNodes($data, -1, 0, $nodes['children'] );
        $nodes['contextmenu'] = false;
        return $nodes;
    }

    private function makeNodes(&$data, $indexLatestParsedItem, $previouslevel, &$nodes) {
        $i = 0;
        $counter = 0;
        foreach($data as $i=> $item) {
            if($i <= $indexLatestParsedItem) {
                continue;
            }
            if($item['level'] < $previouslevel || $counter === 0 && $item['level'] == $previouslevel) {
                return $i-1;
            }
            $node = [
                'title' => $item['title'],
                'key' => $item['id'] . ($item['type'] ==='d' ? ':' : ''), //ensure ns is unique
                'hns' => $item['hns']
            ];

            if($item['type'] === 'd') { //assumption: if 'd' try always level deeper, maybe not true if d has no items in them by some filter settings?.
                $node['folder'] = true;
                $node['children'] = [];
                $indexLatestParsedItem = $this->makeNodes($data, $i, $item['level'], $node['children'] );
            }
            $nodes[] = $node;
            $previouslevel = $item['level'];
            $counter++;
        }
        return $i;
    }


    /**
     *     //$data = $this->search($ns, $conf['datadir'], $opts);
     *
     * @param $ns
     * @param $datadir
     * @param $opts
     * @return array
     */
    public function search($ns, $datadir, $opts): array
    {
        $data = array();
        $fsdir = "/" . utf8_encodeFN(str_replace(':', '/', $ns));
        if ($this->sort || $this->msort || $this->rsort || $this->hsort) {
            $this->customSearch($data, $datadir, array($this, '_search_index'), $opts, $fsdir);
        } else {
            search($data, $datadir, array($this, '_search_index'), $opts, $fsdir);
        }
        return $data;
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
                if(!empty($skipi) && preg_match($skipi, $id)){
                    return false;
                }
            }
            //check ACL (for sneaky_index namespaces too).
            if($conf['sneaky_index'] && auth_quickaclcheck($id.':') < AUTH_READ) return false;

            //Open requested level
            if($opts['level'] > $lvl || $opts['level'] == -1) {
                $isopen = true;
            }
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
            if(!$hns && $opts['nopg']) {
                $hns = $id.":".$conf['start'];
            }
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
                $title = p_get_first_heading($id, false);
            }
            if(is_null($title)) {
                $title = noNS($id);
            }
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
    public function customSearch(&$data, $base, $func, $opts, $dir = '', $lvl = 1) {
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
                    $this->customSearch($data, $base, $func, $opts, $dir['file'], $lvl + 1);
                }
            }
        } else {
            //sort by page name
            sort($dirs);
            //collect directories
            foreach($dirs as $dir) {
                if(call_user_func_array($func, array(&$data, $base, $dir, 'd', $lvl, $opts))) {
                    $this->customSearch($data, $base, $func, $opts, $dir, $lvl + 1);
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
            if(!$v['hns']) {
                array_pop($data);
            }
        } else {
            //add files to index
            $data = array_merge($data, $files_tmp);
        }
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
    public function _getTitle($ns, $headpage, &$hns) {
        global $conf;
        $hns   = false;
        $title = noNS($ns);
        if(empty($headpage)) {
            return $title;
        }
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
                    $title_tmp = p_get_first_heading($page, false);
                    if(!is_null($title_tmp)) {
                        $title = $title_tmp;
                    }
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
            if($this->nsort) {
                $page = $item['hns'];
            } else {
                $sort = 0;
            }
        }
        if($item['type'] == 'f') {
            $page = $item['id'];
        }
        if($page) {
            if($this->hsort && noNS($item['id']) == $conf['start']) {
                $sort = 1;
            }
            if($this->msort) {
                $sort = p_get_metadata($page, $this->msort);
            }
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
        if($sort === false) {
            $sort = noNS($item['id']);
        }
        return $sort;
    }

}
