<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Navigation\NavigationHeader class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

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

    /**
     * Tests getDisplay() method.
     *
     * @return void
     */
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

    /**
     * Tests _logo() method.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testLogo()
    {
        $GLOBALS['cfg']['NavigationDisplayLogo'] = false;

        $method = $this->getMethod('_logo');

        $this->assertEquals("", $method->invoke($this->object));

        $GLOBALS['cfg']['NavigationDisplayLogo'] = true;
        $GLOBALS['cfg']['NavigationLogoLink'] = '';

        $this->assertEquals(
            "    <div id=\"pmalogo\">\n"
            . "                phpMyAdmin\n"
            . "            </div>\n",
            $method->invoke($this->object)
        );

        $GLOBALS['cfg']['NavigationLogoLink'] = 'index.php';
        $GLOBALS['cfg']['NavigationLogoLinkWindow'] = 'new';

        $this->assertContains(
            htmlentities('target="_blank" rel="noopener noreferrer"'),
            $method->invoke($this->object)
        );
    }

    /**
     * Tests _serverChoice() method.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testServerChoice()
    {
        $GLOBALS['cfg']['Servers'] = [1, 2, 3];
        $method = $this->getMethod('_serverChoice');

        $this->assertEquals(
            '<!-- SERVER CHOICE START -->'
            . '<div id="serverChoice">'
            . '<form method="post" action="index.php" class="disableAjax">'
            . '<input type="hidden" name="token" value="token" />'
            . '<label for="select_server">Current server:</label> '
            . '<select name="server" id="select_server" class="autosubmit">'
            . '<option value="">(Servers) ...</option>'
            . "\n"
            . '</select></form></div>'
            . '<!-- SERVER CHOICE END -->',
            $method->invoke($this->object)
        );
    }

    /**
     * Get inaccessible method (protected / private) and make it public using reflection.
     *
     * @param string $name Method name
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    public function getMethod($name) {
        $class = new \ReflectionClass('PhpMyAdmin\Navigation\NavigationHeader');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
}