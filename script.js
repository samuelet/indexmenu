/* DOKUWIKI:include scripts/nojsindex.js */
/* DOKUWIKI:include scripts/toolbarindexwizard.js */


/**
 * Add button action for the link wizard button
 *
 * @param  DOMElement btn   Button element to add the action to
 * @param  array      props Associative array of button properties
 * @param  string     edid  ID of the editor textarea
 * @return boolean    If button should be appended
 */
function addBtnActionIndexmenu($btn, props, edid) {
    indexmenu_wiz.init(jQuery('#'+edid));
    $btn.click(function(){
        indexmenu_wiz.toggle();
        return false;
    });
    return true;
}


// try to add button to toolbar
if (window.toolbar != undefined) {
    window.toolbar[window.toolbar.length] = {
        "type":"Indexmenu",
        "title":"Insert the Indexmenu tree",
        "icon":"../../plugins/indexmenu/images/indexmenu_toolbar.png"
    }
}




/*
// Section below works only in release till 2012-09-10 "Adora Belle". Uncomment to use.
// - Later releases has removed the old javascript library https://github.com/splitbrain/dokuwiki/commit/99421189

// Queue of loaded script files
var indexmenu_jsqueue = new Array();
// Queue of loaded css files
var indexmenu_cssqueue = new Array();
// Queue of nojs trees
var indexmenu_nojsqueue = new Array();
// Context menu
var indexmenu_contextmenu = {'all': new Array()};

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

function indexmenu_createTocMenu(get, picker, btn) {
    var toc_picker = $(picker);
    if (!toc_picker) {
        toc_picker = indexmenu_createPicker(picker);
        toc_picker.className = 'dokuwiki indexmenu_toc';
        toc_picker.innerHTML = '<a href="#"><img src="' + DOKU_BASE + 'lib/plugins/indexmenu/images/close.gif" class="indexmenu_close" /></a><div />';
        addEvent(toc_picker.firstChild, 'click', function (event) {
            event.stopPropagation();
            return indexmenu_showPicker(picker)
        });
    } else {
        toc_picker.style.display = 'none';
    }
    indexmenu_ajaxmenu(get, toc_picker, $(btn), toc_picker.childNodes[1]);
}

function indexmenu_ajaxmenu(get, picker, btn, container, oncomplete) {
    var indx_list;
    if (container) {
        indx_list = container;
    } else {
        indx_list = picker;
    }
    if (!indexmenu_showPicker(picker, btn)) return;
    // We use SACK to do the AJAX requests
    var ajax = new sack(DOKU_BASE + 'lib/plugins/indexmenu/ajax.php');
    ajax.encodeURIString = false;
    ajax.onLoading = function () {
        indx_list.innerHTML = '<div class="tocheader">Loading .....</div>';
    };

    // define callback
    ajax.onCompletion = function () {
        var data = this.response;
        indx_list.innerHTML = "";
        if (isFunction(oncomplete)) {
            oncomplete(data, indx_list);
        } else {
            indx_list.innerHTML = data;
        }
    };

    ajax.runAJAX(encodeURI(get));
}

function indexmenu_createPicker(id, cl) {
    var indx_list = document.createElement('div');
    indx_list.className = cl || 'picker';
    indx_list.id = id;
    indx_list.style.position = 'absolute';
    indx_list.style.display = 'none';
    var body = document.getElementsByTagName('body')[0];
    body.appendChild(indx_list);
    return indx_list;
}

function indexmenu_showPicker(pickerid, btn) {
    var x = 3, y = 3, picker = $(pickerid);
    if (picker.style.display == 'none') {
        x += findPosX(btn);
        y += findPosY(btn);
        if (picker.id != 'picker_plugin_indexmenu') {
            x += btn.offsetWidth - 3;
        } else {
            y += btn.offsetHeight;
        }
        picker.style.display = 'block';
        picker.style.left = x + 'px';
        picker.style.top = y + 'px';
        return true;
    } else {
        picker.style.display = 'none';
        return false;
    }
}

function indexmenu_loadtoolbar() {
    var toolbar = $('tool__bar');
    if (!toolbar) return;
    indexmenu_loadJs(DOKU_BASE + 'lib/plugins/indexmenu/edit.js');
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
    e.preventdefault ? e.preventdefault() : e.returnValue = false;
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
    var X=0,Y=0;
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

function indexmenu_arrconcat(amenu,index,n) {
    var html,id,item,i,a,li;
    if (isUndefined(amenu) || isUndefined(amenu['view'])) {return false;}
    var cmenu = amenu['view'];
    if ($('tool__bar') && isArray(amenu['edit'])) {
	cmenu = amenu['edit'].concat(cmenu);
    }
    var node = index.aNodes[n];
    id = node.hns || node.dokuid;
    for (i in cmenu) {
	if (cmenu[i] == '') {continue;}
	item = document.createElement('li');
	if (cmenu[i][1]) {
	    if (isArray(cmenu[i][1])) {
		html=document.createElement('ul');
		for (a in cmenu[i][1]) {
		    li = document.createElement('li');
		    li.innerHTML = '<a title="'+((cmenu[i][1][a][2]) ? cmenu[i][1][a][2] : cmenu[i][1][a][0])+'" href="'+eval(cmenu[i][1][a][1])+'">'+cmenu[i][1][a][0]+'</a>';
		    html.appendChild(li);
		}
		item.innerHTML = '<span class="indexmenu_submenu">'+cmenu[i][0]+'</span>';
		html.left = $('r' + index.obj).width;
		item.appendChild(html);
	    } else {
		item.innerHTML = '<a title="'+((cmenu[i][2]) ? cmenu[i][2] : cmenu[i][0])+'" href="'+eval(cmenu[i][1])+'">'+cmenu[i][0]+'</a>';
	    }
	} else {
	    item.innerHTML = cmenu[i];
	}
	$('r' + index.obj).lastChild.appendChild(item);
    }
}

addInitEvent(indexmenu_loadtoolbar);

   */ 
