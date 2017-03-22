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

use oat\Taskqueue\JsonTask;

/**
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class JsonTaskTest extends PHPUnit_Framework_TestCase
{

    public function testConstruct()
    {
        $invocable = 'invocable/Action';
        $params = ['foo' => 'bar', 2, 'three'];
        $task = new JsonTask($invocable, $params);
        $this->assertEquals($invocable, $task->getInvocable());
        $this->assertEquals($params, $task->getParameters());
    }

    public function testGetId()
    {
        $id = 'foo';
        $task = new JsonTask('invocable/Action', []);
        $task->setId($id);
        $this->assertEquals($id, $task->getId());
    }

    public function testSetId()
    {
        $id = 'foo';
        $task = new JsonTask('invocable/Action', []);
        $this->assertEquals(null, $task->getId());
        $task->setId($id);
        $this->assertEquals($id, $task->getId());
    }

    public function testGetStatus()
    {
        $status = 'bar';
        $task = new JsonTask('invocable/Action', []);
        $task->setStatus($status);
        $this->assertEquals($status, $task->getStatus());
    }

    public function testSetStatus()
    {
        $status = 'bar';
        $task = new JsonTask('invocable/Action', []);
        $this->assertEquals(null, $task->getStatus());
        $task->setStatus($status);
        $this->assertEquals($status, $task->getStatus());
    }

    public function testGetLabel()
    {
        $label = 'bar';
        $task = new JsonTask('invocable/Action', []);
        $task->setLabel($label);
        $this->assertEquals($label, $task->getLabel());
    }

    public function testSetLabel()
    {
        $label = 'bar';
        $task = new JsonTask('invocable/Action', []);
        $this->assertEquals(null, $task->getLabel());
        $task->setLabel($label);
        $this->assertEquals($label, $task->getLabel());
    }

    public function testGetType()
    {
        $type = 'bar';
        $task = new JsonTask('invocable/Action', []);
        $task->setType($type);
        $this->assertEquals($type, $task->getType());
    }

    public function testSetType()
    {
        $type = 'bar';
        $task = new JsonTask('invocable/Action', []);
        $this->assertEquals(null, $task->getType());
        $task->setType($type);
        $this->assertEquals($type, $task->getType());
    }

    public function testGetInvocable()
    {
        $task = new JsonTask('invocable/Action', []);
        $this->assertEquals('invocable/Action', $task->getInvocable());
    }

    public function testGetParameters()
    {
        $params = ['foo', 'bar' => 'baz'];
        $task = new JsonTask('invocable/Action', $params);
        $this->assertEquals($params, $task->getParameters());
    }

    public function testSetParameters()
    {
        $params = ['foo', 'bar' => 'baz'];
        $task = new JsonTask('invocable/Action', []);
        $this->assertEquals([], $task->getParameters());
        $task->setParameters($params);
        $this->assertEquals($params, $task->getParameters());
    }

    public function testJsonSerialize()
    {
        $params = ['foo' => 'bar', 2, 'three'];
        $task = new JsonTask('invocable/Action', $params);
        $task->setStatus(JsonTask::STATUS_CREATED);
        $task->setId(1);
        $task->setReport('report');

        $serialized = json_encode($task);
        $unserialized = json_decode($serialized, true);
        $this->assertEquals('invocable/Action', $unserialized['invocable']);
        $this->assertEquals($params, $unserialized['params']);
        $this->assertEquals(1, $unserialized['id']);
        $this->assertEquals('report', $unserialized['report']);
        $this->assertEquals(JsonTask::STATUS_CREATED, $unserialized['status']);

        $task = new JsonTask(new TestAction(), $params);

        $serialized = json_encode($task);
        $unserialized = json_decode($serialized, true);
        $this->assertEquals('TestAction', $unserialized['invocable']);
        $this->assertEquals($params, $unserialized['params']);
    }

    public function testRestore()
    {
        $params = ['foo' => 'bar', 2, 'three'];
        $task = new JsonTask(new TestAction(), $params);
        $task->setStatus(JsonTask::STATUS_CREATED);
        $task->setId(1);
        $report = 'test';
        $task->setReport($report);


        $serialized = json_encode($task);
        $restoredTask = JsonTask::restore($serialized);
        $this->assertTrue($restoredTask instanceof JsonTask);
        $this->assertEquals($params, $restoredTask->getParameters());
        $this->assertEquals('TestAction', $restoredTask->getInvocable());
        $this->assertEquals(1, $restoredTask->getId());
        $this->assertEquals($report, $restoredTask->getReport());
        $this->assertEquals(JsonTask::STATUS_CREATED, $restoredTask->getStatus());
    }
}

class TestAction implements \oat\oatbox\action\Action
{
    public function __invoke($params)
    {
        // TODO: Implement __invoke() method.
    }
}