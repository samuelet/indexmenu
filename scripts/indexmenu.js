/*--------------------------------------------------------|
 | dTree 2.05 | www.destroydrop.com/javascript/tree/      |
 |--------------------------------------------------------|
 | Copyright (c) 2002-2003 Geir Landro                    |
 |                                                        |
 | This script can be used freely as long as all          |
 | copyright messages are intact.                         |
 |                                                        |
 | Updated: 17.04.2003                                    |
 |--------------------------------------------------------|
 | Modified for Dokuwiki by                               |
 | Samuele Tognini <samuele@samuele.netsons.org>          |
 | under GPL 2 license                                    |
 | (http://www.gnu.org/licenses/gpl.html)                 |
 | Updated: 29.08.2009                                    |
 |--------------------------------------------------------|
 | Modified for Dokuwiki by                               |
 | Rene Hadler <rene.hadler@iteas.at>                     |
 | under GPL 2 license                                    |
 | (http://www.gnu.org/licenses/gpl.html)                 | 
 | Updated: 07.08.2012                                    |
 |--------------------------------------------------------|
 | jQuery update - 27 02 2012                             |
 | Gerrit Uitslag <klapinklapin@gmail.com                 |
 |--------------------------------------------------------|
 | indexmenu  | https://www.dokuwiki.org/plugin:indexmenu |
 |-------------------------------------------------------*/

// Node object
function Node(dokuid, id, pid, name, hns, isdir, ajax) {
    this.dokuid = dokuid;
    this.id = id;
    this.pid = pid;
    this.name = name;
    this.hns = hns;
    this.isdir = isdir;
    this.ajax = ajax;
    this._io = 0;       //is node open
    this._is = false;
    this._ls = false;
    this._hc = ajax;
    this._ai = 0;
    this._p = false;
    this._lv = 0;
    this._ok = false;
    this._cp = false;
}
// Tree object
/** @constructor */
function dTree(objName, theme) {
    var objExt = indexmenu_findExt(theme);
    this.config = {
        urlbase: DOKU_BASE + 'doku.php?id=',
        plugbase: DOKU_BASE + 'lib/plugins/indexmenu',
        useCookies: true,
        scroll: true,
        toc: true,
        maxjs: 1,
        jsajax: '',
        sepchar: ':',
        theme: theme
    };
    var objImg = this.config.plugbase + '/images/' + theme + '/';
    this.icon = {
        root: objImg + 'base.' + objExt,
        folder: objImg + 'folder.' + objExt,
        folderH: objImg + 'folderh.' + objExt,
        folderOpen: objImg + 'folderopen.' + objExt,
        folderHOpen: objImg + 'folderhopen.' + objExt,
        node: objImg + 'page.' + objExt,
        empty: objImg + 'empty.' + objExt,
        line: objImg + 'line.' + objExt,
        join: objImg + 'join.' + objExt,
        joinBottom: objImg + 'joinbottom.' + objExt,
        plus: objImg + 'plus.' + objExt,
        plusBottom: objImg + 'plusbottom.' + objExt,
        minus: objImg + 'minus.' + objExt,
        minusBottom: objImg + 'minusbottom.' + objExt,
        nlPlus: objImg + 'nolines_plus.' + objExt,
        nlMinus: objImg + 'nolines_minus.' + objExt
    };
    this.obj = objName;
    this.aNodes = [];
    this.aIndent = [];
    this.root = new Node(false, -1);
    this.selectedNode = null;
    this.selectedFound = false;
    this.completed = false;
    this.scrllTmr = 0;
    this.pageid = JSINFO.id || '';
    this.fajax = false;
}

// Adds a new node to the node array
dTree.prototype.add = function (dokuid, id, pid, name, hns, isdir, ajax) {
    this.aNodes[this.aNodes.length] = new Node(dokuid, id, pid, name, hns, isdir, ajax);
};

// Open/close all nodes
dTree.prototype.openAll = function () {
    if (!this.getCookie('co' + this.obj)) {
        this.oAll(true);
    }
};

