<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration\Tracker;

use Piwik\Common;
use Piwik\Db;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Tracker;

/**
 * Tracker DB test
 *
 * @group Core
 * @group TrackerDbTest
 */
class DbTest extends IntegrationTestCase
{
    private $tableName;
    public function setUp()
    {
        parent::setUp();
        $this->tableName = Common::prefixTable('option');
    }

    public function test_rowCount_whenUpdating_returnsAllMatchedRowsNotOnlyUpdatedRows()
    {
        $db = Tracker::getDatabase();
        // insert one record
        $db->query("INSERT INTO `" . Common::prefixTable('option') . "` VALUES ('rowid', '1', false)");

        // We will now UPDATE this table and check rowCount() value
        $sqlUpdate = "UPDATE `" . Common::prefixTable('option') . "` SET option_value = 2";

        // when no record was updated, return 0
        $result = $db->query($sqlUpdate . " WHERE option_name = 'NOT FOUND'");
        $this->assertSame(0, $db->rowCount($result));

        // when one record was found and updated, returns 1
        $result = $db->query($sqlUpdate . " WHERE option_name = 'rowid'");
        $this->assertSame(1, $db->rowCount($result));

        // when one record was found but NOT actually updated (as values have not changed), we make sure to return 1
        // testing for MYSQLI_CLIENT_FOUND_ROWS and MYSQL_ATTR_FOUND_ROWS
        $result = $db->query($sqlUpdate . " WHERE option_name = 'rowid'");
        $this->assertSame(1, $db->rowCount($result));
    }

    public function test_rowCount_whenInserting()
    {
        $db = Tracker::getDatabase();
        // insert one record
        $result = $this->insertRowId();

        $this->assertSame(1, $db->rowCount($result));
    }

    /**
     * @expectedExceptionMessage doesn't exist
     * @expectedException  \Piwik\Tracker\Db\DbException
     */
    public function test_fetchOne_notExistingTable()
    {
        $db = Tracker::getDatabase();
        $this->insertRowId(3);
        $val = $db->fetchOne('SELECT option_value FROM foobarbaz where option_value = "rowid"');
        $this->assertEquals('3', $val);
    }

    /**
     * @expectedExceptionMessage Duplicate entry
     * @expectedException  \Piwik\Tracker\Db\DbException
     */
    public function test_query_error_whenInsertingDuplicateRow()
    {
        $this->insertRowId();
        $this->insertRowId();
    }

    public function test_fetchOne()
    {
        $db = Tracker::getDatabase();
        $this->insertRowId(3);
        $val = $db->fetchOne('SELECT option_value FROM `' . $this->tableName . '` where option_name = "rowid"');
        $this->assertEquals('3', $val);
    }

    public function test_fetchOne_noMatch()
    {
        $db = Tracker::getDatabase();
        $val = $db->fetchOne('SELECT option_value from `' . $this->tableName . '` where option_name = "foobar"');
        $this->assertFalse($val);
    }

    public function test_fetchRow()
    {
        $db = Tracker::getDatabase();
        $this->insertRowId(3);
        $val = $db->fetchRow('SELECT option_value from `' . $this->tableName . '` where option_name = "rowid"');
        $this->assertEquals(array(
            'option_value' => '3'
        ), $val);
    }

    public function test_fetchRow_noMatch()
    {
        $db = Tracker::getDatabase();
        $val = $db->fetchRow('SELECT option_value from `' . $this->tableName . '` where option_name = "foobar"');
        $this->assertFalse($val);
    }

    public function test_fetch()
    {
        $db = Tracker::getDatabase();
        $this->insertRowId(3);
        $val = $db->fetch('SELECT option_value from `' . $this->tableName . '` where option_name = "rowid"');
        $this->assertEquals(array(
            'option_value' => '3'
        ), $val);
    }

    public function test_fetch_noMatch()
    {
        $db = Tracker::getDatabase();
        $val = $db->fetch('SELECT option_value from `' . $this->tableName . '` where option_name = "foobar"');
        $this->assertFalse($val);
    }

    public function test_fetchAll()
    {
        $db = Tracker::getDatabase();
        $this->insertRowId(3);
        $val = $db->fetchAll('SELECT option_value from `' . $this->tableName . '` where option_name = "rowid"');
        $this->assertEquals(array(
            array(
                'option_value' => '3'
            )
        ), $val);
    }

    public function test_fetchAll_noMatch()
    {
        $db = Tracker::getDatabase();
        $val = $db->fetchAll('SELECT option_value from `' . $this->tableName . '` where option_name = "foobar"');
        $this->assertEquals(array(), $val);
    }

    private function insertRowId($value = '1')
    {
        $db = Tracker::getDatabase();
        return $db->query("INSERT INTO `" . $this->tableName . "` VALUES ('rowid', '$value', false)");
    }
}
