<?php

/**
 * @license    GPL 2 (https://www.gnu.org/licenses/gpl.html)
 *
 * @author Wizzard <wizzardsk@gmail.com>
 */
$lang['checkupdate']           = 'Pravidelne kontrolovať aktualizácie.';
$lang['only_admins']           = 'Povoliť syntax indexmenu iba správcom.<br>Pozor: stránka upravená používateľom, ktorý nie je správca, stratí každý obsiahnutý strom indexmenu.';
$lang['aclcache']              = 'Optimalizovať vyrovnávaciu pamäť indexmenu pre ACL (funguje len pre menné priestory požadované z koreňa).<br>Voľba metódy ovplyvňuje len zobrazenie uzlov v strome indexmenu, nie oprávnenia stránok.<ul><li>None: Štandard. Najrýchlejšia metóda, ktorá nevytvára ďalšie súbory vyrovnávacej pamäte, ale uzly so zamietnutým oprávnením sa môžu zobraziť neoprávneným používateľom alebo naopak. Odporúča sa, keď cez ACL nezamietate prístup k stránkam alebo vám nezáleží na tom, ako sa strom zobrazí.<li>User: Podľa prihlásenia používateľa. Pomalšia metóda, ktorá vytvára veľa súborov vyrovnávacej pamäte, ale vždy správne skryje zamietnuté stránky. Odporúča sa, keď máte ACL stránok závislé od prihlásenia používateľov.<li>Groups: Podľa členstva v skupinách. Dobrý kompromis medzi predchádzajúcimi metódami, ale ak zamietnete oprávnenie na čítanie používateľovi, ktorý patrí do skupiny s oprávnením na čítanie, môže tieto uzly v strome aj tak zobraziť. Odporúča sa, keď ACL celého webu závisia od členstva v skupinách.</ul>';
$lang['headpage']              = 'Metóda úvodnej stránky: stránka, z ktorej sa získa názov a odkaz menného priestoru.<br>Môže to byť ktorákoľvek z týchto hodnôt:<ul><li>Globálna štartovacia stránka.<li>Stránka s názvom menného priestoru, ktorá sa nachádza v ňom.<li>Stránka s názvom menného priestoru, ktorá je na jeho úrovni.<li>Stránka s vlastným názvom.<li>Zoznam názvov stránok oddelený čiarkami.</ul>';
$lang['hide_headpage']         = 'Skryť úvodné stránky.';
$lang['page_index']            = 'Stránka, ktorá nahradí hlavný index DokuWiki. Vytvorte ju a vložte syntax indexmenu. Použite <code>id#random</code>, ak už máte bočný panel indexmenu s voľbou navbar. Odporúčam <code>{{indexmenu>..|js navbar nocookie id#random}}</code>.';
$lang['empty_msg']             = 'Správa, ktorá sa zobrazí, keď je strom prázdny. Použite syntax DokuWiki, nie HTML kód. Premenná <code>{{ns}}</code> je skratka pre požadovaný menný priestor.';
$lang['skip_index']            = 'ID menných priestorov, ktoré sa majú preskočiť. Použite formát regulárneho výrazu. Príklad: <code>/(sidebars|private:myns)/</code>';
$lang['skip_file']             = 'ID stránok, ktoré sa majú preskočiť. Použite formát regulárneho výrazu. Príklad <code>/(:start$|^public:newstart$)/</code>';
$lang['show_sort']             = 'Zobrazovať správcom triediace číslo indexmenu ako poznámku navrchu stránky';
$lang['themes_url']            = 'Sťahovať JS témy z tejto http adresy.';
$lang['be_repo']               = 'Umožniť ostatným sťahovať témy z vášho webu.';
$lang['defaultoptions']        = 'Zoznam volieb indexmenu oddelených medzerami. Tieto voľby sa štandardne použijú na každé indexmenu a dajú sa zrušiť obráteným príkazom v syntaxi pluginu';
