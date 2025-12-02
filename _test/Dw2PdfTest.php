<?php

namespace dokuwiki\plugin\indexmenu\test;

use DokuWikiTest;

/**
 * dw2pdf tests for the indexmenu plugin
 *
 * @group plugin_indexmenu
 * @group plugins
 */
class Dw2PdfTest extends DokuWikiTest
{
    /** @inheritdoc **/
    protected $pluginsEnabled = ['indexmenu'];

    /**
     * Test sorting of pages in dw2pdf export
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testSorting()
    {
        $pages = [
            [
                'id' => 'en:admin-guides:latest_release:getting_started:actions:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 2930,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:display_the_id_of_column_and_set:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 809,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:help_search:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 404,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:hot_keys:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 2717,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:how_to_navigate_in_datawalk_system:external_links:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 986,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:how_to_navigate_in_datawalk_system:start_page:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 978,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:how_to_navigate_in_datawalk_system:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 958,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:integration-with-external-systems-by-injecting-javascript:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 1766,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:system_architecture:production_sizing_page',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 9805,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:system_architecture:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 14474,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:workstation:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 1381,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 4469,
            ],
        ];

        $tags = [
            'en:admin-guides:latest_release:getting_started:actions:start' => 10,
            'en:admin-guides:latest_release:getting_started:display_the_id_of_column_and_set:start' => 9,
            'en:admin-guides:latest_release:getting_started:help_search:start' => 3,
            'en:admin-guides:latest_release:getting_started:hot_keys:start' => 9,
            'en:admin-guides:latest_release:getting_started:how_to_navigate_in_datawalk_system:external_links:start' => 2,
            'en:admin-guides:latest_release:getting_started:how_to_navigate_in_datawalk_system:start_page:start' => 1,
            'en:admin-guides:latest_release:getting_started:how_to_navigate_in_datawalk_system:start' => 2,
            'en:admin-guides:latest_release:getting_started:integration-with-external-systems-by-injecting-javascript:start' => 210,
            'en:admin-guides:latest_release:getting_started:system_architecture:production_sizing_page' => 1,
            'en:admin-guides:latest_release:getting_started:system_architecture:start' => 6,
            'en:admin-guides:latest_release:getting_started:workstation:start' => 1,
            'en:admin-guides:latest_release:getting_started:start' => 1,
        ];

        $expected = [
            [
                'id' => 'en:admin-guides:latest_release:getting_started:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 4469,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:workstation:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 1381,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:how_to_navigate_in_datawalk_system:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 958,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:how_to_navigate_in_datawalk_system:start_page:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 978,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:how_to_navigate_in_datawalk_system:external_links:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 986,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:help_search:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 404,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:system_architecture:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 14474,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:system_architecture:production_sizing_page',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 9805,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:display_the_id_of_column_and_set:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 809,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:hot_keys:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 2717,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:actions:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 2930,
            ],
            [
                'id' => 'en:admin-guides:latest_release:getting_started:integration-with-external-systems-by-injecting-javascript:start',
                'rev' => 1769524502,
                'mtime' => 1769524502,
                'size' => 1766,
            ],
        ];

        $action = plugin_load('action', 'indexmenu_dw2pdf');
        self::setInaccessibleProperty($action, 'tags', $tags);


        usort($pages, [$action, 'cbIndexmenuSort']);

        $this->assertSame($expected, $pages);
    }
}
