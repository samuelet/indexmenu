/**
 * Right Context Menu configuration
 *
 * Some usefull variables:
 *   node.hns = headpage id;
 *   node.isdir = node is namespace;
 *   node.dokuid = the DW id (namespace parent in case of headpage);
 *   id = the DW id of the selected node (headpage id in case of headpage);
 *   index.config.urlbase = Url Base;
 *   index.config.sepchar = Url separator;
 *
 * HOWTO EDIT:
 *
 * To override menu entries or add a menu entry:
 *  - PLEASE EDIT ONLY the scripts/contextmenu.local.js file
 *  - DON'T EDIT this file, it is overwritten at plugin update
 *
 * Base structure of the context menu is displayed below.
 * The entries with 'pg' are shown for page noded, these with 'ns' only for namespaces.
 *
 * Current available for everybody:
 *   indexmenu_contextmenu['all']['pg']['view'] = [...array with menu description here... ];
 *   indexmenu_contextmenu['all']['pg']['edit'] = [ ... ];
 *   indexmenu_contextmenu['all']['ns']['view'] = [ ... ];
 *
 * Current available for admins:
 *   indexmenu_contextmenu['pg']['view'] = [ ... ];
 *   indexmenu_contextmenu['ns']['view'] = [ ... ];
 *
 * Current available for authenticated users:
 *   indexmenu_contextmenu['pg']['view'] = [ ... ];
 *   indexmenu_contextmenu['ns']['view'] = [ ... ];
 *
 * A menu description may contain four kind of entries:
 *  - section title: array with one entry e.g.:
 *      ['Section title (html allowed)']
 *  - menu action: array with two entries e.g.:
 *      ['Title of action 1 (html allowed)', 'javascript here ... see for examples scripts/contextmenu.js']
 *  - menu action with custom tooltip: array with three entries e.g.:
 *      ['Title of action 1 (html allowed)', 'javascript here ... see for examples scripts/contextmenu.js', 'Customized title']
 *  - submenu: array with two entries where second entry is an array that describes again a menu e.g.:
 *      ['title of submenu (html allowed)', [ ...array with menu actions... ]]
 *
 *
 *  Examples:
 *  A menu description array:
 *   ... = [
 *           ['section title'],
 *           ['title of action 1', 'javascript here'],
 *           ['title of submenu', [['title of subaction 1', 'javascript here'], ['title of subaction 1', 'javascript here', 'Click here for action']] ]
 *         ];
 *
 * To Override the common menu title:
 *  indexmenu_contextmenu['all']['pg']['view'][0] = ['customtitle'];
 *
 * To override a menu entry, for example the menu title:
 *   indexmenu_contextmenu['all']['pg']['view'][0] = ['Custom Title'];
 *
 * To add option to page menu:
 *   Array.splice(index, howManyToRemove, description1)
 *     index = position to start (start counting at zero)
 *     howManyToRemove = number of elements that are removed (set to 1 to replace a element)
 *     description1 = array with menu entry description
 *     -> optional: description2 = optional you can add more elements at once by splice(index, howManyToRemove, description1, description2, etc)
 *
 *   indexmenu_contextmenu['all']['pg']['view'].splice(1, 0, ['Input new page', '"javascript: IndexmenuContextmenu.reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\');"']);
 */

// IMPORTANT: DON'T MODIFY THIS FILE, BUT EDIT contextmenu.local.js PLEASE!
// THIS FILE IS OVERWRITTEN WHEN PLUGIN IS UPDATED

/**
 * Right Context Menu configuration for all users:
 */
