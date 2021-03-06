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

use oat\oatbox\service\ConfigurableService;
use oat\Taskqueue\Persistence\RdsQueue;
use Doctrine\DBAL\Schema\SchemaException;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\task\Queue;
use oat\oatbox\action\Action;
use oat\oatbox\task\TaskRunner;
use common_report_Report as Report;

class RunTasks extends ConfigurableService implements Action
{
    public function __invoke($params) {

        $statistics = array();
        $queue = $this->getServiceManager()->get(Queue::CONFIG_ID);
        $report = new Report(Report::TYPE_SUCCESS);
        foreach ($queue as $task) {
            $subReport = $queue->runTask($task);
            $statistics[$subReport->getType()] = isset($statistics[$subReport->getType()])
                ? $statistics[$subReport->getType()] + 1
                : 1;
            $report->add($subReport);
        }
        
        if (empty($statistics)) {
            $report = new Report(Report::TYPE_INFO, __('No tasks to run'));
        } else {
            if (isset($statistics[Report::TYPE_ERROR]) || isset($statistics[Report::TYPE_WARNING])) {
                $report->setType(Report::TYPE_WARNING);
            }
            $report->setMessage(__('Ran %s task(s):', array_sum($statistics)));
        }
        return $report; 
    }
}
