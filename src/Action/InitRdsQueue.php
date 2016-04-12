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
use oat\oatbox\task\Queue;
use oat\oatbox\action\Action;
use common_report_Report as Report;

class InitRdsQueue extends \common_ext_action_InstallAction
{
    public function __invoke($params) {
        try {
            $persistenceId = isset($params[0]) ? $params[0] : 'default';
            $persistence = \common_persistence_Manager::getPersistence($persistenceId);
        } catch (\common_Exception $e) {
            $report = new Report(Report::TYPE_INFO, __('Usage: InitRdsQueue PERSISTENCE_ID'));
            $report->add(new Report(Report::TYPE_ERROR, __('Persistence "%s" could not be loaded', $persistenceId)));
            return $report;
        }
        
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;
        
        try {
        
            $queueTable = $schema->createtable(RdsQueue::QUEUE_TABLE_NAME);
            $queueTable->addOption('engine', 'MyISAM');
            
            $queueTable->addColumn(RdsQueue::QUEUE_ID, "string",array("notnull" => true, "length" => 255));
            $queueTable->addColumn(RdsQueue::QUEUE_STATUS, "string",array("notnull" => true,"length" => 50));
            $queueTable->addColumn(RdsQueue::QUEUE_ADDED, "string",array("notnull" => true));
            $queueTable->addColumn(RdsQueue::QUEUE_UPDATED, "string",array("notnull" => true));
            $queueTable->addColumn(RdsQueue::QUEUE_OWNER, "string",array("notnull" => false, "length" => 255));
            $queueTable->addColumn(RdsQueue::QUEUE_TASK, "string",array("notnull" => true, "length" => 4000));
            $queueTable->addColumn(RdsQueue::QUEUE_REPORT, "text", array("default" => null,"notnull" => false));
            $queueTable->setPrimaryKey(array(RdsQueue::QUEUE_ID));
        
            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }
            
        } catch(SchemaException $e) {
            \common_Logger::i('Database Schema already up to date.');
        }

        $queue = new RdsQueue(array(RdsQueue::OPTION_PERSISTENCE => $persistenceId));
        $queue->setServiceLocator($this->getServiceLocator());
        $this->getServiceLocator()->register(Queue::CONFIG_ID, $queue);
        
        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Setup rds queue successfully using persistence "%s"', $persistenceId));
    }
}
