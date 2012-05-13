/* Queue of loaded script files */
var indexmenu_jsqueue = [];
/* Queue of loaded css files */
var indexmenu_cssqueue = [];
/* Queue of nojs trees */
var indexmenu_nojsqueue = [];

function indexmenu_findExt(path){
    var ext = "gif";
    var cext = path.lastIndexOf(".");
    if ( cext > -1){
	cext++;
	cext = path.substring(cext, path.length).toLowerCase();
	if ((cext == "png") || (cext == "jpg")) {ext = cext;}
    }
    return ext;
}

function indexmenu_createTocMenu(get,picker,btn) {
    var toc_picker = $(picker);
    if (!toc_picker) {
	toc_picker=indexmenu_createPicker(picker);
	toc_picker.className='dokuwiki indexmenu_toc';
	toc_picker.innerHTML='<a href="#"><img src="'+DOKU_BASE+'lib/plugins/indexmenu/images/close.gif" class="indexmenu_close" /></a><div />';
        addEvent(toc_picker.firstChild, 'click',function (event) {event.stopPropagation();return indexmenu_showPicker(picker)});
    } else {
	toc_picker.style.display = 'none';
    }
    indexmenu_ajaxmenu(get,toc_picker,$(btn),toc_picker.childNodes[1]);
}

function indexmenu_ajaxmenu(get,picker,btn,container,oncomplete) {
    var indx_list;
    if (container) {
	indx_list = container;
    } else {
	indx_list = picker;
    }
    if (!indexmenu_showPicker(picker,btn)) return;
    // We use SACK to do the AJAX requests
    var ajax = new sack(DOKU_BASE+'lib/plugins/indexmenu/ajax.php');
    ajax.encodeURIString=false;
    ajax.onLoading = function () {
	indx_list.innerHTML='<div class="tocheader">Loading .....</div>';
    };
    
    // define callback
    ajax.onCompletion = function(){
        var data = this.response;
	indx_list.innerHTML="";
	if (isFunction(oncomplete)) {
	    oncomplete(data,indx_list);
	} else {
	    indx_list.innerHTML=data;
	}
    };
    
    ajax.runAJAX(encodeURI(get));
}

function indexmenu_createPicker(id,cl) {
    var indx_list = document.createElement('div');
    indx_list.className = cl || 'picker';
    indx_list.id=id;
    indx_list.style.position = 'absolute';
    indx_list.style.display  = 'none';
    var body = document.getElementsByTagName('body')[0];
    body.appendChild(indx_list);
    return indx_list;
}

function indexmenu_showPicker(pickerid,btn){
    var x = 3, y = 3, picker = $(pickerid);
    if(picker.style.display == 'none'){
	x += findPosX(btn);
	y += findPosY(btn);
	if (picker.id != 'picker_plugin_indexmenu') {
	    x += btn.offsetWidth-3;
	} else {
	    y += btn.offsetHeight;
	}
	picker.style.display = 'block';
	picker.style.left = x+'px';
	picker.style.top = y+'px';
	return true;
    }else{
	picker.style.display = 'none';
	return false;
    }
}

function indexmenu_loadtoolbar(){
    var toolbar = $('tool__bar');
    if(!toolbar) return;
    indexmenu_loadJs(DOKU_BASE+'lib/plugins/indexmenu/edit.js');
}

function indexmenu_loadJs(f){
    var basef = f.replace(/^.*[\/\\]/g, '');
    if (indexmenu_notinarray(indexmenu_jsqueue,basef)) {
	var oLink = document.createElement("script");
	oLink.src = f;
	oLink.type = "text/javascript";
	oLink.charset="utf-8";
	indexmenu_jsqueue.push(basef);
	document.getElementsByTagName("head")[0].appendChild(oLink);
    }
}

function indexmenu_checkcontextm(n,obj,e){
  var k=0;
  e=e||event;
  if ((e.which == 3 || e.button == 2) || (window.opera && e.which == 1 && e.ctrlKey)) {
    obj.contextmenu (n,e);
    indexmenu_stopevt(e);
  }
}

function indexmenu_stopevt(e) {
    if (!window.indexmenu_contextmenu) {
	return true;
    }
    e=e||event;
    e.preventdefault? e.preventdefault() : e.returnValue = false;
    return false;
}

function indexmenu_notinarray(array,val) {
    for (var i = 0; i < array.length; i++) {
	if (array[i] == val) {
	    return false;
	}
    }
    return true;
}

function indexmenu_mouseposition(obj,e) {
/*http://www.quirksmode.org/js/events_properties.html*/
    if (!e) e = window.event;
    if (e.pageX || e.pageY) 	{
      X = e.pageX;
      Y = e.pageY;
    }
    else if (e.clientX || e.clientY) 	{
      X = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
      Y = e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
    }
    obj.style.left=X-5+'px';
    obj.style.top=Y-5+'px';
}

addInitEvent(indexmenu_loadtoolbar);
