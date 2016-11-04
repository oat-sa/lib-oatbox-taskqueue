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

class RdsQueue extends ConfigurableService implements Queue
{
    const QUEUE_TABLE_NAME = 'queue';
    
    const QUEUE_ID = 'id';
    
    const QUEUE_TASK = 'task';
    
    const QUEUE_OWNER = 'owner';
    
    const QUEUE_STATUS = 'status';
    
    const QUEUE_REPORT = 'report';
    
    const QUEUE_ADDED = 'added';
    
    const QUEUE_UPDATED = 'updated';
    
    const OPTION_PERSISTENCE = 'persistence';

    /**
     * @param $action
     * @param $parameters
     * @param boolean $repeatedly Whether task created repeatedly (for example when execution of task was failed and task puts to the queue again).
     * @return JsonTask
     * @throws \common_exception_Error
     */
    public function createTask($action, $parameters, $repeatedly = false)
    {
        $task = new JsonTask($action, $parameters);
        
        $platform = $this->getPersistence()->getPlatForm();
        $query = 'INSERT INTO '.self::QUEUE_TABLE_NAME.' ('
            .self::QUEUE_ID.', '.self::QUEUE_OWNER.', '.self::QUEUE_TASK.', '.self::QUEUE_STATUS.', '.self::QUEUE_ADDED.', '.self::QUEUE_UPDATED.') '
            .'VALUES  (?, ?, ?, ?, ?, ?)';
        
        $persistence = $this->getPersistence();
        $id = \common_Utils::getNewUri();
        $persistence->exec($query, array(
            $id,
            \common_session_SessionManager::getSession()->getUser()->getIdentifier(),
            json_encode($task),
            Task::STATUS_CREATED,
            $platform->getNowExpression(),
            $platform->getNowExpression()
        ));
        
        $task->setId($id);
        
        return $task;
    }

    /**
     * @param string $taskId
     * @param $stateId
     * @param string $report
     */
    public function updateTaskStatus($taskId, $stateId, $report = '')
    {
        $platform = $this->getPersistence()->getPlatForm();
        $statement = 'UPDATE '.self::QUEUE_TABLE_NAME.' SET '.
            self::QUEUE_STATUS.' = ?, '.
            self::QUEUE_UPDATED.' = ?, '.
            self::QUEUE_REPORT.' = ? '.
            'WHERE '.self::QUEUE_ID.' = ?';
        $this->getPersistence()->exec($statement, array($stateId, $platform->getNowExpression(), json_encode($report), $taskId));
    }
    
    public function getIterator()
    {
        return new QueueIterator($this->getPersistence());
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
