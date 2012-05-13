<?php
/**
 * Default configuration for indexmenu plugin
 *
 * @license:    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author:     Samuele Tognini <samuele@netsons.org>
 */
$conf['only_admins']		=	0;
$conf['aclcache']     		=	'groups';
$conf['headpage']		=	':start:,:same:,:inside:';
$conf['hide_headpage']  	=	1;
$conf['page_index']             =       '';
$conf['empty_msg']		=	'';
$conf['skip_index']		=	'';
$conf['skip_file']		=	'';
$conf['show_sort']	        =	true;
$conf['themes_url']             =       'http://samuele.netsons.org/dokuwiki';
$conf['be_repo']	        =	false;
$conf['sneaky_index']	        =       (isset($GLOBALS['conf']['sneaky_index'])) ? $GLOBALS['conf']['sneaky_index'] : true;