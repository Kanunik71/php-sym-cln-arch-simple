<?php

declare(strict_types=1);

namespace App\Presentation\Console\File;

use App\Application\Service\File\Lifecycle\FileCleanupTmpService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:files:cleanup-tmp',
    description: 'Delete temporary files older than configured TTL',
)]
final class CleanupTmpFilesConsole extends Command
{
    public function __construct(
        private readonly FileCleanupTmpService $cleanupTmpFiles,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $deletedCount = $this->cleanupTmpFiles->execute();

        $output->writeln(sprintf('Deleted %d temporary file(s).', $deletedCount));

        return Command::SUCCESS;
    }
}
