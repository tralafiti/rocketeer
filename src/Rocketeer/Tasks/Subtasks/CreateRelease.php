<?php
/*
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rocketeer\Tasks\Subtasks;

use Rocketeer\Abstracts\AbstractTask;
use Rocketeer\Interfaces\Strategies\DeployStrategyInterface;

/**
 * Creates a new release on the server.
 *
 * @author Maxime Fabre <ehtnam6@gmail.com>
 */
class CreateRelease extends AbstractTask
{
    /**
     * A description of what the task does.
     *
     * @type string
     */
    protected $description = 'Creates a new release on the server';

    /**
     * Run the task.
     *
     * @return string
     */
    public function execute()
    {
        /** @type DeployStrategyInterface $strategy */
        $strategy = $this->getStrategy('Deploy');

        return $strategy->deploy();
    }
}
