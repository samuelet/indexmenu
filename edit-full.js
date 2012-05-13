function indexmenu_toolbar_additions() {
    var edbtn,cmenu,indx_btn,toolbar = $('tool__bar');
    if(!toolbar) return;
    edbtn = $('edbtn__save');
    if(!edbtn) return;
    var indx_list = indexmenu_createPicker('picker_plugin_indexmenu');
    indx_btn = indexmenu_createToolbar();
    toolbar.appendChild(indx_btn);
    indx_btn.onclick = function(){indexmenu_ajaxmenu('req=local',indx_list,this,false,indexmenu_createThemes);return false;};
    cmenu=window.indexmenu_contextmenu;
    if(cmenu[1]) {
	window.indexmenu_contextmenu[0]=cmenu[1].concat(cmenu[0]);
    }
}

function indexmenu_createThemes(data,indx_list) {
    if (data.substring(0,9) != 'indexmenu') {
	indx_list.innerHTML="Retrieving error";
	return;
    }
    var checkboxes=[['<p><strong><em>Navigation</em></strong></p>',0],
		    ['navbar','The tree opens at the current namespace'],
		    ['context','Display the tree of the current wiki namespace context'],
		    ['nocookie','Don\t remember open/closed nodes during user navigation'],
		    ['noscroll','Prevent to scrolling the tree when it does not fit its container width'],
		    ['notoc','Disable the toc preview feature'],
		    ['<p><strong><em>Sort</em></strong></p>',0],
		    ['nsort','Sort also namespaces'],
		    ['tsort','By title'],
		    ['dsort','By date'],
		    ['msort','By meta tag'],
		    ['<p><strong><em>Performance</em></strong></p>',0],
		    ['max#2','How many levels to render with ajax when a node is opened'],
		    ['maxjs#2','How many levels to render in the client browser when a node is opened'],
		    ['<p><strong><em>Filters</em></strong></p>',0],
		    ['nons','Show only pages'],
		    ['nopg','Show only namespaces']];

    var btn,key,theme_url,adata,f,f2,l,fo;
    adata=data.split(',');
    theme_url = DOKU_BASE + 'lib/plugins/indexmenu/images/';
    f=indexmenu_toolFrame(indx_list,'Indexmenu');
    l = document.createElement('div');
    l.className = 'no indexmenu_nojstoolbar';
    f.appendChild(l);
    btn = createToolButton(DOKU_BASE + 'lib/tpl/default/images/open.gif','nojs index');
    btn.innerHTML += 'Nojs';
    btn.className = 'pickerbutton';
    eval('btn.onclick = function(){indexmenu_opts("");}');
    l.appendChild(btn);

    l = document.createElement('div');
    l.className = 'no indexmenu_jstoolbar';
    f.appendChild(l);
    if (adata[0] != 'indexmenu') {
	l.innerHTML += 'No themes';
	adata=[];
    } else {
	adata.splice(0,3);
    }
    for (key in adata) {
	btn = createToolButton(theme_url + adata[key]+'/base.'+indexmenu_findExt(adata[key]),adata[key]);
	btn.className = 'pickerbutton';
	eval('btn.onclick = function(){indexmenu_opts("js#'+adata[key]+'");}');
	l.appendChild(btn);
    }
    f2=indexmenu_toolFrame(indx_list,'Options');
    fo=document.createElement('form');
    fo.id='indexmenu_optfrm';
    fo.className='indexmenu_opts';
    f2.appendChild(fo);
    for (key in checkboxes) {
	lc = document.createElement('label');
	lc.innerHTML=checkboxes[key][0]+' ';
	if (checkboxes[key][1]) {
	    lc.title= checkboxes[key][1];
	    btn=document.createElement('input');
	    btn.type = 'checkbox';
	    btn.name = 'check';
	    btn.title = checkboxes[key][1];
	    btn.value = checkboxes[key][0];
	    fo.appendChild(btn);
	}
	fo.appendChild(lc);
    }

    l = document.createElement('div');
    l.className = 'indexmenu_extratoolbar';
    l.innerHTML='<p><strong><em>Extra</em></strong></p>';
    f.appendChild(l);
    btn = createToolButton(theme_url+'/msort.gif','Insert the sort meta-number');
    btn.className = 'pickerbutton';
    btn.onclick = function(){insertTags(
	'wiki__text',
	'{{indexmenu_n>',
	'}}',
	'1'
	);
	$('picker_plugin_indexmenu').style.display='none';
	return false;
    };
    l.appendChild(btn);
}

function indexmenu_createToolbar (){
    var indx_ico = document.createElement('img');
    indx_ico.src = DOKU_BASE + 'lib/plugins/indexmenu/images/indexmenu_toolbar.png';
    var indx_btn = document.createElement('button');
    indx_btn.id = 'syntax_plugin_indexmenu';
    indx_btn.className = 'toolbutton';
    indx_btn.title = 'Insert the Indexmenu tree';
    indx_btn.appendChild(indx_ico);
    return indx_btn;
}

function indexmenu_opts(m) {
    var i,v = '';
    var f=$('indexmenu_optfrm');
    for (i=0; i < f.length; i++) {
	if (f[i].checked) {
	    v = v + ' ' + f[i].value;
	}
    }
    insertTags(
	       'wiki__text',
	       '{{indexmenu>',
	       ((m||v)?'|':'')+m.replace(/#default$/,'')+v+'}}',
	       '#1'
	       );
    $('picker_plugin_indexmenu').style.display='none';
    return false;
}

function indexmenu_insertTags(lnk,sep) {
    var r,l=lnk;
    if (sep) {
	r=new RegExp (sep,"g");
	l=lnk.replace(r,':');
    }
    insertTags('wiki__text','[[',']]',l);
}

function indexmenu_toolFrame(parent,txt) {
    f=document.createElement('fieldset');
    l=document.createElement('legend');
    l.innerHTML='<strong>'+txt+'</strong>';
    f.appendChild(l);
    parent.appendChild(f);
    return f;
}

indexmenu_toolbar_additions();
