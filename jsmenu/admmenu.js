/* Right Context Menu configuration for admin users:
   Menu is built from four array items: title, link, show if page or headpage, show if namespace.
   Link is not created if it's 0, otherwise it's evaluated.
   Second array is displayed only in edit mode.

   Some usefull variables:
   node.hns = headpage id;
   node.isdir = node is namespace;
   node.dokuid = the DW id (namespace parent in case of headpage);
   id = the DW id of the selected node (headpage id in case of headpage);
   this.config.urlbase = Url Base;
   this.config.sepchar = Url separator;
*/

var indexmenu_contextmenu=new Array(
				    //Standard right menu
				    new Array(
					      '<b><em>Page action:</em></b>',0,1,0,
					      '<b><em>Namespace action:</em></b>',0,0,1,
					      'New page here','"javascript: indexmenu_reqpage(\'"+this.config.urlbase+"\',\'"+this.config.sepchar+"\',\'"+node.dokuid+"\');"',1,1,
					      'Headpage here','"javascript: indexmenu_reqpage(\'"+this.config.urlbase+"\',\'"+this.config.sepchar+"\',\'"+node.dokuid+"\',\'"+node.name+"\');"',0,1,
					      'Edit','indexmenu_getid(this.config.urlbase,id)+"do=edit"',1,0,
					      'Search','"javascript: indexmenu_srchpage(\'"+this.config.urlbase+"\',\'"+this.config.sepchar+"\',\'"+node.isdir+"\',\'"+node.dokuid+"\');"',1,1,
					      'Toc preview','"javascript: indexmenu_createTocMenu(\'req=toc&id="+id+"\',\'picker_"+this.obj+"\',\'s"+this.obj+node.id+"\');"',1,0,
					      'Revisions','indexmenu_getid(this.config.urlbase,id)+"do=revisions"',1,0,
					      'Purge cache','indexmenu_getid(this.config.urlbase,id)+"purge=true"',1,0,
					      'Acls','indexmenu_getid(this.config.urlbase,id)+"do=admin&page=acl"',1,1
					      ),

				    //Right menu in edit mode.
				    new Array(
					      '<b><em>Edit action:</em></b>',0,1,0,
					      'Insert as DWlink','"javascript: indexmenu_insertTags(\'"+id+"\',\'"+this.config.sepchar+"\');"+this.obj+".divdisplay(\'r\',0);"',1,0
					      )
				    );

/*Custom User Functions
Insert your custom functions here.
*/
function indexmenu_reqpage(b,s,id,n) {
    var r,u=b;
    if (n) {
	r = id + s + n;
    } else {
	r = prompt("Insert the pagename to create","");
	if (!r) {return;}
	r = id + s + r;
    }
    if (r) window.location.href = indexmenu_getid(u,r)+"do=edit";
}

function indexmenu_srchpage(u,s,isdir,nid) {
    var r = prompt("Insert keyword(s) to search for within this namespace","");
    if (r)
        {
	    var fnid = nid;
	    if (isdir == "0") {
		fnid = fnid.substring(0,nid.lastIndexOf(s));
	    }
	    var b=u,re = new RegExp(s, 'g');
	    fnid = fnid.replace(re, ":");
	    b += (u.indexOf("?id=") < 0) ? '?id=': '';
	    window.location.href = indexmenu_getid(b,r+" @"+fnid)+"do=search";
	}
}

function indexmenu_getid(u,id) {
    var url=(u||'')+encodeURI(id||'');
    url += (u.indexOf("?") < 0) ? '?': '&';
    return url;
}