// Outputs the tree to the page
dTree.prototype.toString = function () {
    var str = '';
    this.pageid = this.pageid.replace(/:/g,this.config.sepchar);
    if (this.config.scroll) {
        str += '<div id="cdtree_' + this.obj + '" class="dtree" style="position:relative;overflow:hidden;width:100%;">';
    }
    str += '<div id="dtree_' + this.obj + '" class="dtree ' + this.config.theme + '" style="overflow:';
    if (this.config.scroll) {
        str += 'visible;position:relative;width:100%"';
    } else {
        str += 'hidden;"';
    }
    str += '>';
	if (jQuery('#dtree_' + this.obj)[0]) {
        str += '<div class="error">Indexmenu id conflict</div>';
    }
    if (this.config.toc) {
        str += '<div id="t' + this.obj + '" class="indexmenu_tocbullet ' + this.config.theme + '" style="display:none;" title="Table of contents"></div>';
        str += '<div id="toc_' + this.obj + '" style="display:none;"></div>';
    }
    if (this.config.useCookies) {
        this.selectedNode = this.getSelected();
    }
    str += this.addNode(this.root) + '</div>';
    if (this.config.scroll) {
        str += '<div id="z' + this.obj + '" class="indexmenu_rarrow"></div>';
        str += '<div id="left_' + this.obj + '" class="indexmenu_larrow" style="display:none;" title="Click to scroll back" onmousedown="javascript:' + this.obj + '.scroll(\'r\',1);" onmouseup="javascript:' + this.obj + '.stopscroll();"></div>';
        str += '</div>';
    }
    this.completed = true;
    //hide the fallback nojs indexmenu
    jQuery('#nojs_' + this.obj).css("display", "none"); //using  .hide(); let's  crash opera
    return str;
};

// Creates the tree structure
dTree.prototype.addNode = function (pNode) {
    var str = '', cn, n = pNode._ai, l = pNode._lv + 1;
    for (n; n < this.aNodes.length; n++) {
        if (this.aNodes[n].pid == pNode.id) {
            cn = this.aNodes[n];
            cn._p = pNode;
            cn._ai = n;
            cn._lv = l;
            this.setCS(cn);
            if (cn._hc && !cn._io && this.config.useCookies) {
                cn._io = this.isOpen(cn.id);
            }
            if (this.pageid == (!cn.hns && cn.dokuid || cn.hns)) {
                cn._cp = true;
            } else if (cn.id == this.selectedNode && !this.selectedFound) {
                cn._is = true;
                this.selectedNode = n;
                this.selectedFound = true;
            }
            if (!cn._hc && cn.isdir && !cn.ajax && !cn.hns) {
                if (cn._ls) {
                    str += this.noderr(cn, n);
                }
            } else {
                str += this.node(cn, n);
            }
            if (cn._ls) {
                break;
            }
        }
    }
    return str;
};

dTree.prototype.noderr = function (node, nodeId) {
    var str = '<div class="dTreeNode">' + this.indent(node, nodeId);
    str += '<div class="emptynode" title="Empty"></div></div>';
    return str;
};

