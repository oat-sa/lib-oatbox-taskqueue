<?php
/**
 * Created by PhpStorm.
 * User: christophe
 * Date: 06/06/17
 * Time: 11:00
 */

namespace oat\Taskqueue\Persistence;


use oat\oatbox\task\implementation\TaskList;
use oat\oatbox\task\Queue;
use oat\oatbox\task\Task;
use oat\oatbox\task\TaskInterface\TaskPersistenceInterface;
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

    const OPTION_PERSISTENCE = 'persistence';

    /**
     * @return \common_persistence_SqlPersistence
     */
    protected function getPersistence() {
        return $this->getPersistence();
    }

    public function get($taskId)
    {
        $task = null;
        $statement = 'SELECT * FROM ' . self::QUEUE_TABLE_NAME . ' ' .
            'WHERE ' . self::QUEUE_ID . ' = ?';
        $query = $this->getPersistence()->query($statement, array($taskId));
        $data = $query->fetch(\PDO::FETCH_ASSOC);
        if ($data) {
            $task = Task::restore($data[self::QUEUE_TASK]);
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

    public function search(array $filters, $limit, $offset)
    {
        // TODO: Implement search() method.
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
        $task = $this->getTask($taskId);
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
        return new TaskList($data);
    }


}