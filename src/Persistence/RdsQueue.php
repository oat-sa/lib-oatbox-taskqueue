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
use oat\oatbox\task\AbstractQueue;
use oat\oatbox\task\implementation\SyncTask;
use oat\oatbox\task\implementation\TaskList;
use oat\Taskqueue\Action\TaskQueueSearch;
use oat\Taskqueue\JsonTask;
use oat\oatbox\task\Task;
use oat\oatbox\task\Queue;
use TheSeer\fDOM\fDOMDocument;

class RdsQueue extends AbstractQueue
{
    const QUEUE_TABLE_NAME = 'queue';
    
    const QUEUE_ID = 'id';

    const QUEUE_LABEL = 'label';

    const QUEUE_TYPE = 'type';

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
     * @param null $label
     * @param null $type
     * @return Task|false
     */
    public function createTask($action, $parameters, $repeatedly = false, $label = null , $type = null)
    {
        if ($repeatedly) {
            \common_Logger::w("Repeated call of action'; Execution canceled.");
            return false;
        }
        $task = new SyncTask($action, $parameters);
        $task->setLabel($label);
        $task->setType($type);
        $this->getPersistence()->add($task);
        return $task;
    }

    /**
     * @return TaskList
     */
    public function getIterator()
    {
        return $this->getPersistence()->search([self::QUEUE_STATUS => Task::STATUS_CREATED]);
    }

}