// Creates the node icon, url and text
dTree.prototype.node = function (node, nodeId) {
    var h = 1, jsfnc, str;
    jsfnc = 'onmouseover="' + this.obj + '.show_feat(\'' + nodeId + '\');" onmousedown="return indexmenu_checkcontextm(\'' + nodeId + '\',' + this.obj + ',event);" oncontextmenu="return indexmenu_stopevt(event)"';
    if (node._lv > this.config.maxjs) {
        h = 0;
    } else {
        node._ok = true;
    }
    str = '<div class="dTreeNode">' + this.indent(node, nodeId);
    node.icon = (this.root.id == node.pid) ? this.icon.root : ((node.hns) ? this.icon.folderH : ((node._hc) ? this.icon.folder : this.icon.node));
    node.iconOpen = (node._hc) ? ((node.hns) ? this.icon.folderHOpen : this.icon.folderOpen) : this.icon.node;
    if (this.root.id == node.pid) {
        node.icon = this.icon.root;
        node.iconOpen = this.icon.root;
    }
    str += '<img id="i' + this.obj + nodeId + '" src="' + ((node._io) ? node.iconOpen : node.icon) + '" alt="" />';
    if (!node._hc || node.hns) {
        str += '<a id="s' + this.obj + nodeId + '" class="' + ((node._cp) ? 'navSel' : ((node._is) ? 'nodeSel' : (node._hc) ? 'nodeFdUrl' : 'nodeUrl'));
        str += '" href="' + this.config.urlbase;
        (node.hns) ? str += node.hns : str += node.dokuid;
        str += '"' + ' title="' + node.name + '"' + jsfnc;
        str += ' onclick="javascript: ' + this.obj + '.s(' + nodeId + ');"';
        str += '>' + node.name + '</a>';
    }
    else if (node.pid != this.root.id) {
        str += '<a id="s' + this.obj + nodeId + '" href="javascript: ' + this.obj + '.o(' + nodeId + '); " class="node"' + jsfnc + '>' + node.name + '</a>';
    } else {
        str += node.name;
    }
    str += '</div>';
    if (node._hc) {
        str += '<div id="d' + this.obj + nodeId + '" class="clip" style="display:' + ((this.root.id == node.pid || node._io) ? 'block' : 'none') + ';">';
        if (h) {
            str += this.addNode(node);
        }
        str += '</div>';
    }
    this.aIndent.pop();
    return str;
};

// Adds the empty and line icons
dTree.prototype.indent = function (node, nodeId) {
    var n, str = '';
    if (this.root.id != node.pid) {
        for (n = 0; n < this.aIndent.length; n++) {
            str += '<img src="' + ( (this.aIndent[n] == 1) ? this.icon.line : this.icon.empty ) + '" alt="" />';
        }
        if (node._ls) {
            this.aIndent.push(0);
        } else {
            this.aIndent.push(1);
        }
        if (node._hc) {
            str += '<a href="javascript: ' + this.obj + '.o(' + nodeId + ');"><img id="j' + this.obj + nodeId + '" src="';
            str += ( (node._io) ? ((node._ls) ? this.icon.minusBottom : this.icon.minus) : ((node._ls) ? this.icon.plusBottom : this.icon.plus ) );
            str += '" alt="" /></a>';
        } else {
            str += '<img src="' + ((node._ls) ? this.icon.joinBottom : this.icon.join) + '" alt="" />';
        }
    }
    return str;
};

// Checks if a node has any children and if it is the last sibling
dTree.prototype.setCS = function (node) {
    var lastId, n;
    for (n = 0; n < this.aNodes.length; n++) {
        if (this.aNodes[n].pid == node.id) {
            node._hc = true;
        }
        if (this.aNodes[n].pid == node.pid) {
            lastId = this.aNodes[n].id;
        }
    }
    if (lastId == node.id) {
        node._ls = true;
    }
};

// Returns the selected node
dTree.prototype.getSelected = function () {
    var sn = this.getCookie('cs' + this.obj);
    return (sn) ? sn : null;
};

// Highlights the selected node
dTree.prototype.s = function (id) {
    var eOld, eNew, cn = this.aNodes[id];
    if (this.selectedNode != id) {
        eNew = jQuery("#s" + this.obj + id)[0];
        if (!eNew) {
            return;
        }
        if (this.selectedNode || this.selectedNode === 0) {
            eOld = jQuery("#s" + this.obj + this.selectedNode)[0];
            eOld.className = "node";
        }
        eNew.className = "nodeSel";
        this.selectedNode = id;
        if (this.config.useCookies) {
            this.setCookie('cs' + this.obj, cn.id);
        }
    }
};

// Toggle Open or close
dTree.prototype.o = function (id) {
    var cn = this.aNodes[id];
    this.nodeStatus(!cn._io, id, cn._ls);
    cn._io = !cn._io;
    if (this.config.useCookies) {
        this.updateCookie();
    }
    this.divdisplay('z', 0);
    this.resizescroll("block");
};

