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
namespace oat\Taskqueue;

use oat\oatbox\task\AbstractTask;
use oat\oatbox\task\Task;

class JsonTask extends AbstractTask implements \JsonSerializable, Task
{
    public function __construct($invocable, $params)
    {
        $this->setInvocable($invocable);
        $this->setParameters($params);
    }
    
    // Serialization
    
    /**
     * (non-PHPdoc)
     * @see JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        $invocable = $this->getInvocable();
        if (is_object($invocable) && !$invocable instanceof \JsonSerializable) {
            $invocable = get_class($invocable);
        }

        return [
            'invocable' => $invocable,
            'params'    => $this->getParameters(),
            'id'        => $this->getId(),
            'status'    => $this->getStatus(),
            'report'    => $this->getReport(),
            'label'     => $this->getLabel(),
            'type'     => $this->getType(),
            'added'     => date('Y-m-d H:i:s',$this->getCreationDate()),
            'owner'     => $this->getOwner(),
        ];
    }

    public function getCreationDate()
    {
        return strtotime($this->creationDate);
    }

    /**
     * Restore a task
     * 
     * @param array $data
     * @return \oat\Taskqueue\JsonTask
     */
    public static function restore($data)
    {
        $taskData = json_decode($data, true);

        if (!isset($taskData['invocable'], $taskData['params'])){
            return null;
        }

        $task = new self($taskData['invocable'], $taskData['params']);

        if (isset($taskData['report'])) {
            $task->setReport($taskData['report']);
        }
        if (isset($taskData['status'])) {
            $task->setStatus($taskData['status']);
        }
        if (isset($taskData['id'])) {
            $task->setId($taskData['id']);
        }
        if (isset($taskData['added'])) {
            $task->setCreationDate($taskData['added']);
        }
        if (isset($taskData['owner'])) {
            $task->setOwner($taskData['owner']);
        }
        if (isset($taskData['label'])) {
            $task->setLabel($taskData['label']);
        }
        if (isset($taskData['type'])) {
            $task->setType($taskData['type']);
        }
        if (isset($taskData['added'])) {
            $task->setType($taskData['added']);
        }

        return $task;
    }
}
