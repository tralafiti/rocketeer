<?php

/*
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Rocketeer\Console;

use League\Container\ContainerAwareInterface;
use Rocketeer\Container;
use Rocketeer\Rocketeer;
use Rocketeer\Services\Config\ContextualConfiguration;
use Rocketeer\Services\Environment\Pathfinder;
use Rocketeer\Traits\ContainerAware;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

/**
 * A standalone Rocketeer CLI.
 *
 * @author Maxime Fabre <ehtnam6@gmail.com>
 */
class Console extends Application
{
    use ContainerAware;

    /**
     * Create a new Artisan console application.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        parent::__construct('Rocketeer');
    }

    /**
     * {@inheritdoc}
     */
    public function add(Command $command)
    {
        if ($command instanceof ContainerAwareInterface) {
            $command->setContainer($this->container);
        }

        return parent::add($command);
    }

    //////////////////////////////////////////////////////////////////////
    //////////////////////////////// HELP ////////////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * Display the application's help.
     *
     * @return string
     */
    public function getHelp()
    {
        $help = str_replace($this->getLongVersion(), null, parent::getHelp());
        $state = $this->buildBlock('Current state', $this->getCurrentState());
        $help = sprintf('%s'.PHP_EOL.PHP_EOL.'%s%s', $this->getLongVersion(), $state, $help);

        return $help;
    }

    /**
     * @return string
     */
    public function getLongVersion()
    {
        $version = Rocketeer::COMMIT === '@commit@' ? '(dev version)' : Rocketeer::COMMIT;

        return sprintf(
            '<info>%s</info> <comment>%s</comment>',
            $this->getName(),
            $version
        );
    }

    /**
     * Build an help block.
     *
     * @param string   $title
     * @param string[] $informations
     *
     * @return string
     */
    protected function buildBlock($title, $informations)
    {
        $message = '<comment>'.$title.'</comment>';
        foreach ($informations as $name => $info) {
            $message .= PHP_EOL.sprintf('  <info>%-15s</info> %s', $name, $info);
        }

        return $message;
    }

    /**
     * Get current state of the CLI.
     *
     * @return string[]
     */
    protected function getCurrentState()
    {
        return [
            'application_name' => realpath($this->container->get(ContextualConfiguration::class)->get('application_name')),
            'application' => realpath($this->container->get(Pathfinder::class)->getApplicationPath()),
            'configuration' => realpath($this->container->get(Pathfinder::class)->getConfigurationPath()),
            'tasks' => $this->container->get('path.rocketeer.tasks'),
            'events' => $this->container->get('path.rocketeer.events'),
            'logs' => $this->container->get('path.rocketeer.logs'),
        ];
    }
}