indexmenu_contextmenu['all']['pg'] = {
    'view': [
        ['<span class="indexmenu_titlemenu"><b>'+LANG.plugins.indexmenu.page+'</b></span>'],
        [LANG.plugins.indexmenu.revs, 'IndexmenuContextmenu.getid(index.config.urlbase,id)+"do=revisions"'],
        [LANG.plugins.indexmenu.tocpreview, '"javascript: IndexmenuContextmenu.createTocMenu(\'call=indexmenu&req=toc&id="+id+"\',\'picker_"+index.obj+"\',\'s"+index.obj+node.id+"\');"']
    ],
    //Menu items in edit mode, when previewing
    'edit': [
        ['<span class="indexmenu_titlemenu"><b>'+LANG.plugins.indexmenu.editmode+'</b></span>'],
        [LANG.plugins.indexmenu.insertdwlink, '"javascript: IndexmenuContextmenu.insertTags(\'"+id+"\',\'"+index.config.sepchar+"\');"+index.obj+".divdisplay(\'r\',0);"', LANG.plugins.indexmenu.insertdwlinktooltip]
    ]
};

indexmenu_contextmenu['all']['ns'] = {
    'view': [
        ['<span class="indexmenu_titlemenu"><b>'+LANG.plugins.indexmenu.ns+'</b></span>'],
        [LANG.plugins.indexmenu.search, '"javascript: IndexmenuContextmenu.srchpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.isdir+"\',\'"+node.dokuid+"\');"', LANG.plugins.indexmenu.searchtooltip]
    ]
};


if (JSINFO && JSINFO.isadmin) {
    /**
     * Right Context Menu configuration for admin users:
     */
    indexmenu_contextmenu['pg'] = {
        'view': [
            [LANG.plugins.indexmenu.edit, 'IndexmenuContextmenu.getid(index.config.urlbase,id)+"do=edit"'],
            ['<em>'+LANG.plugins.indexmenu.create+'--></em>', [
                [LANG.plugins.indexmenu.headpage, '"javascript: IndexmenuContextmenu.reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\',\'"+node.name+"\');"', LANG.plugins.indexmenu.headpagetooltip],
                [LANG.plugins.indexmenu.startpage, 'IndexmenuContextmenu.getid(index.config.urlbase,id+index.config.sepchar+"start")+"do=edit"', LANG.plugins.indexmenu.startpagetooltip],
                [LANG.plugins.indexmenu.custompage, '"javascript: IndexmenuContextmenu.reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\');"', LANG.plugins.indexmenu.custompagetooltip]
            ]],
            ['<em>'+LANG.plugins.indexmenu.more+'--></em>', [
                [LANG.plugins.indexmenu.acls, 'IndexmenuContextmenu.getid(index.config.urlbase,id)+"do=admin&page=acl"'],
                [LANG.plugins.indexmenu.purgecache, 'IndexmenuContextmenu.getid(index.config.urlbase,id)+"purge=true"'],
                [LANG.plugins.indexmenu.exporthtml, 'IndexmenuContextmenu.getid(index.config.urlbase,id)+"do=export_xhtml"'],
                [LANG.plugins.indexmenu.exporttext, 'IndexmenuContextmenu.getid(index.config.urlbase,id)+"do=export_raw"']
            ]]
        ]
    };

    indexmenu_contextmenu['ns'] = {
        'view': [
            [LANG.plugins.indexmenu.newpage, '"javascript: IndexmenuContextmenu.reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\');"', LANG.plugins.indexmenu.newpagetooltip],
            ['<em>'+LANG.plugins.indexmenu.more+'--></em>', [
                [LANG.plugins.indexmenu.headpagehere, '"javascript: IndexmenuContextmenu.reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\',\'"+node.name+"\');"', LANG.plugins.indexmenu.headpageheretooltip],
                [LANG.plugins.indexmenu.acls, 'IndexmenuContextmenu.getid(index.config.urlbase,node.dokuid)+"do=admin&page=acl"']
            ]]
        ]
    };

} else if (JSINFO && JSINFO.isauth) {
    /**
     * Right Context Menu configuration for authenticated users:
     */
    indexmenu_contextmenu['pg'] = {
        'view': [
            [LANG.plugins.indexmenu.newpagehere, '"javascript: IndexmenuContextmenu.reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\');"'],
            ['Edit', 'IndexmenuContextmenu.getid(index.config.urlbase,id)+"do=edit"', 1, 0 ],
            ['<em>'+LANG.plugins.indexmenu.more+'--></em>', [
                [LANG.plugins.indexmenu.headpagehere, '"javascript: IndexmenuContextmenu.reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\',\'"+node.name+"\');"'],
                [LANG.plugins.indexmenu.purgecache, 'IndexmenuContextmenu.getid(index.config.urlbase,id)+"purge=true"'],
                [LANG.plugins.indexmenu.exporthtml, 'IndexmenuContextmenu.getid(index.config.urlbase,id)+"do=export_xhtml"']
            ]]
        ]
    };

}

