<?php

declare(strict_types=1);

namespace App\Presentation\Console\TaskGroup;

use App\Application\Service\TaskGroup\Lifecycle\TaskGroupRecalculateService;
use App\Shared\Utils\Asserts\InputAssertUtils;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:task-groups:recalculate',
    description: 'Recalculate status of a task group by id',
)]
final class RecalculateTaskGroupsConsole extends Command
{
    public function __construct(
        private readonly TaskGroupRecalculateService $recalculateTaskGroups,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'Task group UUID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->recalculateTaskGroups->execute(
            taskGroupId: InputAssertUtils::requiredString($input->getArgument('id'), 'id'),
        );

        $output->writeln('Task group recalculated.');

        return Command::SUCCESS;
    }
}
