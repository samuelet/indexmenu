<?php
/**
 * Info Indexmenu: Displays the index of a specified namespace. 
 *
 * @license     GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author      Samuele Tognini <samuele@netsons.org>
 * 
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if(!defined('INDEXMENU_IMG_ABSDIR')) define('INDEXMENU_IMG_ABSDIR',DOKU_PLUGIN."indexmenu/images");
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_INC.'inc/search.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_indexmenu_indexmenu extends DokuWiki_Syntax_Plugin {

  var $sort=false;
  var $msort=false;
  var $rsort=false;
  var $nsort=false;
  
  /**
   * return some info
   */
  function getInfo(){
    return array(
		 'author' => 'Samuele Tognini',
		 'email'  => 'samuele@netsons.org',
		 'date'   => rtrim(io_readFile(DOKU_PLUGIN.'indexmenu/VERSION.txt')),
		 'name'   => 'Indexmenu',
		 'desc'   => 'Insert the index of a specified namespace.',
		 'url'    => 'http://wiki.splitbrain.org/plugin:indexmenu'
		 );
  }
  
  /**
   * What kind of syntax are we?
   */
  function getType(){
    return 'substition';
  }

  function getPType(){
    return 'block';
  }
 
  /**
   * Where to sort in?
   */
  function getSort(){
    return 138;
  }
 
  /**
   * Connect pattern to lexer
   */
  function connectTo($mode) {
    $this->Lexer->addSpecialPattern('{{indexmenu>.+?}}',$mode,'plugin_indexmenu_indexmenu');
  }
      
  /**
   * Handle the match
   */
  function handle($match, $state, $pos, &$handler){
    $theme="default";
    $ns=".";
    $level = -1;
    $nons = true;
    $gen_id='random';
    $maxjs=0;
    $max=0;
    $jsajax='';
    $nss=array();
    $match = substr($match,12,-2);
    //split namespace,level,theme
    $match = preg_split('/\|/u', $match, 2);
    //split options
    $opts=preg_split('/ /u',$match[1]);
    //Context option
    $context = in_array('context',$opts);
    //split optional namespaces
    $nss_temp=preg_split("/ /u",$match[0],-1,PREG_SPLIT_NO_EMPTY);
    //Array optional namespace => level
    for ($i = 1; $i < count($nss_temp); $i++) {
      $nsss=preg_split("/#/u",$nss_temp[$i]);
      if (!$context) {
	$nsss[0] = $this->_parse_ns($nsss[0]);
      }
      $nss[]=array($nsss[0],(is_numeric($nsss[1])) ? $nsss[1] : $level);
    }
    //split main requested namespace
    if (preg_match('/(.*)#(\S*)/u',$nss_temp[0],$ns_opt)) {
      //split level
      $ns = $ns_opt[1];
      if (is_numeric($ns_opt[2])) $level=$ns_opt[2];
    } else {
      $ns = $nss_temp[0];
    }
    if (!$context) {
      $ns = $this->_parse_ns($ns);
    }
    //nocookie option (disable for uncached pages)
    $nocookie=$context||in_array('nocookie',$opts);
    //noscroll option
    $noscroll=in_array('noscroll',$opts);
    //Open at current namespace option
    $navbar=in_array('navbar',$opts);
    //no namespaces  options
    $nons = in_array('nons',$opts);
    //no pages option
    $nopg = in_array('nopg',$opts);
    //disable toc preview
    $notoc = in_array('notoc',$opts);
    //Main sort method
    if (in_array('tsort',$opts)) {
      $sort='t';
    } elseif (in_array('dsort',$opts)) {
      $sort='d';
    } else $sort=0;
    //Directory sort
    $nsort=in_array('nsort',$opts);
    //Metadata sort method
    if ($msort = in_array('msort',$opts)) {
      $msort='indexmenu_n';
    } elseif (preg_match('/msort#(\S+)/u',$match[1],$msort_tmp) >0) $msort=str_replace(':',' ',$msort_tmp[1]);
    //reverse sort
    $rsort=in_array('rsort',$opts);
    //javascript option
    if (!$js= in_array('js',$opts)) {
      //split theme
      if (preg_match('/js#(\S*)/u',$match[1],$tmp_theme) > 0) {
	if (is_dir(INDEXMENU_IMG_ABSDIR."/".$tmp_theme[1])) {
	  $theme=$tmp_theme[1];
	}
	$js=true;
      } 
    }
    //id generation method 
    if (preg_match('/id#(\S+)/u',$match[1],$id) >0) $gen_id=$id[1];
    //max option
    if (preg_match('/max#(\d+)($|\s+|#(\d+))/u',$match[1],$maxtmp) >0) {
      $max=$maxtmp[1];
      if ($maxtmp[3]) $jsajax = "&max=".$maxtmp[3];
      //disable cookie to avoid javascript errors
      $nocookie=true;
    }
    if ($sort) $jsajax .= "&sort=".$sort;
    if ($msort) $jsajax .= "&msort=".$msort;
    if ($rsort) $jsajax .= "&rsort=1";
    if ($nsort) $jsajax .= "&nsort=1";
    if ($nopg) $jsajax .= "&nopg=1";
    //max js option
    if (preg_match('/maxjs#(\d+)/u',$match[1],$maxtmp) >0) $maxjs=$maxtmp[1];
    //js options
    $js_opts=compact('theme','gen_id','nocookie','navbar','noscroll','maxjs','notoc','jsajax','context');
    return array($ns,
		 $js_opts,
		 $sort,
		 $msort,
		 $rsort,
		 $nsort,
		 array('level' => $level,
		       'nons' => $nons,
		       'nopg' => $nopg,
		       'nss' => $nss,
		       'max' => $max,
		       'js' => $js,
		       'skip_index' => $this->getConf('skip_index'),
		       'skip_file' => $this->getConf('skip_file'),
		       'headpage' => $this->getConf('headpage'),
		       'hide_headpage' => $this->getConf('hide_headpage')
		       )
		 );
  }  
  
  /**
   * Render output
   */
  function render($mode, &$renderer, $data) {
    global $ACT;
    global $conf;
    global $INFO;
    if($mode == 'xhtml'){
      if ($ACT == 'preview') {
	//Check user permission to display indexmenu in a preview page
	if( $this->getConf('only_admins') &&
	    $conf['useacl'] &&
	    $INFO['perm'] < AUTH_ADMIN)
	  return false;
	//disable cookies
	$data[1]['nocookie']=true;
      }
      //Navbar with nojs
      if ($data[1]['navbar'] && !$data[6]['js']) {
	if (!isset($data[0])) $data[0]='..';
	$data[6]['nss'][]=array(getNS($INFO['id']));
	$renderer->info['cache'] = FALSE;
      }

      if ($data[1]['context']) {
        //resolve current id relative namespaces
	$data[0]=$this->_parse_ns($data[0],$INFO['id']);
	foreach ($data[6]['nss'] as $key=>$value) {
	  $data[6]['nss'][$key][0] = $this->_parse_ns($value[0],$INFO['id']);
	}
	$renderer->info['cache'] = FALSE;
      }
      $n = $this->_indexmenu($data);
      if (!@$n) {
	$n = $this->getConf('empty_msg');
	$n = str_replace('{{ns}}',cleanID($data[0]),$n);
	$n = p_render('xhtml',p_get_instructions($n),$info);
      }
      $renderer->doc .= $n;
      return true;
    } else if ($mode == 'metadata') {
      if (!($data[1]['navbar'] && !$data[6]['js']) && !$data[1]['context']) {
	//this is an indexmenu page that needs the PARSER_CACHE_USE event trigger;
	$renderer->meta['indexmenu'] = TRUE;
      }
      $renderer->doc .= ((empty($data[0])) ? $conf['title'] : nons($data[0])) ." index\n\n";
      unset($renderer->persistent['indexmenu']);
      return true;
    } else {
      return false;
    }
  }
  
  /**
   * Return the index 
   * @author Samuele Tognini <samuele@netsons.org>
   *
   * This function is a simple hack of Dokuwiki html_index($ns)
   * @author Andreas Gohr <andi@splitbrain.org>
   */
  function _indexmenu($myns) {
    global $conf;
    $ns = $myns[0];
    $js_opts=$myns[1];
    $this->sort = $myns[2];
    $this->msort = $myns[3];
    $this->rsort = $myns[4];
    $this->nsort = $myns[5];
    $opts = $myns[6];
    $output=false;
    $data = array();
    $js_name="indexmenu_";
    $fsdir="/".utf8_encodeFN(str_replace(':','/',$ns));
    if ($this->sort || $this->msort || $this->rsort) {
      $custsrch=$this->_search($data,$conf['datadir'],array($this,'_search_index'),$opts,$fsdir);
    } else {
      search($data,$conf['datadir'],array($this,'_search_index'),$opts,$fsdir);
    }
    if (!$data) return false;

    // Id generation method
    if (is_numeric($js_opts['gen_id'])) {
      $js_name .= $js_opts['gen_id'];
    } elseif ($js_opts['gen_id'] == 'ns') {
      $js_name .= sprintf("%u",crc32($ns));
    } else {
      $js_name .= uniqid(rand());
    }

    //javascript index
    if ($opts['js']) {      
      $ns = str_replace('/',':',$ns);
      $output_tmp=$this->_jstree($data,$ns,$js_opts,$js_name,$opts['max']);
      //remove unwanted nodes from standard index 
      $this->_clean_data($data);
    } else {
      $output .= "<script type='text/javascript' charset='utf-8'>\n";
      $output .= "<!--//--><![CDATA[//><!--\n";
      $output .= "indexmenu_nojsqueue.push(new Array('".$js_name."','".utf8_encodeFN($js_opts['jsajax'])."'));\n";
      $output .= "addInitEvent(function(){indexmenu_loadJs(DOKU_BASE+'lib/plugins/indexmenu/nojsindex.js');});\n";
      $output .= "//--><!]]>\n";
      $output .= "</script>\n";
    }
    //Nojs dokuwiki index
    $output.="\n".'<div id="nojs_'.$js_name.'" class="indexmenu_nojs"';
    $output.=">\n";
    $output.=html_buildlist($data,'idx',array($this,"_html_list_index"),"html_li_index");
    $output.="</div>\n";
    $output.=$output_tmp;
    return $output;
  }

  /**
   * Build the browsable index of pages using javascript
   *
   * @author  Samuele Tognini <samuele@netsons.org>
   */
  function _jstree($data,$ns,$js_opts,$js_name,$max) {
    global $conf;
    $hns=false;
    if (empty($data)) return false;
    //Render requested ns as root
    $headpage=$this->getConf('headpage');
    if (empty($ns) && !empty($headpage)) $headpage.=','.$conf['start'];
    $title=$this->_getTitle($ns,$headpage,$hns);
    if (empty($title)) {
      (empty($ns)) ? $title = htmlspecialchars($conf['title'],ENT_QUOTES) : $title=$ns;
    }
    $out = "<script type='text/javascript' charset='utf-8'>\n";
    $out .= "<!--//--><![CDATA[//><!--\n";
    $out .= "var $js_name = new dTree('".$js_name."','".$js_opts['theme']."');\n";
    $sepchar = idfilter(':');
    $out .= "$js_name.config.urlbase='".substr(wl(":"), 0, -1)."';\n";
    $out .= "$js_name.config.sepchar='".$sepchar."';\n";
    if ($js_opts['notoc']) $out .="$js_name.config.toc=false;\n";
    if ($js_opts['nocookie']) $out .="$js_name.config.useCookies=false;\n";
    if ($js_opts['noscroll']) $out .="$js_name.config.scroll=false;\n";
    if ($js_opts['maxjs'] > 0)  $out .= "$js_name.config.maxjs=".$js_opts['maxjs'].";\n";
    if (!empty($js_opts['jsajax'])) $out .= "$js_name.config.jsajax='".utf8_encodeFN($js_opts['jsajax'])."';\n";
    $out .= $js_name.".add('".idfilter(cleanID($ns))."',0,-1,'".$title."'";
    if ($hns) $out .= ",'".idfilter(cleanID($hns))."'";
    $out .= ");\n";
    $anodes = $this->_jsnodes($data,$js_name);
    $out .= $anodes[0];
    $out .= "document.write(".$js_name.");\n";
    $out .= "addInitEvent(function(){".$js_name.".init(";
    $out .= (int) is_file(INDEXMENU_IMG_ABSDIR.'/'.$js_opts['theme'].'/style.css').",";
    $out .= (int) $js_opts['nocookie'].",";
    $out .= '"'.$anodes[1].'",';
    $out .= (int) $js_opts['navbar'].",$max";
    $out .= ");});\n";
    $out .= "//--><!]]>\n";
    $out .= "</script>\n";
    return $out;
  }

  /**
   * Return array of javascript nodes and nodes to open.
   *
   * @author  Samuele Tognini <samuele@netsons.org>
   */
  function _jsnodes($data,$js_name,$noajax=1) {
    if (empty($data)) return false;
    //Array of nodes to check
    $q=array('0');
    //Current open node
    $node=0;
    $out='';
    $extra='';
    if ($noajax) {
      $jscmd=$js_name.".add";
      $com=";\n";
    } else {
      $jscmd="new Array ";
      $com=",";
    }
    foreach ($data as $i=>$item){
      $i++;
      //Remove already processed nodes (greater level = lower level)
      while ($item['level'] <= $data[end($q)-1]['level']) {
	array_pop($q);  
      }

      //till i found its father node
      if ($item['level']==1) {
	//root node
	$father='0';
      } else {
	//Father node
	$father=end($q);
      }
      //add node and its options
      if ($item['type'] == 'd' ) {
	//Search the lowest open node of a tree branch in order to open it.
	if ($item['open']) ($item['level'] < $data[$node]['level']) ? $node=$i : $extra .= "$i ";
	//insert node in last position
	array_push($q,$i);
      }
      $out .= $jscmd."('".idfilter($item['id'])."',$i,".$father.",'".$item['title']."'";
      //hns
      ($item['hns']) ? $out .= ",'".idfilter($item['hns'])."'" : $out .= ",0";
      ($item['type'] == 'd' || $item['type']=='l') ? $out .= ",1" : $out .= ",0";
      //MAX option
      ($item['type']=='l') ? $out .= ",1" : $out .= ",0";
      $out .= ")".$com;
    }
    $extra=rtrim($extra,' ');
    return array($out,$extra);
  }
  /**
   * Get page title, checking for headpages
   *
   * @author  Samuele Tognini <samuele@netsons.org>
   */
  function _getTitle ($ns,$headpage,&$hns) {
    global $conf;
    $hns=false;
    $title=noNS($ns);
    if (empty($headpage)) return $title;
    $ahp=explode(",",$headpage);
    foreach ($ahp as $hp) {
      switch ($hp) {
      case ":inside:":
	$page=$ns.":".noNS($ns);
	break;
      case ":same:":
	$page=$ns;
	break;
	//it's an inside start
      case ":start:":
	$page=ltrim($ns.":".$conf['start'],":");
	break;
	//inside pages
      default:
	$page=$ns.":".$hp;
      }
      //check headpage
      if (@file_exists(wikiFN($page)) && auth_quickaclcheck($page) >= AUTH_READ) {
	if ($conf['useheading'] && $title_tmp=p_get_first_heading($page,FALSE)) $title=$title_tmp;
	$title=htmlspecialchars($title,ENT_QUOTES);
	$hns=$page;
	//headpage found, exit for
	break;
      }
    }
    return $title;
  }

  /**
   * Parse namespace request
   *
   * @author  Samuele Tognini <samuele@netsons.org>
   */
  function _parse_ns ($ns,$id=FALSE) {
    if (!$id) {
      global $ID;
      $id = $ID;
    }
    //Just for old reelases compatibility
    if (empty($ns) || $ns == '..') $ns=":..";
    return resolve_id(getNS($id),$ns);
  }
  
  /**
   * Clean index data from unwanted nodes in nojs mode.
   *
   * @author  Samuele Tognini <samuele@netsons.org>
   */
  function _clean_data(&$data) {
    foreach ($data as $i=>$item) {
      //closed node
      if ($item['type'] == "d" && !$item['open']) {
	$a=$i+1;
	$level=$data[$i]['level'];
	//search and remove every lower and closed nodes
	while ($data[$a]['level'] > $level && !$data[$a]['open']) {
	  unset($data[$a]);
	  $a++;
	}
      }
      $i++;
    }
  }

  /**
   * Build the browsable index of pages
   *
   * $opts['ns'] is the current namespace
   *
   * @author  Andreas Gohr <andi@splitbrain.org>
   * modified by Samuele Tognini <samuele@netsons.org>
   */
  function _search_index(&$data,$base,$file,$type,$lvl,$opts){
    global $conf;
    $hns=false;
    $return=false;
    $isopen=false;
    $skip_index=$opts['skip_index'];
    $skip_file=$opts['skip_file'];
    $headpage=$opts['headpage'];
    $id = pathID($file);
    if($type == 'd'){
      // Skip folders in plugin conf
      if (!empty($skip_index) &&
	  preg_match($skip_index, $id))
	return false;
      //check ACL (for sneaky_index namespaces too).
      if ($this->getConf('sneaky_index') && auth_quickaclcheck($id.':') < AUTH_READ) return false;
      //Open requested level
      if ($opts['level'] > $lvl || $opts['level'] == -1) $isopen=true;
      //Search optional namespaces
      if (!empty($opts['nss'])){
	$nss=$opts['nss'];
	for ($a=0; $a<count($nss);$a++) {
	  if (preg_match("/^".$id."($|:.+)/i",$nss[$a][0],$match)) {
	    //It contains an optional namespace
	    $isopen=true;
	  } elseif (preg_match("/^".$nss[$a][0]."(:.*)/i",$id,$match)) {
	    //It's inside an optional namespace
	    if ($nss[$a][1] == -1 || substr_count($match[1],":") < $nss[$a][1]) {
	      $isopen=true; 
	    } else {
	      $isopen=false;
	    }
	  }
	}
      }
      if ($opts['nons']) {
	return $isopen;
      } elseif ($opts['max'] >0 && !$isopen && $lvl >= $opts['max']) {
	$isopen=false;
	//Stop recursive searching
	$return=false;
	//change type
	$type="l";
      } elseif ($opts['js']) {
	$return=true;
      } else {
	$return=$isopen;
      }
      //Set title and headpage
      $title=$this->_getTitle($id,$headpage,$hns);
      if (!$hns && $opts['nopg']) $hns=$id.":".$conf['start'];
    } else {
      //Nopg.Dont show pages
      if ($opts['nopg']) return false;
      $return=true;
      //Nons.Set all pages at first level
      if ($opts['nons']) $lvl=1;
      //don't add
      if (substr($file,-4) != '.txt') return false;
      //check hiddens and acl
      if (isHiddenPage($id) || auth_quickaclcheck($id) < AUTH_READ) return false;
      //Skip files in plugin conf
      if (!empty($skip_file) &&
	  preg_match($skip_file, $id))
	return false;
      //Skip headpages to hide
      if (!$opts['nons'] && 
	  !empty($headpage) && 
	  $opts['hide_headpage']) {
	if ($id==$conf['start']) return false;
	$ahp=explode(",",$headpage);
	foreach ($ahp as $hp) {
	  switch ($hp) {
	  case ":inside:":
	    if (noNS($id)==noNS(getNS($id)))  return false;
	    break;
	  case ":same:":
	    if (@is_dir(dirname(wikiFN($id))."/".utf8_encodeFN(noNS($id)))) return false;
	    break;
	    //it' s an inside start
	  case ":start:":
	    if (noNS($id)==$conf['start']) return false;
	    break;
	  default:
	    if (noNS($id)==cleanID($hp)) return false;
	  }
	}
      }
      //Set title
      if (!$conf['useheading'] || !$title=p_get_first_heading($id,FALSE)) $title=noNS($id);
      $title=htmlspecialchars($title,ENT_QUOTES);
    }
    
    $item = array( 'id'    => $id,
		   'type'  => $type,
		   'level' => $lvl,
		   'open'  => $isopen, 
		   'title' => $title,
		   'hns'   => $hns,
		   'file' => $file,
		   'return' => $return
		   );
    $item['sort'] = $this->_setorder($item);
    $data[] = $item;
    return $return;
  }


  /**
   * Index item formatter
   *
   * User function for html_buildlist()
   *
   * @author Andreas Gohr <andi@splitbrain.org>
   * modified by Samuele Tognini <samuele@netsons.org>
   */
  function _html_list_index($item){
    $ret = '';
    //namespace
    if($item['type']=='d' || $item['type']=='l'){
      $link=$item['id'];
      $more='idx='.$item['id'];
      //namespace link
      if ($item['hns']) {
	$link=$item['hns'];
	$tagid="indexmenu_idx_head";
	$more='';
      } else {
	//namespace with headpage
	$tagid="indexmenu_idx";
	if ($item['open']) $tagid.=' open';
      }
      $ret .= '<a href="'.wl($link,$more).'" class="'.$tagid.'">';
      $ret .= $item['title'];
      $ret .= '</a>';
    }else{
      //page link
      $ret .= html_wikilink(':'.$item['id']);
    }  
    return $ret;
  }


  /**
   * recurse direcory
   *
   * This function recurses into a given base directory
   * and calls the supplied function for each file and directory
   *
   * @param   array ref $data The results of the search are stored here
   * @param   string    $base Where to start the search
   * @param   callback  $func Callback (function name or arayy with object,method)
   * @param   string    $dir  Current directory beyond $base
   * @param   int       $lvl  Recursion Level
   * @author  Andreas Gohr <andi@splitbrain.org>
   * modified by Samuele Tognini <samuele@netsons.org>
   */
  function _search(&$data,$base,$func,$opts,$dir='',$lvl=1){
    $dirs   = array();
    $files  = array();
    $files_tmp=array();
    $dirs_tmp=array();

    //read in directories and files
    $dh = @opendir($base.'/'.$dir);
    if(!$dh) return;
    while(($file = readdir($dh)) !== false){
      //skip hidden files and upper dirs
      if(preg_match('/^[\._]/',$file)) continue;
      if(is_dir($base.'/'.$dir.'/'.$file)){
	$dirs[] = $dir.'/'.$file;
	continue;
      }
      $files[] = $dir.'/'.$file;
    }
    closedir($dh);
    //Sort dirs
    if ($this->nsort) {
      foreach($dirs as $dir){
	search_callback($func,$dirs_tmp,$base,$dir,'d',$lvl,$opts);
      }
      usort($dirs_tmp,array($this,"_cmp"));
      foreach ($dirs_tmp as $dir) {
	$data[]=$dir;
	if ($dir['return']) $this->_search($data,$base,$func,$opts,$dir['file'],$lvl+1);
      }
    } else {
      sort($dirs);
      foreach($dirs as $dir){
	if (search_callback($func,$data,$base,$dir,'d',$lvl,$opts)) $this->_search($data,$base,$func,$opts,$dir,$lvl+1);
      }
    }
    //Sort files
    foreach($files as $file){
      search_callback($func,$files_tmp,$base,$file,'f',$lvl,$opts);
    }
    usort($files_tmp,array($this,"_cmp"));
    if (empty($dirs) && empty($files_tmp)) {
      $v=end($data);
      if (!$v['hns']) array_pop($data);
    } else {
      $data=array_merge($data,$files_tmp);
    }
    return true;
  }

  /**
   * Sort nodes
   *
   */
  function _cmp($a, $b) {
    if ($this->rsort) {
      return strnatcasecmp($b['sort'], $a['sort']);
    } else {
      return strnatcasecmp($a['sort'], $b['sort']);
    }
  }

  
  /**
   * Add sort information to item.
   *
   * @author  Samuele Tognini <samuele@netsons.org>
   */
  function _setorder($item) {
    $sort=false;
    if ($item['type']=='d') {
      //Fake order info when nsort is not requested
      ($this->nsort) ? $page=$item['hns'] : $sort=0;
    }
    if ($item['type']=='f') $page=$item['id'];
    if ($page) {
      if ($this->msort) $sort=p_get_metadata($page,$this->msort);
      if (!$sort && $this->sort) {
	switch ($this->sort) {
	case 't':
	  $sort=$item['title'];
	  break;
	case 'd':
	  $sort=@filectime(wikiFN($page));
	  break;
	}
      }
    }
    if ($sort===false) $sort=noNS($item['id']);
    return $sort;
  }
} //Indexmenu class end  
