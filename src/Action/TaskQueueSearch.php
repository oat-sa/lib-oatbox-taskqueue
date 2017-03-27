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
use oat\oatbox\task\AbstractTaskPayload;
use oat\oatbox\task\Task;
use oat\tao\model\datatable\DatatableRequest as DatatableRequestInterface;
use oat\tao\model\datatable\implementation\DatatableRequest;
use oat\Taskqueue\JsonTask;
use oat\Taskqueue\Persistence\RdsQueue;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class TaskQueueSearch extends AbstractTaskPayload implements ServiceLocatorAwareInterface
{

    use ServiceLocatorAwareTrait;

    /**
     * @var \common_persistence_SqlPersistence
     */
    protected $persistence;

    protected $currentUserId;

    /**
     * DatatablePayload constructor.
     * @param DatatableRequestInterface|null $request
     */
    public function __construct(\common_persistence_SqlPersistence $persistence , $currentUserId = null , DatatableRequestInterface $request = null)
    {
        $this->setServiceLocator(ServiceManager::getServiceManager());
        $this->persistence = $persistence;
        $this->currentUserId = $currentUserId;

        if ($request === null) {
            $request = DatatableRequest::fromGlobals();
        }

        $this->request = $request;
    }



    protected function setQueryFilter($name , $value) {
        if(is_array($value)) {
            return  ' ' . $name . ' IN (\''. implode('\' , \'' , $value).'\') ';
        }
        return $name . ' = \'' . $value . '\' ';
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

    protected function setLimit() {

        $page = $this->request->getPage();
        $rows = $this->request->getRows();

        $offset = $rows * ($page-1);

        $query = ' LIMIT ' . ($rows);

        if($offset > 0) {
            $query .= ' OFFSET ' . $offset ;
        }

        return $query;
    }

    protected function getFilters() {
        $params = $this->request->getFilters();

        $params['status'] = [
            Task::STATUS_CREATED,
            Task::STATUS_STARTED,
            Task::STATUS_RUNNING,
            Task::STATUS_FINISHED,
        ];
        if(!empty($this->currentUserId)) {
            $params['owner'] = $this->currentUserId;
        }
        return $params;

    }

    protected function search()  {

        $params = $this->getFilters();

        $query = 'SELECT * FROM ' . RdsQueue::QUEUE_TABLE_NAME . ' WHERE ';

        $query .= $this->setQueryParameters( $params);
        $query .= $this->setSort();
        $query .= $this->setLimit();
        $stmt = $this->persistence->query($query);

        $tasks = [];
        foreach ($stmt as $taskData){
            $tasks[] = JsonTask::restore($taskData[RdsQueue::QUEUE_TASK]);
        }
        return $tasks;
    }

    protected function count() {
        $params = $this->getFilters();
        $query = 'SELECT count(*) as cpt FROM ' . RdsQueue::QUEUE_TABLE_NAME . ' WHERE ';
        $query .= $this->setQueryParameters($params);

        $result = $this->persistence->query($query);
        $taskCount = $result->fetch();
        if($taskCount === false) {
            return 0;
        }
        return $taskCount['cpt'];
    }

}
