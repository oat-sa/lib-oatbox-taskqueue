# lib-oatbox-taskqueue
Task Queue

###Install:
```
sudo php vendor/oat-sa/lib-oatbox-taskqueue/bin/enableQueue.php <pathToTaoRoot> <persistenceId>
```
_Please note that persistence must be sql based._

####Example (call from root of tao):
```
sudo php vendor/oat-sa/lib-oatbox-taskqueue/bin/enableQueue.php . default
```

####Run tasks in task queue:
```
sudo php index.php 'oat\oatbox\task\RunTasks'
```