// Open or close all nodes
dTree.prototype.oAll = function (status) {
    for (var n = 0; n < this.aNodes.length; n++) {
        if (this.aNodes[n]._hc && this.aNodes[n].pid != this.root.id) {
            this.nodeStatus(status, n, this.aNodes[n]._ls);
            this.aNodes[n]._io = status;
        }
    }
    if (this.config.useCookies) {
        this.updateCookie();
    }
};

// Opens the tree to a specific node
dTree.prototype.openTo = function (nId, bSelect, bFirst) {
    var n, cn;
    if (!bFirst) {
        for (n = 0; n < this.aNodes.length; n++) {
            if (this.aNodes[n].id == nId) {
                nId = n;
                break;
            }
        }
    }
    this.fill(this.aNodes[nId].pid);
    cn = this.aNodes[nId];
    if (cn.pid == this.root.id || !cn._p) {
        return;
    }
    cn._io = 1;
    if (this.completed && cn._hc) {
        this.nodeStatus(true, cn._ai, cn._ls);
    }
    if (cn._is) {
        (this.completed) ? this.s(cn._ai) : this._sn = cn._ai;
    }
    this.openTo(cn._p._ai, false, true);
};

dTree.prototype.getOpenTo = function (nodes) {
    if (nodes === '') {
        this.openAll();
    } else if (!this.config.useCookies || !this.getCookie('co' + this.obj)) {
        for (var n = 0; n < nodes.length; n++) {
            this.openTo(nodes[n], false, true);
        }
    }
};

/**
 * Change the status of a node(open or closed)
 *
 * @param status true if open
 * @param id     Node id
 * @param bottom true if bottom node
 */
dTree.prototype.nodeStatus = function (status, id, bottom) {
    if (status && !this.fill(id)) {
        return;
    }
    var eJoin, eIcon;
	eJoin = jQuery('#j' + this.obj + id)[0];
	eIcon = jQuery('#i' + this.obj + id)[0];
    eIcon.src = (status) ? this.aNodes[id].iconOpen : this.aNodes[id].icon;
    eJoin.src = ((status) ? ((bottom) ? this.icon.minusBottom : this.icon.minus) : ((bottom) ? this.icon.plusBottom : this.icon.plus));
    jQuery('#d' + this.obj + id)[0].style.display = (status) ? 'block' : 'none';
};

// [Cookie] Clears a cookie
dTree.prototype.clearCookie = function () {
    var now, yday;
    now = new Date();
    yday = new Date(now.getTime() - 1000 * 60 * 60 * 24);
    this.setCookie('co' + this.obj, 'cookieValue', yday);
    this.setCookie('cs' + this.obj, 'cookieValue', yday);
};

// [Cookie] Sets value in a cookie
dTree.prototype.setCookie = function (cookieName, cookieValue, expires, path, domain, secure) {
    document.cookie =
        encodeURIComponent(cookieName) + '=' + encodeURIComponent(cookieValue) +
            (expires ? '; expires=' + expires.toGMTString() : '') +
            ';path=/' + (domain ? '; domain=' + domain : '') +
            (secure ? '; secure' : '');
};

// [Cookie] Gets a value from a cookie
dTree.prototype.getCookie = function (cookieName) {
    var cookieValue = '', pN, posValue, endPos;
    pN = document.cookie.indexOf(encodeURIComponent(cookieName) + '=');
    if (pN != -1) {
        posValue = pN + (encodeURIComponent(cookieName) + '=').length;
        endPos = document.cookie.indexOf(';', posValue);
        if (endPos != -1) {
            cookieValue = decodeURIComponent(document.cookie.substring(posValue, endPos));
        }
        else {
            cookieValue = decodeURIComponent(document.cookie.substring(posValue));
        }
    }
    return (cookieValue);
};

// [Cookie] Returns ids of open nodes as a string
dTree.prototype.updateCookie = function () {
    var str = '', n;
    for (n = 0; n < this.aNodes.length; n++) {
        if (this.aNodes[n]._io && this.aNodes[n].pid != this.root.id) {
            if (str) {
                str += '.';
            }
            str += this.aNodes[n].id;
        }
    }
    this.setCookie('co' + this.obj, str);
};

