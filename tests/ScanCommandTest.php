<?php

namespace Drupal12Readiness\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ScanCommandTest extends TestCase
{
    public function testScanCommand()
    {
        $projectRoot = dirname(__DIR__);
        $bin = $projectRoot . '/bin/drupal-12-readiness';
        $fixture = $projectRoot . '/tests/test_modules/deprecated_module';

        $process = new Process([$bin, 'scan', $fixture]);
        $process->run();

        $output = $process->getOutput();
        
        $this->assertStringContainsString('Scanning', $output);
        $this->assertStringContainsString('DeprecatedClass.php', $output);
        // PHPStan should catch the deprecated call
        $this->assertStringContainsString('deprecated', strtolower($output));
    }
}
