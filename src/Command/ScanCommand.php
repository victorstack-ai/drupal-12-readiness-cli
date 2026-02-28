<?php

namespace Drupal12Readiness\Command;

use Drupal12Readiness\Report\HtmlReportGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addArgument('path', InputArgument::REQUIRED, 'The path to the Drupal project or module to scan.')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output format: table (default) or html', 'table');
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

        $outputFormat = $input->getOption('output');
        $useJson = ($outputFormat === 'html');

        $processArgs = [
            $phpstanBin,
            'analyse',
            $realPath,
            '-c', $tempConfig,
            '--no-progress',
        ];

        if ($useJson) {
            $processArgs[] = '--error-format=json';
        } else {
            $processArgs[] = '--error-format=table';
        }

        $process = new Process($processArgs);
        $process->setTimeout(600);

        $capturedOutput = '';
        if ($useJson) {
            $process->run();
            $capturedOutput = $process->getOutput();
        } else {
            $process->run(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });
        }

        // Cleanup temp config
        if (file_exists($tempConfig)) {
            unlink($tempConfig);
        }

        // Handle HTML output format.
        if ($useJson) {
            $issues = $this->parsePhpstanJson($capturedOutput, $realPath);
            $totalFiles = $this->countPhpFiles($realPath);

            $generator = new HtmlReportGenerator();
            $html = $generator->generate($issues, $realPath, $totalFiles, 'scan');
            $outputFile = $realPath . '/drupal12-scan-report.html';
            $writtenPath = $generator->writeToFile($html, $outputFile);
            $output->writeln("<info>HTML report written to: $writtenPath</info>");
            return Command::SUCCESS;
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

    /**
     * Parse PHPStan JSON output into a normalized issues array.
     *
     * @param string $jsonOutput The raw JSON output from PHPStan.
     * @param string $basePath   The base path that was scanned.
     *
     * @return array<int, array{file: string, line: int, function: string, context: string, replacement: string}>
     */
    private function parsePhpstanJson(string $jsonOutput, string $basePath): array
    {
        $issues = [];
        $data = json_decode($jsonOutput, true);

        if (!is_array($data) || !isset($data['files'])) {
            return $issues;
        }

        foreach ($data['files'] as $filePath => $fileData) {
            $relativePath = str_replace($basePath . '/', '', $filePath);
            if (!isset($fileData['messages'])) {
                continue;
            }
            foreach ($fileData['messages'] as $message) {
                $issues[] = [
                    'file' => $relativePath,
                    'line' => $message['line'] ?? 0,
                    'function' => 'deprecation',
                    'context' => $message['message'] ?? 'Unknown issue',
                    'replacement' => 'See Drupal 12 upgrade guide',
                ];
            }
        }

        return $issues;
    }

    /**
     * Count PHP-related files in a directory for report metadata.
     *
     * @param string $path The directory to count files in.
     *
     * @return int
     */
    private function countPhpFiles(string $path): int
    {
        $count = 0;
        $extensions = ['php', 'module', 'inc', 'install', 'theme'];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), $extensions, true)) {
                $count++;
            }
        }

        return $count;
    }
}
