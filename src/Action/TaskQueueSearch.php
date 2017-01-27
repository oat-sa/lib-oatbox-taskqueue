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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\Taskqueue\Action;


use oat\oatbox\service\ServiceManager;
use oat\oatbox\task\Queue;
use oat\oatbox\task\Task;
use oat\tao\model\datatable\DatatablePayload;
use oat\tao\model\datatable\DatatableRequest as DatatableRequestInterface;
use oat\tao\model\datatable\implementation\DatatableRequest;
use oat\Taskqueue\Persistence\QueueIterator;
use oat\Taskqueue\Persistence\RdsQueue;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class TaskQueueSearch implements DatatablePayload , ServiceLocatorAwareInterface
{

    use ServiceLocatorAwareTrait;

    /**
     * @var DatatableRequest
     */
    protected $request;

    /**
     * @var \common_persistence_SqlPersistence
     */
    protected $persistence;

    /**
     * DatatablePayload constructor.
     * @param DatatableRequestInterface|null $request
     */
    public function __construct(\common_persistence_SqlPersistence $persistence , DatatableRequestInterface $request = null)
    {
        $this->setServiceLocator(ServiceManager::getServiceManager());
        $this->persistence = $persistence;
        if ($request === null) {
            $request = DatatableRequest::fromGlobals();
        }

        $this->request = $request;
    }



    protected function setQueryFilter($name , $value) {
        if(is_array($value)) {
            return  ' ' . $name . ' IN (?) ';
        }
        return $name . ' = ? ';
    }

    protected function setQueryParameters($params = []) {
        
        $filters = [];
        foreach ($params as $name => $value) {
            $filters[] = $this->setQueryFilter($name , $value);
        }

        return implode(' AND ' , $filters );

    }

    protected function setSort()
    {
        $sortBy    = $this->request->getSortBy();
        $sortOrder = $this->request->getSortOrder();

        if(!empty($sortBy)) {
            return  ' ORDER BY ' . $sortBy . ' ' . $sortOrder . ' ';
        }
        return ' ORDER BY ' . RdsQueue::QUEUE_STATUS . ' DESC ' ;
    }

    protected function setLimit($query) {

        $page = $this->request->getPage();
        $rows = $this->request->getRows();

        $offset = $rows * ($page-1);

        $query .= ' LIMIT ' . ($rows);

        if($offset > 0) {
            $query .= ' OFFSET ' . $offset ;
        }

        return $query;
    }

    protected function search()  {

        $params = $this->request->getFilters();

        $params['status'] = [
            Task::STATUS_CREATED,
            Task::STATUS_STARTED,
            Task::STATUS_RUNNING,
            Task::STATUS_FINISHED,
        ];

        $query = 'SELECT * FROM ' . RdsQueue::QUEUE_TABLE_NAME . ' WHERE ';

        $query .= $this->setQueryParameters( $params);
        $query .= $this->setSort();
        $query .= $this->setLimit();
        $iterator = new QueueIterator($this->persistence , $query , $params);

        return $iterator;
    }

    protected function count() {
        $params = $this->request->getFilters();
        $query = 'SELECT count(*) as CPT FROM ' . RdsQueue::QUEUE_TABLE_NAME . ' WHERE ';
        $query = $this->setQueryParameters($query);

        $result = $this->persistence->query($query, $params);
        $taskCount = $result->fetch();
        return $taskCount['CPT'];
    }

    public function getPayload() {

        $iterator = $this->search();

        $taskList = [];

        foreach ($iterator as $task) {
            $taskList[] =
                [
                    "id"           => $task->getId(),
                    "label"        => $task->getLabel(),
                    "creationDate" => strtotime($task),
                    "status"       => $task->getStatus(),
                    "report"       => $task->getReport(),
                ];
        }

        $data = [
            'page'    => $this->request->getPage(),
            'records' => count($taskList),
            'total'   => $this->count(),
        ];

        return array_merge($taskList , $data);

    }

    public function jsonSerialize()
    {
        return $this->getPayload();
    }


}
