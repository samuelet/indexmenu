

// Queue of loaded script files
var indexmenu_jsqueue = new Array();
// Context menu
var indexmenu_contextmenu = {'all': new Array()};

/* DOKUWIKI:include scripts/nojsindex.js */
/* DOKUWIKI:include scripts/toolbarindexwizard.js */
/* DOKUWIKI:include scripts/contextmenu.js */
/* DOKUWIKI:include scripts/indexmenu.js */



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
if (window.toolbar != undefined) {
    window.toolbar[window.toolbar.length] = {
        "type": "Indexmenu",
        "title": "Insert the Indexmenu tree",
        "icon": "../../plugins/indexmenu/images/indexmenu_toolbar.png"
    }
}

/* functions for js renderer and contextmenu */

function indexmenu_findExt(path) {
    var ext = "gif";
    var cext = path.lastIndexOf(".");
    if (cext > -1) {
        cext++;
        cext = path.substring(cext, path.length).toLowerCase();
        if ((cext == "png") || (cext == "jpg")) {
            ext = cext;
        }
    }
    return ext;
}

/**
 * create div with given id and class on body and return it
 *
 * @param {string} id picker id
 * @param {string} cl class(es)
 * @return {jQuery} jQuery div
 */
function indexmenu_createPicker(id, cl) {
    return jQuery('<div>')
        .addClass(cl || 'picker')
        .attr('id', id)
        .css({position: 'absolute'})
        .hide()
        .appendTo('.dokuwiki:first');
}

/**
 * Create or catch the picker and hide it, next call the ajax content loading to get the ToC
 *
 * @param {string} get    query string
 * @param {string} picker id of picker
 * @param {string} btn    id of button
 */
function indexmenu_createTocMenu(get, picker, btn) {
    var $toc_picker = jQuery('#'+picker);
    if (!$toc_picker.length) {
        $toc_picker = indexmenu_createPicker(picker, 'indexmenu_toc');
        $toc_picker
            .html('<a href="#"><img src="' + DOKU_BASE + 'lib/plugins/indexmenu/images/close.gif" class="indexmenu_close" /></a><div />')
            .children().first().click(function(event) {
                event.stopPropagation();
                return indexmenu_togglePicker($toc_picker);
            });
    } else {
        $toc_picker.hide();
    }
    indexmenu_ajaxmenu(get, $toc_picker, jQuery('#'+btn), $toc_picker.children().last());
}
/**
 * Shows the picker and adds to it or to an internal containter the ajax content
 *
 * @param {string}   get        query string
 * @param {jQuery}   $picker
 * @param {jQuery}   $btn
 * @param {jQuery}   $container if defined ajax result is added to it, otherwise to $picker
 * @param {function} oncomplete called when defined to handle ajax result
 */
function indexmenu_ajaxmenu(get, $picker, $btn, $container, oncomplete) {
    var $indx_list;
    $indx_list = $container || $picker;

    if (!indexmenu_togglePicker($picker, $btn)) return;

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
            $indx_list.html('<div class="tocheader">Loading .....</div>');
        },
        success: onComplete,
        dataType: 'html'
    });
}

/**
 * hide/show picker, will be shown beside btn
 *
 * @param {string|jQuery} $picker
 * @param {jQuery}        $btn
 * @return {Boolean} true if open, false closed
 */
function indexmenu_togglePicker($picker, $btn) {
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
}

function indexmenu_loadJs(f) {
    var basef = f.replace(/^.*[\/\\]/g, '');
    if (indexmenu_notinarray(indexmenu_jsqueue, basef)) {
        var oLink = document.createElement("script");
        oLink.src = f;
        oLink.type = "text/javascript";
        oLink.charset = "utf-8";
        indexmenu_jsqueue.push(basef);
        document.getElementsByTagName("head")[0].appendChild(oLink);
    }
}

function indexmenu_checkcontextm(n, obj, e) {
    var k = 0;
    e = e || event;
    if ((e.which == 3 || e.button == 2) || (window.opera && e.which == 1 && e.ctrlKey)) {
        obj.contextmenu(n, e);
        indexmenu_stopevt(e);
    }
}

function indexmenu_stopevt(e) {
    if (!window.indexmenu_contextmenu) {
        return true;
    }
    e = e || event;
    e.preventDefault ? e.preventDefault() : e.returnValue = false;
    return false;
}

function indexmenu_notinarray(array, val) {
    for (var i = 0; i < array.length; i++) {
        if (array[i] == val) {
            return false;
        }
    }
    return true;
}

function indexmenu_mouseposition(obj, e) {
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
}

function indexmenu_arrconcat(amenu, index, n) {
    var html, id, item, a, li;
    if (typeof amenu == 'undefined' || typeof amenu['view'] == 'undefined') {
        return;
    }
    var cmenu = amenu['view'];
    if (jQuery('#tool__bar')[0] && amenu['edit'] instanceof Array ) {
        cmenu = amenu['edit'].concat(cmenu);
    }
    var node = index.aNodes[n];
    id = node.hns || node.dokuid;

    var createCMenuEntry = function(entry) {
        return '<a title="' + ((entry[2]) ? entry[2] : entry[0]) + '" href="' + eval(entry[1]) + '">' + entry[0] + '</a>';
    };

    jQuery.each(cmenu, function(i, cmenuentry){
            if (cmenuentry == '') {
                return true;
            }
            item = document.createElement('li');
            if (cmenuentry[1]) {
                if (cmenuentry[1] instanceof Array) {
                    html = document.createElement('ul');
                    jQuery.each(cmenuentry[1], function(a, subcmenuentry) {
                        li = document.createElement('li');
                        li.innerHTML = createCMenuEntry(subcmenuentry);
                        html.appendChild(li);
                    });

                    //}
                    item.innerHTML = '<span class="indexmenu_submenu">' + cmenuentry[0] + '</span>';
                    html.left = jQuery('#r' + index.obj)[0].width;
                    item.appendChild(html);
                } else {
                    item.innerHTML = createCMenuEntry(cmenuentry);
                }
            } else {
                item.innerHTML = cmenuentry;
            }
            jQuery('#r' + index.obj).children().last().append(item);
    });

}
