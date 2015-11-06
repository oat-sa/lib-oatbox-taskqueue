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
namespace oat\Taskqueue\Action;

use oat\oatbox\Configurable;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\search\SearchService;
use oat\oatbox\service\ServiceNotFoundException;
use oat\oatbox\task\Queue;
use oat\oatbox\action\Action;

/**
 * Add Task 
 */
class AddTask extends ConfigurableService implements Action
{
    /**
     * 
     * @param array $params
     */
    public function __invoke($params) {
        $action = $params[0];
        unset($params[0]);
        try {
            if (class_exists($action)) {
                $instance = new $action();
                if ($instance instanceof Action) {
                    $invocable = $instance;
                }
            } else {
                $invocable = $this->getServiceManager()->get($action);
            }
            $queue = $this->getServiceManager()->get(Queue::CONFIG_ID);
            $task = $queue->createTask($action, $params);
	        $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Created task "%s" and added it to the queue', $task->getId()));
        } catch (ServiceNotFoundException $e) {
	        $report = new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Action "%s" not found.', $e->getServiceKey()));
	    }
        return $report;
    }

}