/**
 * [Cookie] Checks if a node id is in a cookie
 *
 * @param {int} id Node id
 * @return {Boolean} if open true
 */
dTree.prototype.isOpen = function (id) {
    var n, aOpen = this.getCookie('co' + this.obj).split('.');
    for (n = 0; n < aOpen.length; n++) {
        if (aOpen[n] == id) {
            return true;
        }
    }
    return false;
};

dTree.prototype.openCurNS = function (max) {
    var r, cn, match, t, i, n, cnsa, cna;
    cns = this.pageid;
    r = new RegExp("\\b" + this.config.sepchar + "\\b", "g");
    match = cns.match(r) || -1;
    if (max > 0 && match.length >= max) {
        t = cns.split(this.config.sepchar);
        n = (this.aNodes[0].dokuid == '') ? 0 : this.aNodes[0].dokuid.split(this.config.sepchar).length;
        t.splice(max + n, t.length);
        cnsa = t.join(this.config.sepchar);
    }
    for (i = 0; i < this.aNodes.length; i++) {
        cn = this.aNodes[i];
        if (cns == cn.dokuid || cns == cn.hns) {
            this.openTo(cn.id, false, true);
            this.fajax = false;
            if (cn.pid >= 0) {
				jQuery(this.scroll("l", 4, cn.pid, 1));
            }
            break;
        }
        if (cnsa == cn.dokuid || cnsa == cn.hns) {
            cna = cn;
            this.fajax = true;
        }
    }
    if (cna) {
        this.openTo(cna.id, false, true);
    }
};

dTree.prototype.fill = function (id) {
    if (id == -1 || this.aNodes[id]._ok) {
        return true;
    }
    var n = id, $eLoad, a, rd, ln, eDiv;
    if (this.aNodes[n].ajax) {
        $eLoad = jQuery('#l' + this.obj);
        if (!$eLoad.length) {
            $eLoad = indexmenu_createPicker('l' + this.obj);
        }
        jQuery('#s' + this.obj + n).parent().append($eLoad);
        $eLoad
            .html('Loading ...')
            .css({width: 'auto'})
            .show();
        this.getAjax(n);
        return true;
    }
    rd = [];
    while (!this.aNodes[n]._ok) {
        rd[rd.length] = n;
        n = this.aNodes[n].pid;
    }
    for (ln = rd.length - 1; ln >= 0; ln--) {
        id = rd[ln];
        a = this.aNodes[id];
		eDiv = jQuery('#d' + this.obj + id)[0];
        if (!eDiv) {
            return false;
        }
        this.aIndent = [];
        n = a;
        while (n.pid >= 0) {
            if (n._ls) {
                this.aIndent.unshift(0);
            } else {
                this.aIndent.unshift(1);
            }
            n = n._p;
        }
        eDiv.innerHTML = this.addNode(a);
        a._ok = true;
    }
    return true;
};

dTree.prototype.openCookies = function () {
    var n, cn, aOpen = this.getCookie('co' + this.obj).split('.');
    for (n = 0; n < aOpen.length; n++) {
        if (aOpen[n] === "") {
            break;
        }
        cn = this.aNodes[aOpen[n]];
        if (!cn._ok) {
            this.nodeStatus(true, aOpen[n], cn._ls);
            cn._io = 1;
        }
    }
};

dTree.prototype.scroll = function (where, s, n, i) {
    if (!this.config.scroll) {
        return false;
    }
    var w, dtree, dtreel, nodeId;
	dtree = jQuery('#dtree_' + this.obj)[0];
    dtreel = parseInt(dtree.offsetLeft, 0);
    if (where == "r") {
        jQuery('#left_' + this.obj)[0].style.border = "thin inset";
        this.scrollRight(dtreel, s);
    } else {
        nodeId = jQuery('#s' + this.obj + n)[0];
        if (nodeId == null) {
            return false;
        }
        w = parseInt(dtree.parentNode.offsetWidth - nodeId.offsetWidth - nodeId.offsetLeft, 0);
        if (this.config.toc) {
            w = w - 11;
        }
        if (dtreel <= w) {
            return;
        }
        this.resizescroll("none");
        this.stopscroll();
        this.scrollLeft(dtreel, s, w - 3, i);
    }
};

