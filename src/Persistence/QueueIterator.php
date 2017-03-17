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

class QueueIterator implements \Iterator
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
     * @var array
     */
    private $params = [];

    /**
     * @var JsonTask
     */
    private $currentTask = null;

    /**
     * Id of the current instance
     *
     * @var int
     */
    private $currentResult = null;

    /**
     * QueueIterator constructor.
     * @param \common_persistence_SqlPersistence $persistence
     * @param null $query
     * @param array $params
     */
    public function __construct(\common_persistence_SqlPersistence $persistence, $query = null, array $params = [])
    {
        $this->persistence = $persistence;
        $this->query = $query;
        $this->params = $params;

        if ($this->query === null) {
            $this->query = 'SELECT * FROM ' . RdsQueue::QUEUE_TABLE_NAME .
                           ' WHERE '.RdsQueue::QUEUE_STATUS . ' = ?' .
                             ' AND ' . RdsQueue::QUEUE_ID . '>?' .
                           ' ORDER BY '.RdsQueue::QUEUE_ADDED .
                           ' LIMIT 1';
        }

        if (empty($this->params)) {
            $this->params = [Task::STATUS_CREATED];
        }
        $this->rewind();
    }

    /**
     * Load the next task
     */
    public function next()
    {
        if ($this->valid()) {
            $last = $this->key();
            $this->load($last + 1);
        }
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->currentTask !== null;
    }

    /**
     * return JsonTask;
     */
    public function current()
    {
        return $this->currentTask;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->currentResult;
    }

    /**
     * Load first task
     */
    public function rewind()
    {
        $this->load(0);
    }

    /**
     * @param int $key
     */
    protected function load($key)
    {
        $params = $this->params;

        $currentTask = $this->current();

        if ($key === 0) {
            $params[] = 0; //id
        } else {
            $params[] = $currentTask->getId();
        }

        $result = $this->persistence->query($this->query, $params);
        $data = $result->fetch(\PDO::FETCH_ASSOC);

        if (empty($data)) {
            $task = null;
        } else {
            $taskData = json_decode($data[RdsQueue::QUEUE_TASK], true);
            unset($data[RdsQueue::QUEUE_TASK]);
            $data = array_merge($data, $taskData);
            $task = JsonTask::restore($data);
        }

        $this->currentResult = $key;
        $this->currentTask = $task;
    }
}
