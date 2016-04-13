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

use oat\oatbox\service\ConfigurableService;
use oat\oatbox\Configurable;
use oat\oatbox\task\Task;

class JsonTask extends Configurable implements \JsonSerializable, Task
{
    private $id;
    
    private $invocable;
    
    private $params;
    
    private $status;
    
    public function __construct($invocable, $params)
    {
        $this->invocable = $invocable;
        $this->params = $params;
    }
    
    /**
     * (non-PHPdoc)
     * @see \oat\oatbox\task\Task::getId()
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Setter used during construction
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    
    /**
     * (non-PHPdoc)
     * @see \oat\oatbox\task\Task::getStatus()
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Setter used during construction
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
    
    /**
     * (non-PHPdoc)
     * @see \oat\oatbox\task\Task::getInvocable()
     */
    public function getInvocable()
    {
        return $this->invocable;
    }
    
    /**
     * (non-PHPdoc)
     * @see \oat\oatbox\task\Task::getParameters()
     */
    public function getParameters()
    {
        return $this->params;
    }
    
    public function setParameters(array $params)
    {
        $this->params = $params;
    }

    // Serialization
    
    /**
     * (non-PHPdoc)
     * @see JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        return array(
        	'invocable' => $this->invocable,
            'params'    => $this->params
        );
    }
    
    /**
     * Restore a task
     * 
     * @param string $json
     * @return \oat\Taskqueue\JsonTask
     */
    public static function restore($json)
    {
        $data = json_decode($json, true);
        return new self($data['invocable'], $data['params']);
    }
}
