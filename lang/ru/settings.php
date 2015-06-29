<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 *
 * @author Takumo <9206984@mail.ru>
 */
$lang['checkupdate']           = 'Проверять обновления.';
$lang['only_admins']           = 'Разрешить использование синтаксиса indexmenu только администраторам.<br>Заметка: при редактировании страницы НЕ администратором, страница потеряет indexmenu.';
$lang['aclcache']              = 'Optimize the indexmenu cache for acl (works only for root requested namespaces).<br>The choice of the method affects only the visualization of nodes on the indexmenu tree, not the page authorizations.<ul><li>None: Standard. It is the faster method and it does not create further cache files, but the nodes with denied permission could be showed to no-authorized users or viceversa. Recommended when you don\'t deny pages access by acl or you don\'t care how the tree is displayed.<li>User: Per-User login. Slower method and it creates a lot of cache files, but it always hides correctly denied pages. Recommended when you have page acls that depend on users login.<li>Groups: Per-groups membership. Good compromise between the previous methods, but in case that you deny the read acl to a user which belongs to a group with a read acl auth, then he could anyway displays that nodes in the tree. Recommended when your whole site acls depend on groups membership.</ul>';
$lang['headpage']              = 'Headpage method: the page from which retrive the title and link of a namespace.<br>Can be any of this value:<ul><li>The global start page.<li>A page with the namespace name and that is inside it.<li>A page with the namespace name and that is at its same level.<li>A custom name page.<li>A comma separated list of page names.</ul>';
$lang['hide_headpage']         = 'Скрывать заглавные страницы.';
$lang['page_index']            = 'Страница, которая заменит основное содержание dokuwiki. Создайте её и используйте синтаксис indexmenu. Используйте <code>id#random</code>, если у вас уже есть сайдбар с включённой опцией <code>navbar</code>. Рекомендуется <code>{{indexmenu>..|js navbar nocookie id#random}}</code>.';
$lang['empty_msg']             = 'Сообщение в случае, если дерево пусто. Используйте синтаксис Dokuwiki, не используйте html. Переменная <code>{{ns}}</code> является ярлыком для запрошенного пространства имён.';
$lang['skip_index']            = 'Список пространств имён для пропуска. Используйте регулярные выражения. Например: <code>/(sidebars|private:myns)/</code>';
$lang['skip_file']             = 'Список страниц для пропуска. Используйте регулярные выражения. Например <code>/(:start$|^public:newstart$)/</code>';
$lang['show_sort']             = 'Показывать администраторам порядок сортировки наверху страницы. (При сортировке по мета тэгам)';
$lang['themes_url']            = 'Скачивать темы с этого адреса.';
$lang['be_repo']               = 'Разрешить остальным скачивать темы с вашего сайта.';
$lang['defaultoptions']        = 'Список настроек плагина, разделённый запятыми. Эти настойки будут применяться по умолчанию к каждому indexmenu и могут быть отменены с помощью команды reverse в синтаксисе плагина.';
