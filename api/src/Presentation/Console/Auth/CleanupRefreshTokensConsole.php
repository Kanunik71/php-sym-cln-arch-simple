<?php

declare(strict_types=1);

namespace App\Presentation\Console\Auth;

use App\Application\Service\Auth\RefreshTokenService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:auth:cleanup-refresh-tokens',
    description: 'Delete expired and revoked refresh tokens older than retention',
)]
final class CleanupRefreshTokensConsole extends Command
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $deletedCount = $this->refreshTokenService->cleanup();

        $output->writeln(sprintf('Deleted %d refresh token(s).', $deletedCount));

        return Command::SUCCESS;
    }
}
