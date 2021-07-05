
// Context menu
var indexmenu_contextmenu = {'all': []};

/* DOKUWIKI:include scripts/nojsindex.js */
/* DOKUWIKI:include scripts/toolbarindexwizard.js */
/* DOKUWIKI:include scripts/contextmenu.js */
/* DOKUWIKI:include scripts/indexmenu.js */
/* DOKUWIKI:include scripts/contextmenu.local.js */


/* DOKUWIKI:include scripts/fancytree/jquery.fancytree-all.min.js */
/* DOKUWIKI:include scripts/contextmenu/jquery.ui-contextmenu.min.js */

jQuery(function(){  // on page load
    // Create the tree inside the <div id="tree"> element.
    jQuery(".indexmenu_js2").each(function(){
        let $tree = jQuery(this),
            id = $tree.attr('id');

        $tree.fancytree({
            extensions: [],
            //minExpandLevel: 2, // number of levels already expanded, and not unexpandable.
            clickFolderMode: 3, // expand with single click instead of dblclick
            //autoCollapse: true, //closes other opened nodes, so only one node is opened
            autoScroll: true, // for keyboard..
            autoActivate: false, // we use scheduleAction(). Otherwise looping in combination with clicking
            // tooltip: function(event, data) {
            //     return data.node.title;
            // },
            escapeTitles: false,
            tooltip: true,
            focus: function(event, data) {
                var node = data.node;
                // Auto-activate focused node after 1 second (practical for use with keyboard)
                if(node.key){
                    node.scheduleAction("activate", 1000);
                }
            },
            blur: function(event, data) {
                data.node.scheduleAction("cancel");
            },
            enhanceTitle: function(event, data) {
                let url, node = data.node;
                // console.log('enhanceTitle');
                // console.log(data.node);
                // console.log(data.$title);
                if(!node.folder) {
                    url = DOKU_BASE + node.key
                } else if(node.data.hns === false) {
                    return;
                } else {
                    url = DOKU_BASE + node.data.hns
                }
                data.$title.html("<a href='" + url + "'>" + node.title + "</a>");
            },
        });

        //hide the fallback nojs indexmenu
        jQuery('#nojs_' + id.substr(6)).css("display", "none");


        // Note: Loading and initialization may be asynchronous, so the nodes may not be accessible yet.

        // On page load, activate node if node.data.href matches the url#href
        let tree = jQuery.ui.fancytree.getTree("#" + id),
            path = window.parent && window.parent.location.pathname;

        if(path) {
            let arr = path.split('/');
            let last = arr[arr.length-1] || arr[arr.length-2];
            tree.visit(function(n) {
                if( n.key && n.key === last ) {
                    n.setActive();  //if not using iframes, this create a loops in combination with activate above
                    return false; // done: break traversal
                }
            });
        }
console.log(tree);
        $tree.contextmenu({
            delegate: "span.fancytree-title",
            autoFocus: true,
            //      menu: "#options",
            menu: [
                {title: "Page", cmd: 'pg'},
                {title: "----", cmd: 'pg'},
                {title: "Revisions", cmd: "revs", uiIcon: "ui-icon-arrowreturn-1-w"},
                {title: "ToC preview", cmd: "toc", uiIcon: "ui-icon-bookmark"},
                {title: "Edit", cmd: "edit", uiIcon: "ui-icon-pencil", disabled: false },
                {title: "Headpage", cmd: "hpage", uiIcon: "ui-icon-plus"},
                {title: "Start page", cmd: "spage", uiIcon: "ui-icon-plus"},
                {title: "Custom page...", cmd: "cpage", uiIcon: "ui-icon-plus"},
                {title: "Acls", cmd: "acls", uiIcon: "ui-icon-locked", disabled: true },
                {title: "Purge cache", cmd: "purge", uiIcon: "ui-icon-arrowrefresh-1-e"},
                {title: "Export as HTML", cmd: "html", uiIcon: "ui-icon-document"},
                {title: "Export as text", cmd: "text", uiIcon: "ui-icon-note"},
                {title: "Namespace", cmd:'ns'},
                {title: "----", cmd:'ns'},
                {title: "Search...", cmd: "search", uiIcon: "ui-icon-search"},
                {title: "New page...", cmd: "npage", uiIcon: "ui-icon-plus"},// children:[]
                {title: "Headpage here", cmd: "nshpage", uiIcon: "ui-icon-plus"},
                {title: "Acls", cmd: "nsacls", uiIcon: "ui-icon-locked"}
            ],
            beforeOpen: function(event, ui) {
                var node = jQuery.ui.fancytree.getNode(ui.target);
                // Modify menu entries depending on node status
                $tree.contextmenu("enableEntry", "toc", node.isFolder());
                // Show/hide single entries
                $tree.contextmenu("showEntry", "pg", !node.isFolder());
                $tree.contextmenu("showEntry", "revs", !node.isFolder());
                $tree.contextmenu("showEntry", "toc", !node.isFolder());
                $tree.contextmenu("showEntry", "edit", !node.isFolder());
                $tree.contextmenu("showEntry", "hpage", !node.isFolder());
                $tree.contextmenu("showEntry", "spage", !node.isFolder());
                $tree.contextmenu("showEntry", "cpage", !node.isFolder());
                $tree.contextmenu("showEntry", "acls", !node.isFolder());
                $tree.contextmenu("showEntry", "purge", !node.isFolder());
                $tree.contextmenu("showEntry", "html", !node.isFolder());
                $tree.contextmenu("showEntry", "text", !node.isFolder());

                $tree.contextmenu("showEntry", "ns", node.isFolder());
                $tree.contextmenu("showEntry", "search", node.isFolder());
                $tree.contextmenu("showEntry", "npage", node.isFolder());
                $tree.contextmenu("showEntry", "nshpage", node.isFolder());
                $tree.contextmenu("showEntry", "nsacls", node.isFolder());

                // Activate node on right-click
                node.setActive();
                // Disable tree keyboard handling
                ui.menu.prevKeyboard = node.tree.options.keyboard;
                node.tree.options.keyboard = false;
            },
            close: function(event, ui) {
                // Restore tree keyboard handling
                // console.log("close", event, ui, this)
                // Note: ui is passed since v1.15.0
                var node = jQuery.ui.fancytree.getNode(ui.target);
                node.tree.options.keyboard = ui.menu.prevKeyboard;
                node.setFocus();
            },
            select: function(event, ui) {
                var node = jQuery.ui.fancytree.getNode(ui.target);
                alert("select " + ui.cmd + " on " + node);
            }
        });
    });
});


