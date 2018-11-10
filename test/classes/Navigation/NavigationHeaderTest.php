<?php
/**
 * Created by PhpStorm.
 * User: lkopo
 * Date: 10/11/2018
 * Time: 19:49
 */

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Navigation\NavigationHeader;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PhpMyAdmin\Navigation\NavigationHeader class
 *
 * @package PhpMyAdmin-test
 * @group navigation
 */
class NavigationHeaderTest extends PmaTestCase
{
    /**
     * @var NavigationHeader
     */
    protected $object;

    /**
     * Sets up the fixture.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['server'] = 1;

        $GLOBALS['cfg']['NavigationDisplayServers'] = true;
        $GLOBALS['cfg']['Server']['auth_type'] = 'http';
        $GLOBALS['cfg']['Servers'] = [];

        $GLOBALS['cfg']['NavigationLinkWithMainPanel'] = true;
        $GLOBALS['cfg']['NavigationTreePointerEnable'] = true;
        $GLOBALS['cfg']['NavigationTreePointerEnable'] = true;

        $GLOBALS['pmaThemeImage'] = 'image';

        $this->object = new NavigationHeader();
    }

    /**
     * Tears down the fixture.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    public function testGetDisplay()
    {
        $result = $this->object->getDisplay();

        $this->assertContains(
            '<div id="pma_navigation">'
            . '<div id="pma_navigation_resizer"></div>'
            . '<div id="pma_navigation_collapser"></div>'
            . '<div id="pma_navigation_content">'
            . '<div id="pma_navigation_header">', $result
        );

        $this->assertContains('<div id="pma_navigation_tree" class="list_container synced highlight autoexpand">', $result);
    }
}