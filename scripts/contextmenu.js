/**
 * Right Context Menu configuration for all users:
 * Menu is built from four array items: title, link, show if page or headpage, show if namespace.
 * Link is not created if it's 0, otherwise it's evaluated.
 * Second array is displayed only in edit mode.
 *
 * Some usefull variables:
 * node.hns = headpage id;
 * node.isdir = node is namespace;
 * node.dokuid = the DW id (namespace parent in case of headpage);
 * id = the DW id of the selected node (headpage id in case of headpage);
 * index.config.urlbase = Url Base;
 * index.config.sepchar = Url separator;
 *
 * To Override the common menu title, add in your user type menu a line like this:
 * indexmenu_contextmenu['all']['pg']['view'][0] = ['customtitle'];
 */
indexmenu_contextmenu['all']['pg'] = {
    'view': [
        ['<span class="indexmenu_titlemenu"><b>Page</b></span>'],
        ['Revisions', 'indexmenu_getid(index.config.urlbase,id)+"do=revisions"'],
        ['Toc preview', '"javascript: indexmenu_createTocMenu(\'call=indexmenu&req=toc&id="+id+"\',\'picker_"+index.obj+"\',\'s"+index.obj+node.id+"\');"']
    ],
    //Menu items in edit mode
    'edit': [
        ['<span class="indexmenu_titlemenu"><b>Edit mode</b></span>'],
        ['Insert as DWlink', '"javascript: indexmenu_insertTags(\'"+id+"\',\'"+index.config.sepchar+"\');"+index.obj+".divdisplay(\'r\',0);"', 'Insert the link of this page in the edit box at cursor position']
    ]
};

indexmenu_contextmenu['all']['ns'] = {
    'view': [
        ['<span class="indexmenu_titlemenu"><b>Namespace</b></span>'],
        ['Search ...', '"javascript: indexmenu_srchpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.isdir+"\',\'"+node.dokuid+"\');"', 'Search for pages within this namespace']
    ]
};

/**
 * Common functions
 * Insert your custom functions avaiable for all users here.
 */

function indexmenu_srchpage(u, s, isdir, nid) {
    var r = prompt("Insert keyword(s) to search for within this namespace", "");
    if (r) {
        var fnid = nid;
        if (isdir == "0") {
            fnid = fnid.substring(0, nid.lastIndexOf(s));
        }
        var b = u, re = new RegExp(s, 'g');
        fnid = fnid.replace(re, ":");
        b += (u.indexOf("?id=") < 0) ? '?id=' : '';
        window.location.href = indexmenu_getid(b, r + " @" + fnid) + "do=search";
    }
}

function indexmenu_getid(u, id) {
    var url = (u || '') + encodeURI(id || '');
    url += (u.indexOf("?") < 0) ? '?' : '&';
    return url;
}

function indexmenu_reqpage(b, s, id, n) {
    var r, u = b;
    if (n) {
        r = id + s + n;
    } else {
        r = prompt("Insert the pagename to create", "");
        if (!r) {
            return;
        }
        r = id + s + r;
    }
    if (r) window.location.href = indexmenu_getid(u, r) + "do=edit";
}
