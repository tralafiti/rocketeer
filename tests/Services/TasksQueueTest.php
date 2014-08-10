<?php
namespace Rocketeer\Services;

use Mockery;
use ReflectionFunction;
use Rocketeer\TestCases\RocketeerTestCase;

class TasksQueueTest extends RocketeerTestCase
{
	public function testCanBuildTaskByName()
	{
		$task = $this->tasksQueue()->buildTaskFromClass('Rocketeer\Tasks\Deploy');

		$this->assertInstanceOf('Rocketeer\Abstracts\AbstractTask', $task);
	}

	public function testCanBuildCustomTaskByName()
	{
		$tasks = $this->tasksQueue()->buildQueue(array('Rocketeer\Tasks\Check'));

		$this->assertInstanceOf('Rocketeer\Tasks\Check', $tasks[0]);
	}

	public function testCanBuildTaskFromString()
	{
		$string = 'echo "I love ducks"';

		$string = $this->tasksQueue()->buildTaskFromClosure($string);
		$this->assertInstanceOf('Rocketeer\Tasks\Closure', $string);

		$closure = $string->getClosure();
		$this->assertInstanceOf('Closure', $closure);

		$closureReflection = new ReflectionFunction($closure);
		$this->assertEquals(array('stringTask' => 'echo "I love ducks"'), $closureReflection->getStaticVariables());

		$this->assertEquals('I love ducks', $string->execute());
	}

	public function testCanBuildTaskFromClosure()
	{
		$originalClosure = function ($task) {
			return $task->getCommand()->info('echo "I love ducks"');
		};

		$closure = $this->tasksQueue()->buildTaskFromClosure($originalClosure);
		$this->assertInstanceOf('Rocketeer\Tasks\Closure', $closure);
		$this->assertEquals($originalClosure, $closure->getClosure());
	}

	public function testCanBuildQueue()
	{
		$queue = array(
			'foobar',
			function () {
				return 'lol';
			},
			'Rocketeer\Tasks\Deploy'
		);

		$queue = $this->tasksQueue()->buildQueue($queue);

		$this->assertInstanceOf('Rocketeer\Tasks\Closure', $queue[0]);
		$this->assertInstanceOf('Rocketeer\Tasks\Closure', $queue[1]);
		$this->assertInstanceOf('Rocketeer\Tasks\Deploy', $queue[2]);
	}

	public function testCanRunQueue()
	{
		$this->swapConfig(array(
			'rocketeer::default' => 'production',
		));

		$this->expectOutputString('JOEY DOESNT SHARE FOOD');
		$this->tasksQueue()->run(array(
			function () {
				print 'JOEY DOESNT SHARE FOOD';
			}
		), $this->getCommand());
	}

	public function testCanRunQueueOnDifferentConnectionsAndStages()
	{
		$this->swapConfig(array(
			'rocketeer::default'       => array('staging', 'production'),
			'rocketeer::stages.stages' => array('first', 'second'),
		));

		$output = array();
		$queue  = array(
			function ($task) use (&$output) {
				$output[] = $task->connections->getConnection().' - '.$task->connections->getStage();
			}
		);

		$status = $this->tasksQueue()->run($queue);

		$this->assertTrue($status);
		$this->assertEquals(array(
			'staging - first',
			'staging - second',
			'production - first',
			'production - second',
		), $output);
	}

	public function testCanRunQueueViaExecute()
	{
		$this->swapConfig(array(
			'rocketeer::default' => 'production',
		));

		$status = $this->tasksQueue()->run(array(
			'ls -a',
			function () {
				return 'JOEY DOESNT SHARE FOOD';
			}
		));

		$output = array_slice($this->history->getFlattenedOutput(), 2, 3);
		$this->assertTrue($status);
		$this->assertEquals(array(
			'.'.PHP_EOL.'..'.PHP_EOL.'.gitkeep',
			'JOEY DOESNT SHARE FOOD',
		), $output);
	}

	public function testCanRunOnMultipleConnectionsViaOn()
	{
		$this->swapConfig(array(
			'rocketeer::stages.stages' => array('first', 'second'),
		));

		$status = $this->tasksQueue()->on(array('staging', 'production'), function ($task) {
			return $task->connections->getConnection().' - '.$task->connections->getStage();
		});

		$this->assertTrue($status);
		$this->assertEquals(array(
			'staging - first',
			'staging - second',
			'production - first',
			'production - second',
		), $this->history->getFlattenedOutput());
	}

	public function testCanRunTasksInParallel()
	{
		$parallel = Mockery::mock('Parallel')
		                   ->shouldReceive('run')->once()->with(Mockery::type('array'))
		                   ->mock();

		$this->mockCommand(['parallel' => true]);
		$this->tasksQueue()->setParallel($parallel);

		$this->tasksQueue()->execute(['ls', 'ls']);
	}

	public function testCanCancelQueueIfTaskFails()
	{
		$this->expectOutputString('The tasks queue was canceled by task "MyCustomHaltingTask"');

		$this->mockCommand([], array(
			'error' => function ($error) {
				echo $error;
			},
		));

		$status = $this->tasksQueue()->run(array(
			'Rocketeer\Dummies\MyCustomHaltingTask',
			'Rocketeer\Dummies\MyCustomTask',
		));

		$this->assertFalse($status);
		$this->assertEquals([false], $this->history->getFlattenedOutput());
	}
}