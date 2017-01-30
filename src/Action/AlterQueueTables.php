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
 *
 */

namespace oat\Taskqueue\Action;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use oat\Taskqueue\Persistence\RdsQueue;
use oat\oatbox\service\ServiceNotFoundException;


class AlterQueueTables extends \common_ext_action_InstallAction
{
    protected $persistence;

    public function __invoke($params)
    {
        $service = $this->getService();
        if ($service === null) {
            return new \common_report_Report(\common_report_Report::TYPE_ERROR, __('RdsQueue is not installed'));
        }

        $this->persistence = \common_persistence_Manager::getPersistence($service->getOption(RdsQueue::OPTION_PERSISTENCE));

        //change task column type
        $schemaManager = $this->persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $tableData = $schema->getTable(RdsQueue::QUEUE_TABLE_NAME);
            $tableData->addColumn(RdsQueue::QUEUE_TYPE, "string",array("default" => null , "notnull" => false,"length" => 255));
        } catch(SchemaException $e) {
            \common_Logger::i('Database Schema already up to date.');
        }
        try {
            $tableData = $schema->getTable(RdsQueue::QUEUE_TABLE_NAME);
            $tableData->addColumn(RdsQueue::QUEUE_LABEL, "string",array("default" => null , "notnull" => false,"length" => 255));
        } catch(SchemaException $e) {
            \common_Logger::i('Database Schema already up to date.');
        }
        try {
            $tableData = $schema->getTable(RdsQueue::QUEUE_TABLE_NAME);
            $tableData->changeColumn(RdsQueue::QUEUE_TASK, array('type' => Type::getType('text'), "default" => null, "notnull" => false));
        } catch(SchemaException $e) {
            \common_Logger::i('Database Schema already up to date.');
        }
        $queries = $this->persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $this->persistence->exec($query);
        }

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Tables successfully altered'));
    }

    /**
     * @return null|RdsQueue
     */
    protected function getService()
    {
        try {
            $service = $this->serviceLocator->get(RdsQueue::CONFIG_ID);
        } catch (ServiceNotFoundException $e) {
            $service = null;
        }

        if (!$service instanceof RdsQueue) {
            $service = null;
        }

        return $service;
    }
}
