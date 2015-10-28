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
namespace oat\taskqueue\Persistence;

use oat\oatbox\service\ConfigurableService;
use oat\taskqueue\JsonTask;
use oat\oatbox\task\Task;

class FifoIterator extends \common_persistence_sql_QueryIterator
{
    public function __construct($persistence)
    {
        $query = 'select * from '.RdsQueue::QUEUE_TABLE_NAME.' WHERE '.RdsQueue::QUEUE_STATUS.' = ? ORDER BY '.RdsQueue::QUEUE_ADDED;
        parent::__construct($persistence, $query, array(Task::STATUS_CREATED));
    }
    
    public function current()
    {
        $taskData = parent::current();
        $task = JsonTask::restore($taskData[RdsQueue::QUEUE_TASK]);
        $task->setId($taskData[RdsQueue::QUEUE_ID]);
        return $task;
    }
}
