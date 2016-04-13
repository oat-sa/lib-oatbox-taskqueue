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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *               
 * 
 */
namespace oat\Taskqueue\Persistence;

use oat\oatbox\service\ConfigurableService;
use oat\Taskqueue\JsonTask;
use oat\oatbox\task\Task;
use oat\oatbox\task\Queue;

class RdsQueue extends ConfigurableService implements \IteratorAggregate, Queue
{
    const QUEUE_TABLE_NAME = 'queue';
    
    const QUEUE_ID = 'id';
    
    const QUEUE_TASK = 'task';
    
    const QUEUE_OWNER = 'owner';
    
    const QUEUE_STATUS = 'status';
    
    const QUEUE_ADDED = 'added';
    
    const QUEUE_UPDATED = 'updated';
    
    const OPTION_PERSISTENCE = 'persistence';
    
    public function createTask($actionId, $parameters) {
        
        $task = new JsonTask($actionId, $parameters);
        
        $platform = $this->getPersistence()->getPlatForm();
        $query = 'INSERT INTO '.self::QUEUE_TABLE_NAME.' ('
            .self::QUEUE_OWNER.', '.self::QUEUE_TASK.', '.self::QUEUE_STATUS.', '.self::QUEUE_ADDED.', '.self::QUEUE_UPDATED.') '
            .'VALUES  (?, ?, ?, ?, ?)';

        $persistence = $this->getPersistence();
        $persistence->exec($query, array(
            \common_session_SessionManager::getSession()->getUser()->getIdentifier(),
            json_encode($task),
            Task::STATUS_CREATED,
            $platform->getNowExpression(),
            $platform->getNowExpression()
        ));
        
        $task->setId($persistence->lastInsertId(self::QUEUE_TABLE_NAME));
        
        return $task;
    }
    
    public function getIterator() {
        return new FifoIterator($this->getPersistence());
    }

    /**
     * @param $taskId
     * @param $status
     * @return mixed
     */
    public function updateTaskStatus($taskId, $status)
    {
        $persistence = $this->getPersistence();
        $query = 'UPDATE ' . self::QUEUE_TABLE_NAME . ' set ' . self::QUEUE_STATUS . '=? ' .
                 'WHERE ' . self::QUEUE_ID . '=?';

        return $persistence->exec($query, [
            $status,
            $taskId,
        ]);
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    protected function getPersistence()
    {
        $persistenceManager = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_KEY);
        return $persistenceManager->getPersistenceById($this->getOption(self::OPTION_PERSISTENCE));
    }
}
