/**
 * Right Context Menu configuration
 *
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

/**
 * Right Context Menu configuration for all users:
 */
indexmenu_contextmenu['all']['pg'] = {
    'view': [
        ['<span class="indexmenu_titlemenu"><b>'+LANG.plugins.indexmenu.page+'</b></span>'],
        [LANG.plugins.indexmenu.revs, 'indexmenu_getid(index.config.urlbase,id)+"do=revisions"'],
        [LANG.plugins.indexmenu.tocpreview, '"javascript: indexmenu_createTocMenu(\'call=indexmenu&req=toc&id="+id+"\',\'picker_"+index.obj+"\',\'s"+index.obj+node.id+"\');"']
    ],
    //Menu items in edit mode, when previewing
    'edit': [
        ['<span class="indexmenu_titlemenu"><b>'+LANG.plugins.indexmenu.editmode+'</b></span>'],
        [LANG.plugins.indexmenu.insertdwlink, '"javascript: indexmenu_insertTags(\'"+id+"\',\'"+index.config.sepchar+"\');"+index.obj+".divdisplay(\'r\',0);"', LANG.plugins.indexmenu.insertdwlinktooltip]
    ]
};

indexmenu_contextmenu['all']['ns'] = {
    'view': [
        ['<span class="indexmenu_titlemenu"><b>'+LANG.plugins.indexmenu.ns+'</b></span>'],
        [LANG.plugins.indexmenu.search, '"javascript: indexmenu_srchpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.isdir+"\',\'"+node.dokuid+"\');"', LANG.plugins.indexmenu.searchtooltip]
    ]
};


if (JSINFO && JSINFO.isadmin) {
    /**
     * Right Context Menu configuration for admin users:
     */
    indexmenu_contextmenu['pg'] = {
        'view': [
            [LANG.plugins.indexmenu.edit, 'indexmenu_getid(index.config.urlbase,id)+"do=edit"'],
            ['<em>'+LANG.plugins.indexmenu.create+'--></em>', [
                [LANG.plugins.indexmenu.headpage, '"javascript: indexmenu_reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\',\'"+node.name+"\');"', LANG.plugins.indexmenu.headpagetooltip],
                [LANG.plugins.indexmenu.startpage, 'indexmenu_getid(index.config.urlbase,id+index.config.sepchar+"start")+"do=edit"', LANG.plugins.indexmenu.startpagetooltip],
                [LANG.plugins.indexmenu.custompage, '"javascript: indexmenu_reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\');"', LANG.plugins.indexmenu.custompagetooltip]
            ]],
            ['<em>'+LANG.plugins.indexmenu.more+'--></em>', [
                [LANG.plugins.indexmenu.acls, 'indexmenu_getid(index.config.urlbase,id)+"do=admin&page=acl"'],
                [LANG.plugins.indexmenu.purgecache, 'indexmenu_getid(index.config.urlbase,id)+"purge=true"'],
                [LANG.plugins.indexmenu.exporthtml, 'indexmenu_getid(index.config.urlbase,id)+"do=export_xhtml"'],
                [LANG.plugins.indexmenu.exporttext, 'indexmenu_getid(index.config.urlbase,id)+"do=export_raw"']
            ]]
        ]
    };

    indexmenu_contextmenu['ns'] = {
        'view': [
            [LANG.plugins.indexmenu.newpage, '"javascript: indexmenu_reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\');"', LANG.plugins.indexmenu.newpagetooltip],
            ['<em>'+LANG.plugins.indexmenu.more+'--></em>', [
                [LANG.plugins.indexmenu.headpagehere, '"javascript: indexmenu_reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\',\'"+node.name+"\');"', LANG.plugins.indexmenu.headpageheretooltip],
                [LANG.plugins.indexmenu.acls, 'indexmenu_getid(index.config.urlbase,node.dokuid)+"do=admin&page=acl"']
            ]]
        ]
    };

} else if (JSINFO && JSINFO.isauth) {
    /**
     * Right Context Menu configuration for admin users:
     */
    indexmenu_contextmenu['pg'] = {
        'view': [
            [LANG.plugins.indexmenu.newpagehere, '"javascript: indexmenu_reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\');"'],
            [LANG.plugins.indexmenu.edit, 'indexmenu_getid(index.config.urlbase,id)+"do=edit"', 1, 0 ],
            ['<em>'+LANG.plugins.indexmenu.more+'--></em>', [
                [LANG.plugins.indexmenu.headpagehere, '"javascript: indexmenu_reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\',\'"+node.name+"\');"'],
                [LANG.plugins.indexmenu.purgecache, 'indexmenu_getid(index.config.urlbase,id)+"purge=true"'],
                [LANG.plugins.indexmenu.exporthtml, 'indexmenu_getid(index.config.urlbase,id)+"do=export_xhtml"']
            ]]
        ]
    };

}

/**
 * Common functions
 * Insert your custom functions avaiable for all users here.
 */

function indexmenu_srchpage(u, s, isdir, nid) {
    var r = prompt(LANG.plugins.indexmenu.insertkeywords, "");
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
        r = prompt(LANG.plugins.indexmenu.insertpagename, "");
        if (!r) {
            return;
        }
        r = id + s + r;
    }
    if (r) window.location.href = indexmenu_getid(u, r) + "do=edit";
}
