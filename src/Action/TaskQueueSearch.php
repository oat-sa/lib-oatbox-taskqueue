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
use oat\oatbox\task\TaskInterface\TaskPayLoad;
use oat\oatbox\task\TaskInterface\TaskPersistenceInterface;
use oat\tao\model\datatable\DatatablePayload;
use oat\tao\model\datatable\DatatableRequest as DatatableRequestInterface;
use oat\tao\model\datatable\implementation\DatatableRequest;
use oat\Taskqueue\JsonTask;
use oat\Taskqueue\Persistence\QueueIterator;
use oat\Taskqueue\Persistence\RdsQueue;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class TaskQueueSearch implements TaskPayLoad
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

    protected $currentUserId;

    /**
     * TaskQueueSearch constructor.
     * @param TaskPersistenceInterface $persistence
     * @param null $currentUserId
     * @param DatatableRequestInterface|null $request
     */
    public function __construct(TaskPersistenceInterface $persistence , $currentUserId = null , DatatableRequestInterface $request = null)
    {
        $this->setServiceLocator(ServiceManager::getServiceManager());
        $this->persistence = $persistence;
        $this->currentUserId = $currentUserId;

        if ($request === null) {
            $request = DatatableRequest::fromGlobals();
        }

        $this->request = $request;
    }



    protected function getFilters() {
        $params = $this->request->getFilters();

        if(!array_key_exists('status' , $params) || empty($params['status'])) {
            $params['status'] = [
                Task::STATUS_CREATED,
                Task::STATUS_STARTED,
                Task::STATUS_RUNNING,
                Task::STATUS_FINISHED,
            ];
        }
        if(!empty($this->currentUserId)) {
            $params['owner'] = $this->currentUserId;
        }
        return $params;

    }

    protected function search()  {
        $params    = $this->getFilters();

        $page      = $this->request->getPage();
        $rows      = $this->request->getRows();

        $sortBy    = $this->request->getSortBy();
        $sortOrder = $this->request->getSortOrder();


        return $this->persistence->search($params, $rows, $page , $sortBy , $sortOrder );
    }

    public function count() {
        $params    = $this->getFilters();
        return $this->persistence->count($params);
    }


    public function getPayload() {

        $iterator = $this->search();

        $taskList = [];

        foreach ($iterator as $taskData) {
            $taskList[] =
                [
                    "id"           => $taskData['id'],
                    "label"        => $taskData['label'],
                    "creationDate" => strtotime($taskData['added']),
                    "status"       => $taskData['status'],
                    "report"       => json_decode($taskData['report'], true),
                ];
        }
        $countTotal = $this->count();
        $rows = $this->request->getRows();
        $data = [
            'rows'    => $rows,
            'page'    => $this->request->getPage(),
            'amount' => count($taskList),
            'total'   => ceil($countTotal/$rows),
            'data' => $taskList,
        ];

        return $data;

    }

    public function jsonSerialize()
    {
        return $this->getPayload();
    }


}
