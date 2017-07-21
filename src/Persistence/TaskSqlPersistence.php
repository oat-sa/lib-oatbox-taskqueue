<?php
/**
 * Created by PhpStorm.
 * User: christophe
 * Date: 06/06/17
 * Time: 11:00
 */

namespace oat\Taskqueue\Persistence;


use oat\oatbox\task\Task;
use oat\oatbox\task\TaskInterface\TaskPersistenceInterface;
use oat\Taskqueue\JsonTask;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class TaskSqlPersistence implements TaskPersistenceInterface
{

    use ServiceLocatorAwareTrait;

    /**
     * @var \common_persistence_SqlPersistence
     */
    protected $persistence;

    const QUEUE_TABLE_NAME = 'queue';

    const QUEUE_ID = 'id';

    const QUEUE_LABEL = 'label';

    const QUEUE_TYPE = 'type';

    const QUEUE_TASK = 'task';

    const QUEUE_OWNER = 'owner';

    const QUEUE_STATUS = 'status';

    const QUEUE_REPORT = 'report';

    const QUEUE_ADDED = 'added';

    const QUEUE_UPDATED = 'updated';

    protected $persistenceName = 'persistence';

    public function __construct($config  = array()) {
        if(isset($config['persistence'])) {
            $this->persistenceName =  $config['persistence'];
        }
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    protected function getPersistence()
    {
        if(is_null($this->persistence)) {
            $persistenceManager = $this->getServiceLocator()->get(\common_persistence_Manager::SERVICE_ID);
            $this->persistence =  $persistenceManager->getPersistenceById($this->persistenceName);
        }

        return $this->persistence;
    }

    public function get($taskId)
    {
        $task = null;
        $statement = 'SELECT * FROM ' . self::QUEUE_TABLE_NAME . ' ' .
            'WHERE ' . self::QUEUE_ID . ' = ?';
        $query = $this->getPersistence()->query($statement, array($taskId));
        $data = $query->fetch(\PDO::FETCH_ASSOC);
        if ($data) {
            $task = JsonTask::restore($data);
        }
        return $task;
    }

    public function add(Task $task)
    {
        $id = \common_Utils::getNewUri();
        $platform = $this->getPersistence()->getPlatForm();
        $now = $platform->getNowExpression();

        $task->setId($id);
        $task->setStatus(Task::STATUS_CREATED);
        $task->setCreationDate($now);

        $query = 'INSERT INTO '.self::QUEUE_TABLE_NAME.' ('
            .self::QUEUE_ID .', '.self::QUEUE_OWNER.', ' .self::QUEUE_LABEL.', ' .self::QUEUE_TYPE.', ' . self::QUEUE_TASK.', '.self::QUEUE_STATUS.', '.self::QUEUE_ADDED.', '.self::QUEUE_UPDATED.') '
            .'VALUES  (?, ?, ?, ?, ?, ? , ? , ?)';

        $persistence = $this->getPersistence();
        $persistence->exec($query, array(
            $task->getId(),
            $task->getOwner(),
            $task->getLabel(),
            $task->getType(),
            json_encode($task),
            $task->getStatus(),
            $now,
            $now
        ));

        return $task;
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

    protected function setSort($sortBy , $sortOrder)
    {
        if(empty($sortBy)) {
            return '';
        }
        if(!empty($sortBy)) {
            return  ' ORDER BY ' . $sortBy . ' ' . $sortOrder . ' ';
        }
        return ' ORDER BY ' . RdsQueue::QUEUE_STATUS . ' DESC ' ;
    }

    protected function setLimit($page , $rows) {
        if(empty($page)) {
            return '';
        }
        $offset = $rows * ($page-1);

        $query = ' LIMIT ' . ($rows);

        if($offset > 0) {
            $query .= ' OFFSET ' . $offset ;
        }

        return $query;
    }

    public function search(array $filterTask, $rows = null, $page = null , $sortBy = null , $sortOrder = null)
    {

        $query = 'SELECT * FROM ' . RdsQueue::QUEUE_TABLE_NAME . ' WHERE ';

        $query .= $this->setQueryParameters( $filterTask);
        $query .= $this->setSort($sortBy , $sortOrder);
        $query .= $this->setLimit($page , $rows);
        $stmt = $this->getPersistence()->query($query);

        $list = $stmt->fetchAll();
        return new QueueIterator($list);
    }

    public function has($taskId)
    {
        $statement = 'SELECT COUNT(*) AS cpt FROM ' . self::QUEUE_TABLE_NAME . ' ' .
            'WHERE ' . self::QUEUE_ID . ' = ?';
        $query = $this->getPersistence()->query($statement, array($taskId));
        $data = $query->fetch(\PDO::FETCH_ASSOC);
        return (isset($data['cpt']) && $data['cpt'] > 0);
    }

    public function update($taskId, $status)
    {
        $task = $this->get($taskId);
        $task->setStatus($status);
        $platform = $this->getPersistence()->getPlatForm();
        $statement = 'UPDATE '.self::QUEUE_TABLE_NAME.' SET '.
            self::QUEUE_STATUS.' = ?, '.
            self::QUEUE_TASK.' = ?, '.
            self::QUEUE_UPDATED.' = ? '.
            'WHERE '.self::QUEUE_ID.' = ?';

        return $this->getPersistence()->exec($statement, [
            $status,
            json_encode($task),
            $platform->getNowExpression(),
            $taskId
        ]);
    }

    public function setReport($taskId, \common_report_Report $report)
    {
        $task = $this->get($taskId);
        $task->setReport($report);

        $platform = $this->getPersistence()->getPlatForm();
        $statement = 'UPDATE '.self::QUEUE_TABLE_NAME.' SET '.
            self::QUEUE_UPDATED.' = ?, '.
            self::QUEUE_TASK.' = ?, '.
            self::QUEUE_REPORT.' = ? '.
            'WHERE '.self::QUEUE_ID.' = ?';

        return $this->getPersistence()->exec($statement, [
            $platform->getNowExpression(),
            json_encode($task),
            json_encode($report),
            $taskId
        ]);
    }

    public function count(array $params)
    {
        $statement = 'SELECT COUNT(*) AS cpt FROM ' . self::QUEUE_TABLE_NAME . ' ' .
            'WHERE ' . self::QUEUE_STATUS . ' != ?';

        if (!empty($params)) {
            $statement .= ' AND '. $this->setQueryParameters($params);
        }

        $query = $this->getPersistence()->query($statement, array(Task::STATUS_ARCHIVED));
        $data = $query->fetch(\PDO::FETCH_ASSOC);
        return intval($data['cpt']);
    }

    public function getAll()
    {
        $statement = 'SELECT COUNT(*) AS cpt FROM ' . self::QUEUE_TABLE_NAME . ' ' .
            'WHERE ' . self::QUEUE_STATUS . ' != ?';
        $query = $this->getPersistence()->query($statement, array(Task::STATUS_ARCHIVED));
        $data = $query->fetch(\PDO::FETCH_ASSOC);
        return new QueueIterator($data);
    }


}