/**
 * Add button action for the indexmenu wizard button
 *
 * @param  {jQuery}   $btn  Button element to add the action to
 * @param  {Array}    props Associative array of button properties
 * @param  {string}   edid  ID of the editor textarea
 * @return {boolean}  If button should be appended
 */
function addBtnActionIndexmenu($btn, props, edid) {
    indexmenu_wiz.init(jQuery('#' + edid));
    $btn.click(function () {
        indexmenu_wiz.toggle();
        return false;
    });
    return true;
}


// try to add button to toolbar
if (window.toolbar !== undefined) {
    window.toolbar[window.toolbar.length] = {
        "type": "Indexmenu",
        "title": "Insert the Indexmenu tree",
        "icon": "../../plugins/indexmenu/images/indexmenu_toolbar.png"
    }
}


/**
 *  functions for js index renderer and contextmenu
 */
var IndexmenuUtils = {

    /**
     * Determine extension from given theme dir name
     *
     * @param {string} themedir name of theme dir
     * @returns {string} extension gif, png or jpg
     */
    determineExtension: function (themedir) {
        let extension = "gif";
        let posext = themedir.lastIndexOf(".");
        if (posext > -1) {
            posext++;
            let ext = themedir.substring(posext, themedir.length).toLowerCase();
            if ((ext === "png") || (ext === "jpg")) {
                extension = ext;
            }
        }
        return extension;
    },

    /**
     * Create div with given id and class on body and return it
     *
     * @param {string} id picker id
     * @param {string} cl class(es)
     * @return {jQuery} jQuery div
     */
    createPicker: function (id, cl) {
        return jQuery('<div>')
            .addClass(cl || 'picker')
            .attr('id', id)
            .css({position: 'absolute'})
            .hide()
            .appendTo('body');
    }

};
