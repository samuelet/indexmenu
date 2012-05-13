/**
 * Javascript for index view
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */

indexmenu_nojs = {
     /**
     * Delay in ms before showing the throbber.
     * Used to skip the throbber for fast AJAX calls.
     */
    throbber_delay: 500,

    /**
     * Attach event handlers to all "folders" below the given element
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    treeattach: function(iobj){
	var obj=$('nojs_'+iobj[0]);
	if (!obj) return;

	var items = getElementsByClass('indexmenu_idx',obj,'a');
	for(var i=0; i<items.length; i++){
	    var elem = items[i];
	    
	    // attach action to make the link clickable by AJAX
	    addEvent(elem,'click',function(e){ return indexmenu_nojs.toggle(e,this,iobj[1]); });
	}
    },

    /**
     * Open or close a subtree using AJAX
     * The contents of subtrees are "cached" untill the page is reloaded.
     * A "loading" indicator is shown only when the AJAX call is slow.
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @author Ben Coburn <btcoburn@silicodon.net>
     * Modified by Samuele Tognini <samuele@netsons.org> for the indexmenu plugin
     */
    toggle: function(e,clicky,jsajax){
        var listitem = clicky.parentNode.parentNode;

        // if already open, close by removing the sublist
        var sublists = listitem.getElementsByTagName('ul');
        if(sublists.length && listitem.className=='open'){
            sublists[0].style.display='none';
            listitem.className='closed';
            e.preventDefault();
            return false;
        }

        // just show if already loaded
        if(sublists.length && listitem.className=='closed'){
            sublists[0].style.display='';
            listitem.className='open';
            e.preventDefault();
            return false;
        }

        // prepare an AJAX call to fetch the subtree
	var ajax = new sack(DOKU_BASE+'lib/plugins/indexmenu/ajax.php');
        ajax.AjaxFailedAlert = '';
        ajax.encodeURIString = false;
        if(ajax.failed) return true;

        //prepare the new ul
        var ul = document.createElement('ul');
        ul.className = 'idx';
        timeout = window.setTimeout(function(){
            // show the throbber as needed
            ul.innerHTML = '<li><img src="'+DOKU_BASE+'lib/images/throbber.gif" alt="loading..." title="loading..." /></li>';
            listitem.appendChild(ul);
            listitem.className='open';
        }, this.throbber_delay);
        ajax.elementObj = ul;
        ajax.afterCompletion = function(){
            window.clearTimeout(timeout);
            indexmenu_nojs.treeattach(ul);
            if (listitem.className!='open') {
                listitem.appendChild(ul);
                listitem.className='open';
            }
        };
        ajax.runAJAX(encodeURI('req=index&nojs=1&'+clicky.search.substr(1)+'&max=1'+decodeURIComponent(jsajax)));
        e.preventDefault();
        return false;
    },

    /* Find all nojs indexmenu objects */
    treefind: function () {
	var aobj=indexmenu_nojsqueue;
	if (!aobj) return;

	for (var i in aobj) { 
	    indexmenu_nojs.treeattach(aobj[i]);
	}
    }
};

indexmenu_nojs.treefind();