var IndexmenuContextmenu = {

    /**
     * Common functions
     * Insert your custom functions (available for all users) here.
     */

    srchpage: function (u, s, isdir, nid) {
        var r = prompt(LANG.plugins.indexmenu.insertkeywords, "");
        if (r) {
            var fnid = nid;
            if (isdir == "0") {
                fnid = fnid.substring(0, nid.lastIndexOf(s));
            }
            var b = u, re = new RegExp(s, 'g');
            fnid = fnid.replace(re, ":");
            b += (u.indexOf("?id=") < 0) ? '?id=' : '';
            window.location.href = IndexmenuContextmenu.getid(b, r + " @" + fnid) + "do=search";
        }
    },

    getid: function (u, id) {
        var url = (u || '') + encodeURI(id || '');
        url += (u.indexOf("?") < 0) ? '?' : '&';
        return url;
    },

    reqpage: function (b, s, id, n) {
        var r;
        if (n) {
            r = id + s + n;
        } else {
            r = prompt(LANG.plugins.indexmenu.insertpagename, "");
            if (!r) {
                return;
            }
            r = id + s + r;
        }
        if (r) window.location.href = IndexmenuContextmenu.getid(b, r) + "do=edit";
    },

    insertTags: function (lnk, sep) {
        var r, l = lnk;
        if (sep) {
            r = new RegExp(sep, "g");
            l = lnk.replace(r, ':');
        }
        insertTags('wiki__text', '[[', ']]', l);
    },

    /**
     * Create or catch the picker and hide it, next call the ajax content loading to get the ToC
     *
     * @param {string} get    query string
     * @param {string} picker id of picker
     * @param {string} btn    id of button
     */
    createTocMenu: function (get, picker, btn) {
        var $toc_picker = jQuery('#' + picker);
        if (!$toc_picker.length) {
            $toc_picker = IndexmenuUtils.createPicker(picker, 'indexmenu_toc');
            $toc_picker
                .html('<a href="#"><img src="' + DOKU_BASE + 'lib/plugins/indexmenu/images/close.gif" class="indexmenu_close" /></a><div />')
                .children().first().click(function (event) {
                    event.stopPropagation();
                    return IndexmenuContextmenu.togglePicker($toc_picker, jQuery('#' + btn));
                });
        } else {
            $toc_picker.hide();
        }
        IndexmenuContextmenu.ajaxmenu(get, $toc_picker, jQuery('#' + btn), $toc_picker.children().last(), null);
    },

    /**
     * Shows the picker and adds to it or to an internal containter the ajax content
     *
     * @param {string}   get        query string
     * @param {jQuery}   $picker
     * @param {jQuery}   $btn
     * @param {jQuery}   $container if defined ajax result is added to it, otherwise to $picker
     * @param {function} oncomplete called when defined to handle ajax result
     */
    ajaxmenu: function (get, $picker, $btn, $container, oncomplete) {
        var $indx_list;
        $indx_list = $container || $picker;

        if (!IndexmenuContextmenu.togglePicker($picker, $btn)) return;

        var onComplete = function (data) {
            $indx_list.html('');
            if (typeof oncomplete == 'function') {
                oncomplete(data, $indx_list);
            } else {
                $indx_list.html(data);
            }
        };

        //get content for picker/container
        jQuery.ajax({
            type: "POST",
            url: DOKU_BASE + 'lib/exe/ajax.php',
            data: get,
            beforeSend: function () {
                $indx_list.html('<div class="tocheader">'+LANG.plugins.indexmenu.loading+'</div>');
            },
            success: onComplete,
            dataType: 'html'
        });
    },


    /**
     * Hide/show picker, will be shown beside btn
     *
     * @param {string|jQuery} $picker
     * @param {jQuery}        $btn
     * @return {Boolean} true if open, false closed
     */
    togglePicker: function ($picker, $btn) {
        var x = 8, y = 0;

        if (!$picker.is(':visible')) {
            var pos = $btn.offset();
            //position + width of button
            x += pos.left + $btn[0].offsetWidth;
            y += pos.top;

            $picker
                .show()
                .offset({
                    left: x,
                    top: y
                });

            return true;
        } else {
            $picker.hide();
            return false;
        }
    },

    /**
     * Concatenates contextmenu configuration arrays
     *
     * @param amenu
     * @param index
     * @param n
     */
    arrconcat: function (amenu, index, n) {
        var html, id, item, a, li;
        if (typeof amenu == 'undefined' || typeof amenu['view'] == 'undefined') {
            return;
        }
        var cmenu = amenu['view'];
        if (jQuery('#tool__bar')[0] && amenu['edit'] instanceof Array) {
            cmenu = amenu['edit'].concat(cmenu);
        }
        var node = index.aNodes[n];
        id = node.hns || node.dokuid;

        var createCMenuEntry = function (entry) {
            return '<a title="' + ((entry[2]) ? entry[2] : entry[0]) + '" href="' + eval(entry[1]) + '">' + entry[0] + '</a>';
        };

        jQuery.each(cmenu, function (i, cmenuentry) {
            if (cmenuentry == '') {
                return true;
            }
            item = document.createElement('li');
            var $cmenu = jQuery('#r' + index.obj);
            if (cmenuentry[1]) {
                if (cmenuentry[1] instanceof Array) {
                    html = document.createElement('ul');
                    jQuery.each(cmenuentry[1], function (a, subcmenuentry) {
                        li = document.createElement('li');
                        li.innerHTML = createCMenuEntry(subcmenuentry);
                        html.appendChild(li);
                    });

                    //}
                    item.innerHTML = '<span class="indexmenu_submenu">' + cmenuentry[0] + '</span>';
                    html.left = $cmenu[0].width;
                    item.appendChild(html);
                } else {
                    item.innerHTML = createCMenuEntry(cmenuentry);
                }
            } else {
                item.innerHTML = cmenuentry;
            }
            $cmenu.children().last().append(item);
        });
    },

    /**
     *
     *
     * @param obj
     * @param e
     */
    mouseposition: function (obj, e) {
        //http://www.quirksmode.org/js/events_properties.html
        var X = 0, Y = 0;
        if (!e) e = window.event;
        if (e.pageX || e.pageY) {
            X = e.pageX;
            Y = e.pageY;
        }
        else if (e.clientX || e.clientY) {
            X = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
            Y = e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
        }
        obj.style.left = X - 5 + 'px';
        obj.style.top = Y - 5 + 'px';
    },

    /**
     *
     *
     * @param n
     * @param obj
     * @param e
     */
    checkcontextm: function (n, obj, e) {
        e = e || event;
        if ((e.which == 3 || e.button == 2) || (window.opera && e.which == 1 && e.ctrlKey)) {
            obj.contextmenu(n, e);
            IndexmenuContextmenu.stopevt(e);
        }
    },

    /**
     *
     *
     * @param e
     * @returns {boolean}
     */
    stopevt: function (e) {
        if (!window.indexmenu_contextmenu) {
            return true;
        }
        e = e || event;
        e.preventDefault ? e.preventDefault() : e.returnValue = false;
        return false;
    }
};

