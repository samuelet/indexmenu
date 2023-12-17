
// Context menu
var indexmenu_contextmenu = {'all': []};

/* DOKUWIKI:include scripts/nojsindex.js */
/* DOKUWIKI:include scripts/toolbarindexwizard.js */
/* DOKUWIKI:include scripts/contextmenu.js */
/* DOKUWIKI:include scripts/indexmenu.js */
/* DOKUWIKI:include scripts/contextmenu.local.js */


/* DOKUWIKI:include scripts/fancytree/jquery.fancytree-all.min.js */
// function logEvent(event, data, msg){
//     //        var args = Array.isArray(args) ? args.join(", ") :
//     msg = msg ? ": " + msg : "";
//     jQuery.ui.fancytree.info("Event('" + event.type + "', node=" + data.node + ")" + msg);
// }
jQuery(function(){  // on page load
    // Create the tree inside the <div id="tree"> element.
    const predefinedPresets = {
        'bootstrap': { //works with template bootstrap3 or by manually adding resources to icon plugin assets
            'preset': 'bootstrap3',
            'map': {}
        },
        'bootstrap-n': { //works with template bootstrap3 or ..etc
            'preset': 'bootstrap3',
            'map': {}
        },
        'awesome': { //works with icons-plugin, settings: enable plugin»icons»loadFontAwesome
            'preset': 'awesome4', //plugin icons does include only awesome4, not awesome5.
            'map': {}
        },
        'material': { // add Material Icons font stylesheet to header with TPL_METAHEADER_OUTPUT in action component
            'preset': 'material',
            'map': {}
        },
        'mdi': { //works with icons-plugin, settings: enable plugin»icons»loadMaterialDesignIcons

            'preset': '',
            'map': {
                _addClass: "mdi",
                checkbox: "mdi-checkbox-blank-outline",
                checkboxSelected: "mdi-check-box-outline",
                checkboxUnknown: "mdi-checkbox-intermediate fancytree-helper-indeterminate-cb",
                dragHelper: "mdi-play",
                dropMarker: "mdi-skip-forward",
                error: "mdi-warning",
                expanderClosed: "mdi-chevron-right",
                expanderLazy: "mdi-chevron-right",
                expanderOpen: "mdi-chevron-down",
                // We may prevent wobbling rotations on FF by creating a separate sub element:
                loading: "mdi-refresh",
                nodata: "mdi-information-outline",
                noExpander: "",
                radio: "mdi-radiobox-blank", // "fa-circle-o"
                radioSelected: "mdi-radiobox-marked",
                // Default node icons.
                // (Use tree.options.icon callback to define custom icons based on node data)
                doc: "mdi-file-outline",
                docOpen: "mdi-file-outline",
                folder: "mdi-folder",
                folderOpen: "mdi-folder-open",
            }
        },
        'typicons': { //works with icons-plugin, settings: enable plugin»icons»loadTypicons
            'preset': '',
            'map': {
                _addClass: "typcn",
                checkbox: "typcn-media-stop-outline",
                checkboxSelected: "typcn-input-checked",
                checkboxUnknown: "typcn-media-stop-outline fancytree-helper-indeterminate-cb",
                dragHelper: "typcn-media-play-outline",
                dropMarker: "typcn-media-fast-forward-outline",
                error: "typcn-warning",
                expanderClosed: "typcn-media-play",
                expanderLazy: "typcn-media-play",
                expanderOpen: "typcn-arrow-sorted-down",
                // We may prevent wobbling rotations on FF by creating a separate sub element:
                loading: "typcn-arrow-sync",
                nodata: "typcn-info-large",
                noExpander: "",
                radio: "typcn-media-record-outline", // "fa-circle-o"
                radioSelected: "typcn-media-record",
                // Default node icons.
                // (Use tree.options.icon callback to define custom icons based on node data)
                doc: "typcn-document",
                docOpen: "typcn-document",
                folder: "typcn-folder",
                folderOpen: "typcn-folder-open",
            }
        }

    };
    // userDefinedPresets can be defined in conf/userscript.js
    const presets = {...predefinedPresets, ...(typeof userDefinedPresets === 'undefined' ? [] : userDefinedPresets)};

    jQuery(".indexmenu_js2").each(function(){
        let $tree = jQuery(this),
            id = $tree.attr('id');
        const options = $tree.data('options');
        console.log("options");
        console.log(options);
        let themePreset = presets[options.opts.theme];

        $tree.fancytree({
            extensions: themePreset ? ["glyph"] : [],
            glyph: {
                preset: themePreset ? themePreset.preset : '',
                map: themePreset ? themePreset.map : {}
            },
            //minExpandLevel: 2, // number of levels already expanded, and not unexpandable.
            clickFolderMode: 3, // expand with single click instead of dblclick
            //autoCollapse: true, //closes other opened nodes, so only one node is opened
            // autoScroll: true, // for keyboard..  --opening folders becomes jumpy
            autoActivate: false, // we use scheduleAction(). Otherwise, looping in combination with clicking
            activeVisible: false,
            // tooltip: function(event, data) {
            //     return data.node.title;
            // },
            escapeTitles: false,
            tooltip: true,
            rtl: jQuery('html[dir=rtl]').length,
            focus: function(event, data) {
                // Auto-activate focused node after 1 second (practical for use with keyboard)
                if(data.node.key){
                    data.node.scheduleAction("activate", 1000);
                }
            },
            blur: function(event, data) {
                data.node.scheduleAction("cancel");
            },
            click: function(event, data) {
                // return false to prevent default behavior (i.e. activation, ...)
                // var targettype = data.targetType; //store target type global
                if(data.targetType === 'expander') {
                    data.node.toggleExpanded(); //perform default action
                    return false;  // prevent default action
                }
            },
            activate: function(event, data){
                const node = data.node;
                // let orgEvent = data.originalEvent;
                // console.log("activate1");
                // console.log(node.key)
                // console.log(JSINFO.id)
// console.log(data);
                //prevent looping (hns is false or a page id)
                if(node.key === JSINFO.id || node.data.hns === JSINFO.id) {
                    //node is equal to current page
                    return;
                }
                //TODO node.key=="namespace:", JSINFO.id=="namespace:start", here an extra reload is done..
                //happens if you try to close an folder with hns(a start page) while that start page shown.

                // if(targettype === 'expander') {  //check global stored target type
                //     return false;
                // }
                if(node.data.url === false) {
                    return false;
                }
                // console.log("activate");
                // console.log(node.data.url);
                if(node.data.url){
                    //window.open(node.data.href, (orgEvent.ctrlKey || orgEvent.metaKey) ? "_blank" /*node.data.target*/ : node.data.target);
                    window.location.href = node.data.url;
                    // console.log("reload");
                }
            },
            init: function(event, data) {
                //activate current node
                data.tree.reactivate();
            },
            enhanceTitle: function(event, data) {
                let node = data.node;

                if(node.data.url === false) {
                    return;
                }
                data.$title.html("<a href='" + node.data.url + "'>" + node.title + "</a>");
            },
            source: {
                url: DOKU_BASE + 'lib/exe/ajax.php',
                data: {
                    ns: options.ns,
                    call: 'indexmenu',
                    req: 'fancytree',

                    level: options.opts.level, //only init
                    nons: options.opts.nons ? 1 : 0, //only init; without ns, no lower levels possible
                    nopg: options.opts.nopg ? 1 : 0,
                    subnss: options.opts.subnss, //subns to open. Only on init array, later just current ns string
                    navbar: options.opts.navbar ? 1 : 0, //only init: open tree at current page
                    currentpage: JSINFO.id,
                    max: options.opts.max, //#n of max#n#m
                    //js: 1,//options.opts.js, //only init true, later false
                    skipns: options.opts.skipns,
                    skipfile: options.opts.skipfile,
                    sort: options.sort.sort ? options.sort.sort : 0, //'t', 'd', false TODO is false handled correctly?
                    msort: options.sort.msort ? options.sort.msort : 0, //'indexmenu_n', or metadata 'key subkey' TODO is empty handled correctly?
                    rsort: options.sort.rsort ? 1 : 0,
                    nsort: options.sort.nsort ? 1 : 0,
                    hsort: options.sort.hsort ? 1 : 0,

                    init: 1
                }
            },
            lazyLoad: function(event, data) {
                const node = data.node;
                // Issue an Ajax request to load child nodes
                data.result = {
                    url: DOKU_BASE + 'lib/exe/ajax.php', //TODO reminder: after adding node.key to subnss and maxajax loading is incomplete for ns3
                    data: {
                        ns: node.key, // ns with trailing :
                        call: 'indexmenu',
                        req: 'fancytree',

                        level: 1, //level opened nodes, for follow up ajax requests only next level, so:1
                        //nons: options.opts.nons ? 1 : 0, //todo: sets text false
                        nopg: options.opts.nopg ? 1 : 0,
                        subnss: '',//node.key,//options.opts.subnss, //TODO only string of current ns, that should be opened (use this only for navbar!)
                        currentpage: JSINFO.id,
                        max: options.opts.maxajax, //#m of max#n#m
                        //js: 0, //options.opts.js, //original: only true needed if init
                        skipns: options.opts.skipns,
                        skipfile: options.opts.skipfile,
                        sort: options.sort.sort ? options.sort.sort  : 0,
                        msort: options.sort.msort ? options.sort.msort : 0,
                        rsort: options.sort.rsort ? 1 : 0,
                        nsort: options.sort.nsort ? 1 : 0,
                        hsort: options.sort.hsort ? 1 : 0,

                        init: 0
                    }
                }
            }
        });

        //hide the fallback nojs indexmenu
        jQuery('#nojs_' + id.substring(6)).css("display", "none");


        // Note: Loading and initialization may be asynchronous, so the nodes may not be accessible yet.

        // On page load, activate node if node.data.href matches the url#href
        let tree = jQuery.ui.fancytree.getTree("#" + id),
            path = window.parent && window.parent.location.pathname;
// console.log(path);
// console.log('test');
        if(path) {
            let arr = path.split('/'); // not reliable with config:useslash?
            let last = arr[arr.length-1] || arr[arr.length-2];
            // console.log(arr);
            // console.log(last);

            // tree.activateKey(last);
            // var node1=tree.getNodeByKey(last);
            // console.log(node1);
            //     node1.setActive();
            // also possible:
            //                $.ui.fancytree.getTree("#tree").getNodeByKey("id4.3.2").setActive();

            // tree.visit(function(n) {
            //     console.log(n.key);
            //     console.log(n);
            //     if( n.key && n.key === last ) {
            //         n.setActive();  //if not using iframes, this creates a loops in combination with activate above
            //         return false; // done: break traversal
            //     }
            // });
        }
// console.log(tree);
// console.log("test");
//         jQuery.contextMenu({
//             selector: "span.fancytree-title",
//             items: {
//                 // "cut": {name: "Cut", icon: "cut",
//                 //     callback: function(key, opt){
//                 //         var node = jQuery.ui.fancytree.getNode(opt.$trigger);
//                 //         alert("Clicked on " + key + " on " + node);
//                 //     }
//                 // },
//                 "page": {name: "Page", icon: "", disabled: true },
//                 "sep1": "----",
//                 "revs": {name: "Revisions", icon: "ui-icon-arrowreturn-1-w", disabled: false },
//                 "toc": {name: "ToC preview", icon: "ui-icon-bookmark", disabled: false },
//                 "edit": {name: "Edit", icon: "edit", disabled: false },
//                 "hpage": {name: "Headpage", icon: "add", disabled: false},
//                 "spage": {name: "Start page", icon: "add", disabled: false},
//                 "cpage": {name: "Custom page...", icon: "add", disabled: false},
//                 "acls": {name: "Acls", icon: "ui-icon-locked", disabled: false},
//                 "purge": {name: "Purge cache", icon: "loading", disabled: false},
//                 "html": {name: "Export as HTML", icon: "ui-icon-document", disabled: false},
//                 "text": {name: "Export as text", icon: "ui-icon-note", disabled: false},
//                 "sep2": "----",
//                 "ns": {name: "Namespace", icon: "", disabled: true},
//                 "sep3": "----",
//                 "search": {name: "Search...", icon: "ui-icon-search", disabled: false},
//                 "npage": {name: "New page...", icon: "add", disabled: false},
//                 "nshpage": {name: "Headpage here", icon: "add", disabled: false},
//                 "nsacls": {name: "Acls", icon: "ui-icon-locked", disabled: false}
//             },
//             callback: function(itemKey, opt) {
//                 var node = jQuery.ui.fancytree.getNode(opt.$trigger);
//                 alert("select " + itemKey + " on " + node);
//             }
//         });

        // $tree.contextmenu({
        //     delegate: "span.fancytree-title",
        //     autoFocus: true,
        //     //      menu: "#options",
        //     menu: [
        //         {title: "Page", cmd: 'pg'},
        //         {title: "----", cmd: 'pg'},
        //         {title: "Revisions", cmd: "revs", uiIcon: "ui-icon-arrowreturn-1-w"},
        //         {title: "ToC preview", cmd: "toc", uiIcon: "ui-icon-bookmark"},
        //         {title: "Edit", cmd: "edit", uiIcon: "ui-icon-pencil", disabled: false },
        //         {title: "Headpage", cmd: "hpage", uiIcon: "ui-icon-plus"},
        //         {title: "Start page", cmd: "spage", uiIcon: "ui-icon-plus"},
        //         {title: "Custom page...", cmd: "cpage", uiIcon: "ui-icon-plus"},
        //         {title: "Acls", cmd: "acls", uiIcon: "ui-icon-locked", disabled: true },
        //         {title: "Purge cache", cmd: "purge", uiIcon: "ui-icon-arrowrefresh-1-e"},
        //         {title: "Export as HTML", cmd: "html", uiIcon: "ui-icon-document"},
        //         {title: "Export as text", cmd: "text", uiIcon: "ui-icon-note"},
        //         {title: "Namespace", cmd:'ns'},
        //         {title: "----", cmd:'ns'},
        //         {title: "Search...", cmd: "search", uiIcon: "ui-icon-search"},
        //         {title: "New page...", cmd: "npage", uiIcon: "ui-icon-plus"},// children:[]
        //         {title: "Headpage here", cmd: "nshpage", uiIcon: "ui-icon-plus"},
        //         {title: "Acls", cmd: "nsacls", uiIcon: "ui-icon-locked"}
        //     ],
        //     beforeOpen: function(event, ui) {
        //         var node = jQuery.ui.fancytree.getNode(ui.target);
        //         // Modify menu entries depending on node status
        //         $tree.contextmenu("enableEntry", "toc", node.isFolder());
        //         // Show/hide single entries
        //         $tree.contextmenu("showEntry", "pg", !node.isFolder());
        //         $tree.contextmenu("showEntry", "revs", !node.isFolder());
        //         $tree.contextmenu("showEntry", "toc", !node.isFolder());
        //         $tree.contextmenu("showEntry", "edit", !node.isFolder());
        //         $tree.contextmenu("showEntry", "hpage", !node.isFolder());
        //         $tree.contextmenu("showEntry", "spage", !node.isFolder());
        //         $tree.contextmenu("showEntry", "cpage", !node.isFolder());
        //         $tree.contextmenu("showEntry", "acls", !node.isFolder());
        //         $tree.contextmenu("showEntry", "purge", !node.isFolder());
        //         $tree.contextmenu("showEntry", "html", !node.isFolder());
        //         $tree.contextmenu("showEntry", "text", !node.isFolder());
        //
        //         $tree.contextmenu("showEntry", "ns", node.isFolder());
        //         $tree.contextmenu("showEntry", "search", node.isFolder());
        //         $tree.contextmenu("showEntry", "npage", node.isFolder());
        //         $tree.contextmenu("showEntry", "nshpage", node.isFolder());
        //         $tree.contextmenu("showEntry", "nsacls", node.isFolder());
        //
        //         // Activate node on right-click
        //         node.setActive();
        //         // Disable tree keyboard handling
        //         ui.menu.prevKeyboard = node.tree.options.keyboard;
        //         node.tree.options.keyboard = false;
        //     },
        //     close: function(event, ui) {
        //         // Restore tree keyboard handling
        //         // console.log("close", event, ui, this)
        //         // Note: ui is passed since v1.15.0
        //         var node = jQuery.ui.fancytree.getNode(ui.target);
        //         node.tree.options.keyboard = ui.menu.prevKeyboard;
        //         node.setFocus();
        //     },
        //     select: function(event, ui) {
        //         var node = jQuery.ui.fancytree.getNode(ui.target);
        //         alert("select " + ui.cmd + " on " + node);
        //     }
        // });
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
