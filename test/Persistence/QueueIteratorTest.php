<?php
/**  
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *               
 * 
 */

use oat\oatbox\service\ServiceManager;
use oat\oatbox\service\ServiceNotFoundException;
use oat\Taskqueue\Persistence\RdsQueue;
use oat\Taskqueue\Persistence\QueueIterator;
use oat\oatbox\task\Task;
use oat\Taskqueue\JsonTask;

/**
 * Class QueueIterator
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package oat\Taskqueue\Persistence
 */
class QueueIteratorTest extends PHPUnit_Framework_TestCase
{

    protected $fixtureData = [];

    /**
     * Check whether queue is initialized and load fixtures
     */
    protected function setUp()
    {
        $persistence = $this->getPersistence();
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        if (!$schema->hasTable(RdsQueue::QUEUE_TABLE_NAME)) {
            $this->markTestSkipped(
                'RdsQueue table does not exists.'
            );
        }
    }

    /**
     * Delete fixtures
     */
    public function tearDown()
    {
        $this->deleteTestData();
    }

    /**
     *
     */
    public function testNext()
    {
        $this->loadFixture();
        $iterator = $this->getIterator();
        $current = $iterator->current();
        $this->assertEquals($this->fixtureData[0][RdsQueue::QUEUE_ID], $current->getId());

        $iterator->next();
        $current = $iterator->current();
        $this->assertEquals($this->fixtureData[1][RdsQueue::QUEUE_ID], $current->getId());

        //task with index 2 is finished and should be skipped
        $iterator->next();
        $current = $iterator->current();
        $this->assertEquals($this->fixtureData[3][RdsQueue::QUEUE_ID], $current->getId());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    /**
     *
     */
    public function testValid()
    {
        $this->loadFixture();
        $iterator = $this->getIterator();
        $this->assertTrue($iterator->valid());
        $iterator->next();
        $this->assertTrue($iterator->valid());
        $iterator->next();
        $this->assertTrue($iterator->valid());
        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    /**
     *
     */
    public function testCurrent()
    {
        $this->loadFixture();
        $iterator = $this->getIterator();
        $current = $iterator->current();
        $this->assertEquals($this->fixtureData[0][RdsQueue::QUEUE_ID], $current->getId());

        $iterator->next();
        $current = $iterator->current();
        $this->assertEquals($this->fixtureData[1][RdsQueue::QUEUE_ID], $current->getId());

        //task with index 2 is finished and should be skipped
        $iterator->next();
        $current = $iterator->current();
        $this->assertEquals($this->fixtureData[3][RdsQueue::QUEUE_ID], $current->getId());

        $iterator->next();
        $current = $iterator->current();
        $this->assertEquals(null, $current);
    }

    /**
     *
     */
    public function testKey()
    {
        $this->loadFixture();
        $iterator = $this->getIterator();
        $this->assertEquals(0, $iterator->key());
        $iterator->next();
        $this->assertEquals(1, $iterator->key());
        $iterator->next();
        $this->assertEquals(2, $iterator->key());
        $iterator->next();
        $this->assertEquals(3, $iterator->key());
    }

    /**
     *
     */
    public function testRewind()
    {
        $this->loadFixture();
        $iterator = $this->getIterator();
        $iterator->next();
        $iterator->rewind();
        $this->assertEquals(0, $iterator->key());
    }

    /**
     *
     */
    public function testIteration()
    {
        $this->loadFixture();
        $iterator = $this->getIterator();
        $tasks = [];
        foreach ($iterator as $key => $task) {
            $tasks[$key] = $task;
        }
        $this->assertEquals(3, count($tasks));
        $this->assertEquals($this->fixtureData[0][RdsQueue::QUEUE_ID], $tasks[0]->getId());
        $this->assertEquals($this->fixtureData[1][RdsQueue::QUEUE_ID], $tasks[1]->getId());
        $this->assertEquals($this->fixtureData[3][RdsQueue::QUEUE_ID], $tasks[2]->getId());
    }

    /**
     * @return QueueIterator
     */
    public function testConstruct()
    {
        $this->loadFixture();
        //test __construct method with default query to make 100% test coverage
        $iterator = new QueueIterator($this->getPersistence());
        foreach ($iterator as $key => $task) {
            $tasks[$key] = $task;
        }
        $this->assertTrue(count($tasks) >= 3);
    }

    /**
     * Load fixtures to table
     */
    protected function loadFixture()
    {
        $query = 'INSERT INTO '.RdsQueue::QUEUE_TABLE_NAME.' ('
            .RdsQueue::QUEUE_ID.', '.RdsQueue::QUEUE_STATUS.', '.RdsQueue::QUEUE_ADDED.', '.RdsQueue::QUEUE_UPDATED.', '.RdsQueue::QUEUE_OWNER.', '.RdsQueue::QUEUE_TASK.') '
            .'VALUES  (?, ?, ?, ?, ?, ?)';

        $this->fixtureData = [
            [
                RdsQueue::QUEUE_ID => 'http://sample/first.rdf#i14743578724540001_test_record',
                RdsQueue::QUEUE_STATUS => Task::STATUS_CREATED,
                RdsQueue::QUEUE_ADDED => 1,
                RdsQueue::QUEUE_UPDATED => 0,
                RdsQueue::QUEUE_OWNER => 'http://sample/first.rdf#i1474293333684066',
                'invocable' => "",
                'params' => [],
            ],
            [
                RdsQueue::QUEUE_ID => 'http://sample/first.rdf#i14743578724540002_test_record',
                RdsQueue::QUEUE_STATUS => Task::STATUS_CREATED,
                RdsQueue::QUEUE_ADDED => 2,
                RdsQueue::QUEUE_UPDATED => 0,
                RdsQueue::QUEUE_OWNER => 'http://sample/first.rdf#i1474293333684066',
                'invocable' => "",
                'params' => [],
            ],
            [
                RdsQueue::QUEUE_ID => 'http://sample/first.rdf#i14743578724540003_test_record',
                RdsQueue::QUEUE_STATUS => Task::STATUS_FINISHED,
                RdsQueue::QUEUE_ADDED => 3,
                RdsQueue::QUEUE_UPDATED => 0,
                RdsQueue::QUEUE_OWNER => 'http://sample/first.rdf#i1474293333684066',
                'invocable' => "",
                'params' => [],
            ],
            [
                RdsQueue::QUEUE_ID => 'http://sample/first.rdf#i14743578724540004_test_record',
                RdsQueue::QUEUE_STATUS => Task::STATUS_CREATED,
                RdsQueue::QUEUE_ADDED => 4,
                RdsQueue::QUEUE_UPDATED => 0,
                RdsQueue::QUEUE_OWNER => 'http://sample/first.rdf#i1474293333684066',
                'invocable' => "",
                'params' => [],
            ],
        ];

        $persistence = $this->getPersistence();
        foreach ($this->fixtureData as $taskData) {
            $task = JsonTask::restore(json_encode($taskData));
            $persistence->exec($query, array(
                $taskData[RdsQueue::QUEUE_ID],
                $taskData[RdsQueue::QUEUE_STATUS],
                $taskData[RdsQueue::QUEUE_ADDED],
                $taskData[RdsQueue::QUEUE_UPDATED],
                $taskData[RdsQueue::QUEUE_OWNER],
                json_encode($task),
            ));
        }
    }

    /**
     * Clear test data before and after each test method
     * @after
     * @before
     */
    protected function deleteTestData()
    {
        $sql = 'DELETE FROM ' . RdsQueue::QUEUE_TABLE_NAME .
            ' WHERE ' . RdsQueue::QUEUE_ID . " LIKE '%_test_record'";

        $this->getPersistence()->exec($sql);
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    protected function getPersistence()
    {
        $serviceManager = ServiceManager::getServiceManager();
        try {
            $rdsQueueService = $serviceManager->get(RdsQueue::CONFIG_ID);
            if ($rdsQueueService instanceof RdsQueue) {
                $persistenceId = $rdsQueueService->getOption(RdsQueue::OPTION_PERSISTENCE);
            } else {
                $persistenceId = 'default';
            }
        } catch (ServiceNotFoundException $e) {
            $persistenceId = 'default';
        }

        $persistenceManager = $serviceManager->get(\common_persistence_Manager::SERVICE_KEY);
        return $persistenceManager->getPersistenceById($persistenceId);
    }

    /**
     * @return string
     */
    private function getQuery()
    {
        $query = 'SELECT * FROM ' . RdsQueue::QUEUE_TABLE_NAME .
            ' WHERE '.RdsQueue::QUEUE_STATUS . '=?' .
            ' AND ' . RdsQueue::QUEUE_ID . '<=?' .
            ' AND ' . RdsQueue::QUEUE_ID . '>?' .
            ' AND ' . RdsQueue::QUEUE_ID . " LIKE '%_test_record'" .
            ' ORDER BY '.RdsQueue::QUEUE_ADDED .
            ' LIMIT 1';

        return $query;
    }

    /**
     * @return QueueIterator
     */
    private function getIterator()
    {
        return new QueueIterator($this->getPersistence(), $this->getQuery());
    }
}
