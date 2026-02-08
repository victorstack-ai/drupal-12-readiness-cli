<?php

namespace Drupal12Readiness\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ScanCommand extends Command
{
    protected static $defaultName = 'scan';

    protected function configure()
    {
        $this
            ->setName('scan')
            ->setDescription('Scan a directory for Drupal 12 deprecations.')
            ->addArgument('path', InputArgument::REQUIRED, 'The path to the Drupal project or module to scan.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        $realPath = realpath($path);

        if (!$realPath || !is_dir($realPath)) {
            $output->writeln("<error>Invalid path: $path</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Scanning $realPath for Drupal 12 deprecations...</info>");

        $projectRoot = dirname(__DIR__, 2);
        $phpstanBin = $projectRoot . '/vendor/bin/phpstan';
        $template = $projectRoot . '/phpstan.neon.template';
        $tempConfig = $projectRoot . '/phpstan.neon';

        // Prepare temporary phpstan.neon
        $configContent = file_get_contents($template);
        file_put_contents($tempConfig, $configContent);

        $process = new Process([
            $phpstanBin,
            'analyse',
            $realPath,
            '-c', $tempConfig,
            '--no-progress',
            '--error-format=table'
        ]);

        $process->setTimeout(600);
        $process->run(function ($type, $buffer) use ($output) {
            $output->write($buffer);
        });

        // Cleanup temp config
        if (file_exists($tempConfig)) {
            unlink($tempConfig);
        }

        if (!$process->isSuccessful()) {
            $output->writeln("
<comment>Scan completed with potential issues.</comment>");
            return Command::SUCCESS; // We return success even if issues are found, as the command itself ran.
        }

        $output->writeln("
<info>Scan completed! No major Drupal 12 deprecations found at level 1.</info>");
        return Command::SUCCESS;
    }
}
