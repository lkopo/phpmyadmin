<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Navigation\NavigationTree class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\NavigationTree;
use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Theme;

/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.inc.php' will use it globally
 */
$GLOBALS['server'] = 0;
$GLOBALS['cfg']['Server']['DisableIS'] = false;

require_once 'libraries/check_user_privileges.inc.php';

/**
 * Tests for PhpMyAdmin\Navigation\NavigationTree class
 *
 * @package PhpMyAdmin-test
 * @group navigation
 */
class NavigationTreeTest extends PmaTestCase
{
    /**
     * @var NavigationTree
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
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['user'] = 'root';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;
        $GLOBALS['cfg']['ShowDatabasesNavigationAsTree']  = true;

        $GLOBALS['cfg']['NavigationTreeDbSeparator'] = '_';
        $GLOBALS['cfg']['FirstLevelNavigationItems'] = 100;

        $GLOBALS['cfg']['NaturalOrder'] = true;

        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['db'] = 'db';

        $this->object = new NavigationTree();
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
     * Very basic rendering test.
     *
     * @return void
     */
    public function testRenderState()
    {
        $result = $this->object->renderState();
        $this->assertContains('pma_quick_warp', $result);
    }

    /**
     * Very basic path rendering test.
     *
     * @return void
     */
    public function testRenderPath()
    {
        $result = $this->object->renderPath();
        $this->assertContains('list_container', $result);
    }

    /**
     * Very basic select rendering test.
     *
     * @return void
     */
    public function testRenderDbSelect()
    {
        $result = $this->object->renderDbSelect();
        $this->assertContains('pma_navigation_select_database', $result);
    }

    /**
     * Tests _getNavigationDbPos() method with following configuration:
     * $GLOBALS['db'] = '';
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetNavigationDbPos_NoDB()
    {
        $GLOBALS['db'] = '';
        $method = $this->getMethod('_getNavigationDbPos');
        $this->assertEquals(0, $method->invoke($this->object));
    }

    /**
     * Tests _getNavigationDbPos() method with following configuration:
     * $GLOBALS['cfg']['Server']['DisableIS'] = false;
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetNavigationDbPos()
    {
        $expectedQuery = "SELECT (COUNT(DB_first_level) DIV 100) * 100 "
                        . "from ( "
                        . " SELECT distinct SUBSTRING_INDEX(SCHEMA_NAME, "
                        . " '_', 1) "
                        ." DB_first_level "
                        . " FROM INFORMATION_SCHEMA.SCHEMATA "
                        . " WHERE `SCHEMA_NAME` < 'db' "
                        .") t ";
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('fetchValue')
            ->will($this->returnValue(0))
            ->with($expectedQuery);
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $method = $this->getMethod('_getNavigationDbPos');
        $this->assertEquals(0, $method->invoke($this->object));
    }

    /**
     * Tests _getNavigationDbPos() method with following configuration:
     * $GLOBALS['cfg']['Server']['DisableIS'] = true;
     * $GLOBALS['dbs_to_test'] = false;
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetNavigationDbPos_DisableIS_True_NoDbs()
    {
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with("SHOW DATABASES")
            ->will($this->returnValue(true));
        $dbi->expects($this->once())
            ->method('fetchArray')
            ->will($this->returnValue([
                'db'
            ]));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['dbs_to_test'] = false;
        $GLOBALS['cfg']['Server']['DisableIS'] = true;

        $method = $this->getMethod('_getNavigationDbPos');
        $this->assertEquals(0, $method->invoke($this->object));
    }

    /**
     * Tests _getNavigationDbPos() method with following configuration:
     * $GLOBALS['cfg']['Server']['DisableIS'] = true;
     * $GLOBALS['dbs_to_test'] = ['information_schema', 'performance_schema', 'db'];
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetNavigationDbPos_DisableIS_True_WithDbs()
    {
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['dbs_to_test'] = [
            'information_schema', 'performance_schema', 'db'
        ];
        $GLOBALS['cfg']['Server']['DisableIS'] = true;

        $idx = 0;
        foreach ($GLOBALS['dbs_to_test'] as $db) {
            $dbi->expects($this->at($idx++))
                ->method('tryQuery')
                ->with("SHOW DATABASES LIKE '" . $db . "'")
                ->will($this->returnValue(true));
            $dbi->expects($this->at($idx++))
                ->method('fetchArray')
                ->will($this->returnValue([
                    $db
                ]));
            $dbi->expects($this->at($idx++))
                ->method('fetchArray')
                ->will($this->returnValue(false));
        }

        $method = $this->getMethod('_getNavigationDbPos');
        $this->assertEquals(0, $method->invoke($this->object));
    }

    /**
     * Tests sortNode() method.
     *
     * @return void
     */
    public function testSortNode()
    {
        $nodeA = NodeFactory::getInstance(
            'Node', 'someName'
        );

        $nodeB = NodeFactory::getInstance(
            'Node', 'anotherName'
        );

        $this->assertGreaterThanOrEqual(1, NavigationTree::sortNode($nodeA, $nodeB));

        $GLOBALS['cfg']['NaturalOrder'] = false;
        $this->assertGreaterThanOrEqual(1, NavigationTree::sortNode($nodeA, $nodeB));

        $nodeC = NodeFactory::getInstance(
            'Node', 'thirdNode'
        );
        $nodeC->isNew = true;

        $this->assertEquals(-1, NavigationTree::sortNode($nodeC, $nodeA));
        $this->assertEquals(1, NavigationTree::sortNode($nodeA, $nodeC));
    }

    /**
     * Tests _parsePath() method.
     *
     * @throws \ReflectionException
     */
    public function testParsePath()
    {
        $path_root = "root";
        $path_information_schema = "information_schema";
        $aPath = base64_encode($path_root) . "." . base64_encode($path_information_schema);

        $method = $this->getMethod('_parsePath');
        $this->assertEquals(
            [$path_root, $path_information_schema],
            $method->invokeArgs($this->object, [$aPath])
        );
    }

    /**
     * Get inaccessible method (protected / private) and make it public using reflection.
     *
     * @param $name
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    public function getMethod($name) {
        $class = new \ReflectionClass('PhpMyAdmin\Navigation\NavigationTree');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
}
