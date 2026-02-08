<?php

namespace Drupal12Readiness\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

class DatabaseAuditCommand extends Command
{
    protected static $defaultName = 'check:db-api';

    private const DEPRECATED_FUNCTIONS = [
        'db_query' => 'Use \Drupal::database()->query() or injected connection',
        'db_select' => 'Use \Drupal::database()->select() or injected connection',
        'db_insert' => 'Use \Drupal::database()->insert() or injected connection',
        'db_update' => 'Use \Drupal::database()->update() or injected connection',
        'db_delete' => 'Use \Drupal::database()->delete() or injected connection',
        'db_merge' => 'Use \Drupal::database()->merge() or injected connection',
        'db_transaction' => 'Use \Drupal::database()->startTransaction() or injected connection',
        'db_close' => 'Database connections are closed automatically',
        'db_next_id' => 'Use sequences or auto-increment',
        'db_or' => 'Use \Drupal::database()->condition('OR')',
        'db_and' => 'Use \Drupal::database()->condition('AND')',
        'db_xor' => 'Use \Drupal::database()->condition('XOR')',
        'db_condition' => 'Use \Drupal::database()->condition()',
        'db_like' => 'Use \Drupal::database()->escapeLike()',
        'db_driver' => 'Use \Drupal::database()->driver()',
        'db_escape_field' => 'Use \Drupal::database()->escapeField()',
        'db_escape_table' => 'Use \Drupal::database()->escapeTable()',
        'db_find_tables' => 'Use \Drupal::database()->schema()->findTables()',
        'db_ignore_replica' => "Use \Drupal\Core\Database\Database::ignoreTarget('default', 'replica')",
        'db_rename_table' => 'Use \Drupal::database()->schema()->renameTable()',
        'db_drop_table' => 'Use \Drupal::database()->schema()->dropTable()',
        'db_add_field' => 'Use \Drupal::database()->schema()->addField()',
        'db_drop_field' => 'Use \Drupal::database()->schema()->dropField()',
        'db_field_exists' => 'Use \Drupal::database()->schema()->fieldExists()',
        'db_index_exists' => 'Use \Drupal::database()->schema()->indexExists()',
        'db_add_primary_key' => 'Use \Drupal::database()->schema()->addPrimaryKey()',
        'db_drop_primary_key' => 'Use \Drupal::database()->schema()->dropPrimaryKey()',
        'db_add_unique_key' => 'Use \Drupal::database()->schema()->addUniqueKey()',
        'db_drop_unique_key' => 'Use \Drupal::database()->schema()->dropUniqueKey()',
        'db_add_index' => 'Use \Drupal::database()->schema()->addIndex()',
        'db_drop_index' => 'Use \Drupal::database()->schema()->dropIndex()',
        'db_change_field' => 'Use \Drupal::database()->schema()->changeField()',
    ];

    protected function configure()
    {
        $this
            ->setName('check:db-api')
            ->setDescription('Audit code for deprecated procedural Database API calls.')
            ->addArgument('path', InputArgument::REQUIRED, 'The path to the Drupal project or module to scan.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $input->getArgument('path');
        $realPath = realpath($path);

        if (!$realPath || !is_dir($realPath)) {
            $io->error("Invalid path: $path");
            return Command::FAILURE;
        }

        $io->title("Scanning $realPath for deprecated Database API usage...");

        $finder = new Finder();
        $finder->files()
            ->in($realPath)
            ->name('*.php')
            ->name('*.module')
            ->name('*.inc')
            ->name('*.install')
            ->name('*.theme')
            ->exclude('vendor')
            ->exclude('node_modules');

        $issues = [];
        $totalFiles = 0;

        foreach ($finder as $file) {
            $totalFiles++;
            $content = $file->getContents();
            $lines = explode("
", $content);

            foreach (self::DEPRECATED_FUNCTIONS as $func => $replacement) {
                // Regex to find function call not preceded by 'function ' (definition) or '->' (method call)
                // Matches: db_query(...)
                // Ignores: function db_query(...), $obj->db_query(...)
                if (preg_match_all("/(?<!function\s)(?<!->)\b$func\s*\(/", $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        // Find line number
                        $offset = $match[1];
                        $lineNum = substr_count(substr($content, 0, $offset), "
") + 1;
                        $lineContent = trim($lines[$lineNum - 1]);

                        $issues[] = [
                            'file' => $file->getRelativePathname(),
                            'line' => $lineNum,
                            'function' => $func,
                            'context' => $lineContent,
                            'replacement' => $replacement,
                        ];
                    }
                }
            }
        }

        if (empty($issues)) {
            $io->success("Scan complete. No procedural Database API calls found in $totalFiles files.");
            return Command::SUCCESS;
        }

        $io->warning(sprintf("Found %d instances of deprecated Database API usage in %d files:", count($issues), $totalFiles));

        $rows = [];
        foreach ($issues as $issue) {
            $rows[] = [
                $issue['file'] . ':' . $issue['line'],
                $issue['function'],
                $issue['context'],
                $issue['replacement']
            ];
        }

        $io->table(
            ['Location', 'Function', 'Context', 'Suggested Replacement'],
            $rows
        );

        return Command::FAILURE;
    }
}
