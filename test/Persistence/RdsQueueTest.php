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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 */

use oat\Taskqueue\Persistence\RdsQueue;
use oat\Taskqueue\JsonTask;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\service\ServiceNotFoundException;

/**
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class RdsQueueTest extends PHPUnit_Framework_TestCase
{
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

    public function testCreateTask()
    {
        $queue = $this->getInstance();
        $params = ['foo' => 'bar', 2, 'three'];

        $task = $queue->createTask('invocable/Action', $params);

        $taskId = $task->getId();

        $this->assertTrue($task instanceof JsonTask);
        $this->assertTrue(\common_Utils::isUri($taskId));

        $taskData = $this->getTaskData($taskId)[0];
        $this->assertEquals($taskId, $taskData[RdsQueue::QUEUE_ID]);
        $this->assertEquals(JsonTask::STATUS_CREATED, $taskData[RdsQueue::QUEUE_STATUS]);
        $this->assertRegExp('/\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}/', $taskData[RdsQueue::QUEUE_ADDED]);
        $this->assertRegExp('/\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}/', $taskData[RdsQueue::QUEUE_UPDATED]);
        $this->assertEquals(json_encode($task), $taskData[RdsQueue::QUEUE_TASK]);

        $this->deleteTask($taskId);
    }

    public function testUpdateTaskStatus()
    {
        $queue = $this->getInstance();
        $params = ['foo' => 'bar', 2, 'three'];

        $task = $queue->createTask('invocable/Action', $params);
        $taskId = $task->getId();
        $taskData = $this->getTaskData($taskId)[0];

        $this->assertEquals(JsonTask::STATUS_CREATED, $taskData[RdsQueue::QUEUE_STATUS]);

        $queue->updateTaskStatus($taskId, JsonTask::STATUS_FINISHED);

        $taskData = $this->getTaskData($taskId)[0];

        $this->assertEquals(JsonTask::STATUS_FINISHED, $taskData[RdsQueue::QUEUE_STATUS]);

        $this->deleteTask($taskId);
    }

    public function testGetIterator()
    {
        $queue = $this->getInstance();
        $iterator = $queue->getIterator();
        $this->assertTrue($iterator instanceof \common_persistence_sql_QueryIterator);
    }

    protected function deleteTask($id)
    {
        $persistence = $this->getPersistence();
        $sql = 'DELETE FROM ' . RdsQueue::QUEUE_TABLE_NAME .
            ' WHERE ' . RdsQueue::QUEUE_ID . '=?';

        $persistence->exec($sql, [$id]);
    }

    protected function getTaskData($id)
    {
        $persistence = $this->getPersistence();
        $sql = 'SELECT * FROM ' . RdsQueue::QUEUE_TABLE_NAME .
            ' WHERE ' . RdsQueue::QUEUE_ID . '=?';

        return $persistence->query($sql, [$id])->fetchAll();
    }

    /**
     * @return RdsQueue
     */
    protected function getInstance()
    {
        $serviceManager = ServiceManager::getServiceManager();
        try {
            $rdsQueueService = $serviceManager->get(RdsQueue::CONFIG_ID);
        } catch (ServiceNotFoundException $e) {
            $rdsQueueService = null;
        }
        if (!$rdsQueueService instanceof RdsQueue || $rdsQueueService === null) {
            $rdsQueueService = new RdsQueue([RdsQueue::OPTION_PERSISTENCE => 'default']);
            $rdsQueueService->setServiceManager($serviceManager);
        }
        return $rdsQueueService;
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
}
