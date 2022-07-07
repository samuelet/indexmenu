<?php

namespace dokuwiki\plugin\indexmenu;

class Search
{
    /**
     * @var bool|string sort by t=title, d=date of creation, 0 if not set result default in page sort (was needed for dTree..)
     */
    private $sort;
    /**
     * @var string 'indexmenu_n' or other key from the metadata structure
     */
    private $msort;
    /**
     * @var bool Reverse the sorting of pages, combined with $nsort also the namespaces
     */
    private $rsort;
    /**
     * @var bool also sorts the namespaces
     */
    private $nsort;
    /**
     * @var bool Sort the headpages as defined by global config setting startpage to the top
     */
    private $hsort;

    /**
     * Search constructor.
     *
     * @param array $sort
     *   $sort['sort']
     *   $sort['msort']
     *   $sort['rsort']
     *   $sort['nsort']
     *   $sort['hsort'];
     */
    public function __construct($sort)
    {
        $this->sort = $sort['sort'];
        $this->msort = $sort['msort'];
        $this->rsort = $sort['rsort'];
        $this->nsort = $sort['nsort'];
        $this->hsort = $sort['hsort'];
    }

    /**
     * @param array $data results from search
     * @param bool $isInit true if first level of nodes from tree, next levels false
     * @return array|false
     */
    public function buildFancytreeData($data, $isInit, $currentPage) {
        if(empty($data)) return false;

        $children = [];
        $this->makeNodes($data, -1, 0, $children, $currentPage);

        if($isInit) {
            $nodes['children'] = $children;
            $nodes['debug'] = $data;
            return $nodes;
        } else {
            return $children;
        }


    }

    private function makeNodes(&$data, $indexLatestParsedItem, $previousLevel, &$nodes, $currentPage) {
        $i = 0;
        $counter = 0;
        foreach($data as $i=> $item) {
            if($i <= $indexLatestParsedItem) {
                continue;
            }
            if($item['level'] < $previousLevel || $counter === 0 && $item['level'] == $previousLevel) {
                return $i-1;
            }
            $node = [
                'title' => $item['title'],
                'key' => $item['id'] . ($item['type'] ==='f' ? '' : ':'), //ensure ns is unique
                'hns' => $item['hns']
            ];

            // f=file, d=directory, l=directory which is lazy loaded later
            if($item['type'] == 'f') {
                //set current page to active
                if($currentPage == $item['id']) {
                    $node['active'] = true;
                }
            }
            if($item['type'] !== 'f') { //f/d/l, assumption: if 'd' try always level deeper, maybe not true if d has no items in them by some filter settings?.
                $node['folder'] = true;
                if($item['open'] === true){
                    $node['expanded'] = true;
                }
                if($item['type'] === 'd') {
                    $node['children'] = [];
                    $indexLatestParsedItem = $this->makeNodes($data, $i, $item['level'], $node['children'], $currentPage);
                } else { // 'l'
                    $node['lazy'] = true;
                }
            }
            $nodes[] = $node;
            $previousLevel = $item['level'];
            $counter++;
        }
        return $i;
    }


    /**
     * Search pages/folders depending on the given options $opts
     *
     * @param string $ns
     * @param array $opts
     *  $opts['skipns'] string regexp matching namespaceids to skip
     *  $opts['skipfile']  string regexp matching pageids to skip
     *  $opts['headpage']   string headpages options or pageids
     *  $opts['level']      int    desired depth of main namespace, -1 = all levels
     *  $opts['subnss']     array with entries: array(namespaceid,level) specifying namespaces with their own level
     *  $opts['nons']       bool   exclude namespace nodes
     *  $opts['max']        int    If initially closed, the node at max level will retrieve all its child nodes through the AJAX mechanism
     *  $opts['nopg']       bool   exclude page nodes
     *  $opts['hide_headpage'] int don't hide (0) or hide (1)
     *  $opts['js']         bool   use js-render
     * @return array The results of the search
     */
    public function search($ns, $opts): array
    {
        if($opts['tempNew']) {
            $functionName = 'searchIndexmenuItemsNew'; //NEW: a bit different logic for lazy loading of opened/closed nodes
        } else {
            $functionName = 'searchIndexmenuItems';
        }
        global $conf;
        $dataDir = $conf['datadir'];
        $data = array();
        $fsDir = "/" . utf8_encodeFN(str_replace(':', '/', $ns));
        if ($this->sort || $this->msort || $this->rsort || $this->hsort) {
            $this->customSearch($data, $dataDir, array($this, $functionName), $opts, $fsDir);
        } else {
            search($data, $dataDir, array($this, $functionName), $opts, $fsDir);
        }
        return $data;
    }

