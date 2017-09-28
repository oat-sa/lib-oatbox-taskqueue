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
use oat\oatbox\task\implementation\SyncTask;
use oat\oatbox\task\Task;

class JsonTask extends SyncTask
{

    /**
     * Restore a task
     *
     * @param array $data
     * @return Task
     */
    public static function restore(array $data)
    {
        $data = json_decode($data['task'], true);

        if (!isset($data['invocable'], $data['params'])){
            return null;
        }
        /**
         * @var $task Task
         */
        $class = self::class;
        $task = new $class($data['invocable'], $data['params']);
        if (isset($data['report'])) {
            $task->setReport($data['report']);
        }
        if (isset($data['status'])) {
            $task->setStatus($data['status']);
        }
        if (isset($data['id'])) {
            $task->setId($data['id']);
        }
        if (isset($data['added'])) {
            $task->setCreationDate($data['added']);
        }
        if (isset($data['owner'])) {
            $task->setOwner($data['owner']);
        }
        if (isset($data['label'])) {
            $task->setLabel($data['label']);
        }
        if (isset($data['type'])) {
            $task->setType($data['type']);
        }
        return $task;
    }

}