dTree.prototype.scrollLeft = function (lft, s, w, i) {
    if (lft < w - i - 10) {
        this.divdisplay('z', 0);
        this.scrllTmr = 0;
        return;
    }
    var self = this;
    jQuery('#dtree_' + self.obj)[0].style.left = lft + "px";
    this.scrllTmr = setTimeout(function () {
        self.scrollLeft(lft - s, s + i, w, i);
    }, 20);
};

//Scroll Back
dTree.prototype.scrollRight = function (lft, s) {
    if (lft >= s) {
        this.divdisplay('left_', 0);
        this.stopscroll();
        return;
    }
    var self = this;
    jQuery('#dtree_' + self.obj)[0].style.left = lft + "px";
    if (lft > -15) {
        s = 1;
    }
    this.scrllTmr = setTimeout(function () {
        self.scrollRight(lft + s, s + 1);
    }, 20);
};

dTree.prototype.stopscroll = function () {
    jQuery('#left_' + this.obj)[0].style.border = "none";
    clearTimeout(this.scrllTmr);
    this.scrllTmr = 0;
};

dTree.prototype.show_feat = function (n) {
	var w, div, id, dtree, dtreel, self, node = jQuery('#s' + this.obj + n)[0];
    self = this;
    if (this.config.toc && node.className != "node") {
		div = jQuery('#t' + this.obj)[0];
        id = (this.aNodes[n].hns) ? this.aNodes[n].hns : this.aNodes[n].dokuid;
        div.onmousedown = function () {
            indexmenu_createTocMenu('call=indexmenu&req=toc&id=' + decodeURIComponent(id), 'picker_' + self.obj, 't' + self.obj);
        };
        node.parentNode.appendChild(div);
        if (div.style.display == "none") {
            div.style.display = "inline";
        }
    }
    if (this.config.scroll) {
		div = jQuery('#z' + this.obj)[0];
        div.onmouseover = function () {
            div.style.border = "none";
            self.scroll("l", 1, n, 0);
        };
        div.onmousedown = function () {
            div.style.border = "thin inset";
            self.scroll("l", 4, n, 1);
        };
        div.onmouseout = function () {
            div.style.border = "none";
            self.stopscroll();
        };
        div.onmouseup = div.onmouseover;
		dtree = jQuery('#dtree_' + this.obj)[0];
        dtreel = parseInt(dtree.offsetLeft, 0);
        w = parseInt(dtree.parentNode.offsetWidth - node.offsetWidth - node.offsetLeft + 1, 0);
        if (dtreel > w) {
            div.style.display = "none";
            div.style.top = node.offsetTop + "px";
            div.style.left = parseInt(node.offsetLeft + node.offsetWidth + w - 12, 0) + "px";
            div.style.display = "block";
        }
    }
};

dTree.prototype.resizescroll = function (status) {
	var dtree, w, h, left = jQuery('#left_' + this.obj)[0];
    if (!left) {
        return;
    }
    if (left.style.display == status) {
        dtree = jQuery('#dtree_' + this.obj)[0];
        w = parseInt(dtree.offsetHeight / 3, 0);
        h = parseInt(w / 50, 0) * 50;
        if (h < 50) {
            h = 50;
        }
        left.style.height = h + "px";
        left.style.top = w + "px";
        if (status == "none") {
            left.style.display = "block";
        }
    }
};

