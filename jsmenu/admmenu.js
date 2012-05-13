/* Right Context Menu configuration for admin users:
   Menu is built from four array items: title, link, show if page or headpage, show if namespace.
   Link is not created if it's 0, otherwise it's evaluated.
   Second array is displayed only in edit mode.

   Some usefull variables:
   node.hns = headpage id;
   node.isdir = node is namespace;
   node.dokuid = the DW id (namespace parent in case of headpage);
   id = the DW id of the selected node (headpage id in case of headpage);
   index.config.urlbase = Url Base;
   index.config.sepchar = Url separator;
*/
indexmenu_contextmenu['pg'] = {'view' : [
					 ['Edit','indexmenu_getid(index.config.urlbase,id)+"do=edit"'],
					 ['<em>Create--></em>',
					  [['Headpage','"javascript: indexmenu_reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\',\'"+node.name+"\');"','Create a new headpage under this page'],
					   ['Start page','indexmenu_getid(index.config.urlbase,id+index.config.sepchar+"start")+"do=edit"','Create a new start page under this page'],
					   ['Custom page','"javascript: indexmenu_reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\');"','Create a new page under this page'],
					   ]],
					 ['<em>More--></em>',
					  [['Acls','indexmenu_getid(index.config.urlbase,id)+"do=admin&page=acl"'],
					   ['Purge cache','indexmenu_getid(index.config.urlbase,id)+"purge=true"'],
					   ['Export as HTML','indexmenu_getid(index.config.urlbase,id)+"do=export_xhtml"'],
					   ['Export as text','indexmenu_getid(index.config.urlbase,id)+"do=export_raw"']
					   ]]
					 ]
};

indexmenu_contextmenu['ns'] = {'view' : [
					 ['New page','"javascript: indexmenu_reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\');"','Create a new page inside this namespace'],
					 ['<em>More--></em>',
					  [['Headpage here','"javascript: indexmenu_reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\',\'"+node.name+"\');"','Create a new headpage inside this namespace'],
					   ['Acls','indexmenu_getid(index.config.urlbase,node.dokuid)+"do=admin&page=acl"']
					   ]]
					 ]
};
