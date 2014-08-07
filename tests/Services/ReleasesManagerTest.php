<?php
namespace Rocketeer\Services;

use Rocketeer\TestCases\RocketeerTestCase;

class ReleasesManagerTest extends RocketeerTestCase
{
	public function testCanGetCurrentRelease()
	{
		$currentRelease = $this->releasesManager->getCurrentRelease();

		$this->assertEquals(20000000000000, $currentRelease);
	}

	public function testCanGetStateOfReleases()
	{
		$validation = $this->releasesManager->getValidationFile();

		$this->assertEquals(array(
			10000000000000 => true,
			15000000000000 => false,
			20000000000000 => true,
		), $validation);
	}

	public function testCanGetInvalidReleases()
	{
		$validation = $this->releasesManager->getInvalidReleases();

		$this->assertEquals([1 => 15000000000000], $validation);
	}

	public function testCanUpdateStateOfReleases()
	{
		$this->releasesManager->markReleaseAsValid(15000000000000);
		$validation = $this->releasesManager->getValidationFile();

		$this->assertEquals(array(
			10000000000000 => true,
			15000000000000 => true,
			20000000000000 => true,
		), $validation);
	}

	public function testCanMarkReleaseAsValid()
	{
		$this->releasesManager->markReleaseAsValid(123456789);
		$validation = $this->releasesManager->getValidationFile();

		$this->assertEquals(array(
			10000000000000 => true,
			15000000000000 => false,
			20000000000000 => true,
			123456789      => true,
		), $validation);
	}

	public function testCanGetCurrentReleaseFromServerIfUncached()
	{
		$this->mock('rocketeer.storage.local', 'LocalStorage', function ($mock) {
			return $mock
				->shouldReceive('get')->with('current_release.production')->once()->andReturn(null)
				->shouldReceive('set')->with('current_release.production', '20000000000000')->once()
				->shouldReceive('getSeparator')->andReturn('/')
				->shouldReceive('getLineEndings')->andReturn(PHP_EOL);
		});

		$currentRelease = $this->releasesManager->getCurrentRelease();

		$this->assertEquals(20000000000000, $currentRelease);
	}

	public function testCanGetReleasesPath()
	{
		$releasePath = $this->releasesManager->getReleasesPath();

		$this->assertEquals($this->server.'/releases', $releasePath);
	}

	public function testCanGetCurrentReleaseFolder()
	{
		$currentReleasePath = $this->releasesManager->getCurrentReleasePath();

		$this->assertEquals($this->server.'/releases/20000000000000', $currentReleasePath);
	}

	public function testCanGetReleases()
	{
		$releases = $this->releasesManager->getReleases();

		$this->assertEquals([1 => 15000000000000, 0 => 20000000000000, 2 => 10000000000000], $releases);
	}

	public function testCanGetDeprecatedReleases()
	{
		$releases = $this->releasesManager->getDeprecatedReleases();

		$this->assertEquals([15000000000000, 10000000000000], $releases);
	}

	public function testCanGetPreviousValidRelease()
	{
		$currentRelease = $this->releasesManager->getPreviousRelease();

		$this->assertEquals(10000000000000, $currentRelease);
	}

	public function testReturnsCurrentReleaseIfNoPreviousValidRelease()
	{
		$this->mockState(array(
			'10000000000000' => false,
			'15000000000000' => false,
			'20000000000000' => true,
		));

		$currentRelease = $this->releasesManager->getPreviousRelease();

		$this->assertEquals(20000000000000, $currentRelease);
	}

	public function testReturnsCurrentReleaseIfOnlyRelease()
	{
		$this->mockState(array(
			'20000000000000' => true,
		));

		$currentRelease = $this->releasesManager->getPreviousRelease();

		$this->assertEquals(20000000000000, $currentRelease);
	}

	public function testReturnsCorrectPreviousReleaseIfUpdatedBeforehand()
	{
		$this->mockState(array(
			'20000000000000' => true,
		));

		$this->releasesManager->updateCurrentRelease();
		$currentRelease = $this->releasesManager->getPreviousRelease();

		$this->assertEquals(20000000000000, $currentRelease);
	}

	public function testCanUpdateCurrentRelease()
	{
		$this->releasesManager->updateCurrentRelease(30000000000000);

		$this->assertEquals(30000000000000, $this->app['rocketeer.storage.local']->get('current_release.production'));
	}

	public function testCanGetFolderInRelease()
	{
		$folder = $this->releasesManager->getCurrentReleasePath('{path.storage}');

		$this->assertEquals($this->server.'/releases/20000000000000/app/storage', $folder);
	}

	public function testDoesntPingForReleasesAllTheFuckingTime()
	{
		$this->mock('rocketeer.bash', 'Rocketeer\Bash', function ($mock) {
			return $mock
				->shouldReceive('getFile')->times(1)
				->shouldReceive('listContents')->once()->with($this->server.'/releases')->andReturn([20000000000000]);
		});

		$this->releasesManager->getNonCurrentReleases();
		$this->releasesManager->getNonCurrentReleases();
		$this->releasesManager->getNonCurrentReleases();
		$this->releasesManager->getNonCurrentReleases();
	}

	public function testDoesntPingForReleasesIfNoReleases()
	{
		$this->mock('rocketeer.bash', 'Rocketeer\Bash', function ($mock) {
			return $mock
				->shouldReceive('getFile')->times(1)
				->shouldReceive('listContents')->once()->with($this->server.'/releases')->andReturn([]);
		});

		$this->releasesManager->getNonCurrentReleases();
		$this->releasesManager->getNonCurrentReleases();
		$this->releasesManager->getNonCurrentReleases();
		$this->releasesManager->getNonCurrentReleases();
	}

	public function testIgnoresErrorsAndStuffWhenFetchingReleases()
	{
		$this->mock('rocketeer.bash', 'Rocketeer\Bash', function ($mock) {
			return $mock
				->shouldReceive('getFile')->times(1)
				->shouldReceive('listContents')->times(1)->with($this->server.'/releases')->andReturn(['IMPOSSIBLE BECAUSE NOPE FUCK YOU']);
		});

		$releases = $this->releasesManager->getReleases();

		$this->assertEmpty($releases);
	}
}
