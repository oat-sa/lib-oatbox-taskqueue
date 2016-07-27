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
namespace oat\Taskqueue\Persistence;

use oat\Taskqueue\JsonTask;
use oat\oatbox\task\Task;
use Iterator;

class FifoIterator implements Iterator
{
    /**
     * @var \common_persistence_SqlPersistence
     */
    private $persistence;

    /**
     * Query to iterator over
     *
     * @var string
     */
    private $query;

    /**
     * Query parameters
     *
     * @var string
     */
    private $params;

    /**
     * @var int
     */
    private $key = 0;

    /**
     * @var array
     */
    private $cache;

    public function __construct($persistence)
    {
        $this->persistence = $persistence;
        $query = 'select * from '.RdsQueue::QUEUE_TABLE_NAME.' WHERE '.RdsQueue::QUEUE_STATUS.' = ? ORDER BY '.RdsQueue::QUEUE_ADDED;
        $this->query = $query;
        $this->params = array(Task::STATUS_CREATED);
        $this->load();
        $this->rewind();
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->key;
    }

    public function rewind()
    {
        $this->key = 0;
    }

    /**
     * @return JsonTask
     */
    public function current()
    {
        $taskData = $this->cache[$this->key];
        $task = JsonTask::restore($taskData[RdsQueue::QUEUE_TASK]);
        $task->setId($taskData[RdsQueue::QUEUE_ID]);
        return $task;
    }

    public function next()
    {
        ++$this->key;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return isset($this->cache[$this->key]);
    }

    /**
     * Loads all the results in cache
     */
    protected function load()
    {
        $result = $this->persistence->query($this->query, $this->params);
        $this->cache = [];
        while ($statement = $result->fetch()) {
            $this->cache[] = $statement;
        }
    }
}
