<?php

declare(strict_types=1);

namespace Chenm\Helper\Log;

use Imi\Cli\ImiCommand;
use Imi\Log\MonoLogger;
use Monolog\Handler\AbstractProcessingHandler;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * {@inheritDoc}
 */
class MonoHandler extends AbstractProcessingHandler
{
    protected OutputInterface $output;

    /**
     * {@inheritDoc}
     */
    public function __construct(?OutputInterface $output = null, $level = MonoLogger::DEBUG, bool $bubble = true)
    {
        $this->output = $output ?? ImiCommand::getOutput();
        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        $this->output->write((string) $record['formatted']);
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }
}
