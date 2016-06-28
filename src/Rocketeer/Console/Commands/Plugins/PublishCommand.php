<?php

/*
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rocketeer\Console\Commands\Plugins;

use Rocketeer\Console\Commands\AbstractCommand;
use Rocketeer\Services\Ignition\Plugins;
use Symfony\Component\Console\Input\InputArgument;

class PublishCommand extends AbstractCommand
{
    /**
     * The default name.
     *
     * @var string
     */
    protected $name = 'plugin:config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes the configuration of a plugin';

    /**
     * Whether the command's task should be built
     * into a pipeline or run straight.
     *
     * @var bool
     */
    protected $straight = true;

    /**
     * Run the tasks.
     */
    public function fire()
    {
        $this->app->add('rocketeer.command', $this);

        /** @var string $package */
        $package = $this->argument('package');

        $publisher = new Plugins($this->app);
        $publisher->publish($package);
    }

    /**
     * Get the console command arguments.
     *
     * @return string[][]
     */
    protected function getArguments()
    {
        return [
            ['package', InputArgument::REQUIRED, 'The package to publish the configuration for'],
        ];
    }
}