// Toggle Open or close
dTree.prototype.getAjax = function (n) {
    var node, req, curns, selft = this;
    node = selft.aNodes[n];

    req = 'call=indexmenu&req=index&idx=' + node.dokuid + decodeURIComponent(this.config.jsajax);
    curns = this.pageid.substring(0, this.pageid.lastIndexOf(this.config.sepchar));

    if (this.fajax) {
        req += '&nss=' + curns + '&max=1';
    }

    var onCompletion = function (data) {
        var i, ajxnodes, ajxnode, plus;
        plus = selft.aNodes.length - 1;
        eval(data);
        if (!ajxnodes instanceof Array || ajxnodes.length < 1) {
            ajxnodes = [
                ['', 1, 0, '', 0, 1, 0]
            ];
        }
        node.ajax = false;
        for (i = 0; i < ajxnodes.length; i++) {
            ajxnode = ajxnodes[i];
            ajxnode[2] = (ajxnode[2] == 0) ? node.id : ajxnode[2] + plus;
            ajxnode[1] += plus;
            selft.add(ajxnode[0], ajxnode[1], ajxnode[2], ajxnode[3], ajxnode[4], ajxnode[5], ajxnode[6]);
        }
        if (selft.fajax) {
            selft.fajax = false;
            selft.openCurNS(0);
        } else {
            selft.openTo(node.id, false, true);
        }
        jQuery('#l' + selft.obj).hide();
    };

    jQuery.post(
        DOKU_BASE + 'lib/exe/ajax.php',
        'call=indexmenu&'+req,
        onCompletion,
        'html'
    );
};

//Load custom css
dTree.prototype.loadCss = function () {
    var oLink = document.createElement("link");
    oLink.href = this.config.plugbase + '/images/' + this.config.theme + '/style.css';
    oLink.rel = "stylesheet";
    oLink.type = "text/css";
    document.getElementsByTagName('head')[0].appendChild(oLink);
};

//Right click
dTree.prototype.contextmenu = function (n, e) {
    var type, node, cdtree, rmenu;
    cdtree = jQuery("#cdtree_" + this.obj)[0];
	rmenu = jQuery('#r' + this.obj)[0];
    if (!rmenu) {
        return true;
    }
    indexmenu_mouseposition(rmenu, e);
    var cmenu = window.indexmenu_contextmenu;
    node = this.aNodes[n];
    rmenu.innerHTML = '<div class="indexmenu_rmenuhead" title="' + node.name + '">' + node.name + "</div>";
    rmenu.appendChild(document.createElement('ul'));
    type = (node.isdir || node._hc) ? 'ns' : 'pg';
    indexmenu_arrconcat(cmenu['all'][type], this, n);
    if (node.hns) {
        indexmenu_arrconcat(cmenu[type], this, n);
        type = 'pg';
        indexmenu_arrconcat(cmenu['all'][type], this, n);
    }
    indexmenu_arrconcat(cmenu[type], this, n);
    rmenu.style.display = 'inline';
    return false;
};

dTree.prototype.divdisplay = function (obj, v) {
	var o = jQuery('#' + obj + this.obj)[0];
    if (!o) {
        return false;
    }
    (v) ? o.style.display = 'inline' : o.style.display = 'none';
};

dTree.prototype.init = function (s, c, n, nav, max, nomenu) {
    if (s) {
        this.loadCss();
    }
    if (!c) {
        this.openCookies();
    }
    if (n) {
        this.getOpenTo(n.split(" "));
    }
    if (nav) {
        this.openCurNS(max);
    }
    if (!nomenu) {
        var self = this;
        indexmenu_createPicker('r' + this.obj, 'indexmenu_rmenu ' + this.config.theme);
        jQuery('#r' + this.obj)[0].oncontextmenu = indexmenu_stopevt;
		jQuery(document).click(function() {
            self.divdisplay('r', 0);
        });
    }
};

// If Push and pop is not implemented by the browser
if (!Array.prototype.push) {
    Array.prototype.push = function array_push() {
        for (var i = 0; i < arguments.length; i++) {
            this[this.length] = arguments[i];
        }
        return this.length;
    };
}
if (!Array.prototype.pop) {
    Array.prototype.pop = function array_pop() {
        var lstEl = this[this.length - 1];
        this.length = Math.max(this.length - 1, 0);
        return lstEl;
    };
}