    /**
     * Callback that adds an item of namespace/page to the browsable index, if it fits in the specified options
     *

     *
     * @author  Andreas Gohr <andi@splitbrain.org>
     * modified by Samuele Tognini <samuele@samuele.netsons.org>
     *
     * @param array  $data Already collected nodes
     * @param string $base Where to start the search, usually this is $conf['datadir']
     * @param string $file Current file or directory relative to $base
     * @param string $type Type either 'd' for directory or 'f' for file
     * @param int    $lvl  Current recursion depth
     * @param array  $opts Option array as given to search()
     *   $opts['skipns'] string regexp matching namespaceids to skip
     *   $opts['skipfile']  string regexp matching pageids to skip
     *   $opts['headpage']   string headpages options or pageids
     *   $opts['level']      int    desired depth of main namespace, -1 = all levels
     *   $opts['nss']        array with entries: array(namespaceid,level) specifying namespaces with their own level
     *   $opts['nons']       bool   exclude namespace nodes
     *   $opts['max']        int    If initially closed, the node at max level will retrieve all its child nodes through the AJAX mechanism
     *   $opts['nopg']       bool   exclude page nodes
     *   $opts['hide_headpage'] int don't hide (0) or hide (1)
     *   $opts['js']         bool   use js-render
     * @return bool if this directory should be traversed (true) or not (false)
     */
    public function searchIndexmenuItems(&$data, $base, $file, $type, $lvl, $opts) {
        global $conf;
        $hns        = false;
        $isOpen     = false;
        $title      = null;
        $skipns = $opts['skipns'];
        $skipfile  = $opts['skipfile'];
        $headpage   = $opts['headpage'];
        $id         = pathID($file);
        if($type == 'd') {
            // Skip folders in plugin conf
            foreach($skipns as $skipn) {
                if(!empty($skipn) && preg_match($skipn, $id)){
                    return false;
                }
            }
            //check ACL (for sneaky_index namespaces too).
            if($conf['sneaky_index'] && auth_quickaclcheck($id.':') < AUTH_READ) return false;

            //Open requested level
            if($opts['level'] > $lvl || $opts['level'] == -1) {
                $isOpen = true;
            }
            //Search optional subnamespaces with
            if(!empty($opts['subnss'])) {
                $subnss = $opts['subnss'];
                for($a = 0; $a < count($subnss); $a++) {
                    if(preg_match("/^".$id."($|:.+)/i", $subnss[$a][0], $match)) {
                        //It contains a subnamespace
                        $isOpen = true;
                    } elseif(preg_match("/^".$subnss[$a][0]."(:.*)/i", $id, $match)) {
                        //It's inside a subnamespace, check level
                        if($subnss[$a][1] == -1 || substr_count($match[1], ":") < $subnss[$a][1]) {
                            $isOpen = true;
                        } else {
                            $isOpen = false;
                        }
                    }
                }
            }
            if($opts['nons']) {
                return $isOpen;
            } elseif($opts['max'] > 0 && !$isOpen && $lvl >= $opts['max']) {
                $isOpen = false;
                //Stop recursive searching
                $shouldBeTraversed = false;
                //change type
                $type = "l";
            } elseif($opts['js']) {
                $shouldBeTraversed = true; //TODO if js tree, then traverse deeper???
            } else {
                $shouldBeTraversed = $isOpen;
            }
            //Set title and headpage
            $title = $this->getNamespaceTitle($id, $headpage, $hns);
            //link namespace nodes to start pages when excluding page nodes
            if(!$hns && $opts['nopg']) {
                $hns = $id.":".$conf['start'];
            }
        } else {
            //Nopg. Dont show pages
            if($opts['nopg']) return false;

            $shouldBeTraversed = true;
            //Nons.Set all pages at first level
            if($opts['nons']) {
                $lvl = 1;
            }
            //don't add
            if(substr($file, -4) != '.txt') return false;
            //check hiddens and acl
            if(isHiddenPage($id) || auth_quickaclcheck($id) < AUTH_READ) return false;
            //Skip files in plugin conf
            foreach($skipfile as $skipf) {
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
            'open'   => $isOpen,
            'title'  => $title,
            'hns'    => $hns,
            'file'   => $file,
            'shouldBeTraversed' => $shouldBeTraversed
        );
        $item['sort'] = $this->getSortValue($item);
        $data[]       = $item;
        return $shouldBeTraversed;
    }

    /**
     * Callback that adds an item of namespace/page to the browsable index, if it fits in the specified options
     *

     * testing version, for debuggin/fixing lazyloading...


     * @author  Andreas Gohr <andi@splitbrain.org>
     * modified by Samuele Tognini <samuele@samuele.netsons.org>
     *
     * @param array  $data Already collected nodes
     * @param string $base Where to start the search, usually this is $conf['datadir']
     * @param string $file Current file or directory relative to $base
     * @param string $type Type either 'd' for directory or 'f' for file
     * @param int    $lvl  Current recursion depth
     * @param array  $opts Option array as given to search()
     *   $opts['skipns'] string regexp matching namespaceids to skip
     *   $opts['skipfile']  string regexp matching pageids to skip
     *   $opts['headpage']   string headpages options or pageids
     *   $opts['level']      int    desired depth of main namespace, -1 = all levels
     *   $opts['nss']        array with entries: array(namespaceid,level) specifying namespaces with their own level
     *   $opts['nons']       bool   exclude namespace nodes
     *   $opts['max']        int    If initially closed, the node at max level will retrieve all its child nodes through the AJAX mechanism
     *   $opts['nopg']       bool   exclude page nodes
     *   $opts['hide_headpage'] int don't hide (0) or hide (1)
     *   $opts['js']         bool   use js-render
     * @return bool if this directory should be traversed (true) or not (false)
     */
    public function searchIndexmenuItemsNew(&$data, $base, $file, $type, $lvl, $opts) {
        global $conf;
        $hns        = false;
        $isOpen     = false;
        $title      = null;
        $skipns = $opts['skipns'];
        $skipfile  = $opts['skipfile'];
        $headpage   = $opts['headpage'];
        $id         = pathID($file);

        if($type == 'd') {
            // Skip folders in plugin conf
            foreach($skipns as $skipn) {
                if(!empty($skipn) && preg_match($skipn, $id)){
                    return false;
                }
            }
            //check ACL (for sneaky_index namespaces too).
            if($conf['sneaky_index'] && auth_quickaclcheck($id.':') < AUTH_READ) return false;

            //Open requested level
            if($opts['level'] > $lvl || $opts['level'] == -1) {
                $isOpen = true;
            }

            //Search optional subnamespaces with
            if(!empty($opts['subnss'])) {
                $subnss = $opts['subnss'];

                for($a = 0; $a < count($subnss); $a++) {
                    if(preg_match("/^".$id."($|:.+)/i", $subnss[$a][0], $match)) {
                        //It contains a subnamespace
                        $isOpen = true;
                    } elseif(preg_match("/^".$subnss[$a][0]."(:.*)/i", $id, $match)) {
                        //It's inside a subnamespace, check level
                        if($subnss[$a][1] == -1 || substr_count($match[1], ":") < $subnss[$a][1]) {
                            $isOpen = true;
                        } else {
                            $isOpen = false;
                        }
                    }
                }
            }
            if($opts['nons']) {
                return $isOpen;
            } elseif($opts['max'] > 0 && !$isOpen) {
                // limited levels per request, node is closed
                if($lvl >= $opts['max']) { //
                    //change type, more nodes should be loaded by ajax
                    $type = "l";
                    $shouldBeTraversed = false;
                } else {
                    //node is closed, but still more levels requested with max
                    $shouldBeTraversed = true;
                }
            } else {
                $shouldBeTraversed = $isOpen;
            }
            //Set title and headpage
            $title = $this->getNamespaceTitle($id, $headpage, $hns);
            //link namespace nodes to start pages when excluding page nodes
            if(!$hns && $opts['nopg']) {
                $hns = $id.":".$conf['start'];
            }
        } else {
            //Nopg.Dont show pages
            if($opts['nopg']) return false;

            $shouldBeTraversed = true;
            //Nons.Set all pages at first level
            if($opts['nons']) {
                $lvl = 1;
            }
            //don't add
            if(substr($file, -4) != '.txt') return false;
            //check hiddens and acl
            if(isHiddenPage($id) || auth_quickaclcheck($id) < AUTH_READ) return false;
            //Skip files in plugin conf
            foreach($skipfile as $skipf) {
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
            'open'   => $isOpen,
            'title'  => $title,
            'hns'    => $hns,
            'file'   => $file,
            'shouldBeTraversed' => $shouldBeTraversed
        );
        $item['sort'] = $this->getSortValue($item);
        $data[]       = $item;
        return $shouldBeTraversed;
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
            usort($dirs_tmp, array($this, "compareNodes"));
            //add and search each directory
            foreach($dirs_tmp as $dir) {
                $data[] = $dir;
                if($dir['shouldBeTraversed']) {
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
        usort($files_tmp, array($this, "compareNodes"));

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
     * @param string $headpage comma-separated headpages options and headpages
     * @param string $hns reference pageid of headpage, false when not existing
     * @return string when headpage & heading on: title of headpage, otherwise: namespace name
     */
    public function getNamespaceTitle($ns, $headpage, &$hns) {
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
    private function compareNodes($a, $b) {
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
    private function getSortValue($item) {
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
