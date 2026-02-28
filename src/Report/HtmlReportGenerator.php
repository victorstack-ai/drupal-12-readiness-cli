<?php

namespace Drupal12Readiness\Report;

class HtmlReportGenerator
{
    /**
     * Generate an HTML report from scan results.
     *
     * @param array<int, array{file: string, line: int, function: string, context: string, replacement: string}> $issues
     *   A list of detected issues.
     * @param string $scannedPath
     *   The path that was scanned.
     * @param int $totalFiles
     *   The total number of files scanned.
     * @param string $commandName
     *   The command that generated the report (e.g. "check:db-api" or "scan").
     *
     * @return string
     *   The generated HTML content.
     */
    public function generate(array $issues, string $scannedPath, int $totalFiles, string $commandName): string
    {
        $date = date('Y-m-d H:i:s');
        $issueCount = count($issues);
        $statusClass = $issueCount > 0 ? 'has-issues' : 'clean';
        $statusLabel = $issueCount > 0
            ? "$issueCount issue(s) found"
            : 'No issues found';

        $tableRows = '';
        foreach ($issues as $index => $issue) {
            $rowClass = ($index % 2 === 0) ? 'even' : 'odd';
            $file = htmlspecialchars($issue['file'] . ':' . $issue['line'], ENT_QUOTES, 'UTF-8');
            $function = htmlspecialchars($issue['function'], ENT_QUOTES, 'UTF-8');
            $context = htmlspecialchars($issue['context'], ENT_QUOTES, 'UTF-8');
            $replacement = htmlspecialchars($issue['replacement'], ENT_QUOTES, 'UTF-8');

            $tableRows .= <<<ROW
                        <tr class="{$rowClass}">
                            <td class="location">{$file}</td>
                            <td class="function"><code>{$function}</code></td>
                            <td class="context"><code>{$context}</code></td>
                            <td class="replacement">{$replacement}</td>
                        </tr>

            ROW;
        }

        $scannedPathEscaped = htmlspecialchars($scannedPath, ENT_QUOTES, 'UTF-8');
        $commandNameEscaped = htmlspecialchars($commandName, ENT_QUOTES, 'UTF-8');

        $tableSection = '';
        if ($issueCount > 0) {
            $tableSection = <<<TABLE
                <table>
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Function</th>
                            <th>Context</th>
                            <th>Suggested Replacement</th>
                        </tr>
                    </thead>
                    <tbody>
            {$tableRows}
                    </tbody>
                </table>
            TABLE;
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Drupal 12 Readiness Report</title>
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #f5f7fa;
                    color: #333;
                    line-height: 1.6;
                    padding: 2rem;
                }
                .container { max-width: 1200px; margin: 0 auto; }
                h1 {
                    color: #1a73e8;
                    margin-bottom: 0.5rem;
                    font-size: 1.8rem;
                }
                .meta {
                    color: #666;
                    margin-bottom: 1.5rem;
                    font-size: 0.9rem;
                }
                .meta span { margin-right: 1.5rem; }
                .summary {
                    padding: 1rem 1.5rem;
                    border-radius: 8px;
                    margin-bottom: 2rem;
                    font-weight: 600;
                    font-size: 1.1rem;
                }
                .summary.has-issues {
                    background: #fff3cd;
                    border-left: 4px solid #ffc107;
                    color: #856404;
                }
                .summary.clean {
                    background: #d4edda;
                    border-left: 4px solid #28a745;
                    color: #155724;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    background: #fff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                th {
                    background: #1a73e8;
                    color: #fff;
                    padding: 0.75rem 1rem;
                    text-align: left;
                    font-weight: 600;
                    font-size: 0.85rem;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                }
                td {
                    padding: 0.65rem 1rem;
                    border-bottom: 1px solid #eee;
                    font-size: 0.9rem;
                    vertical-align: top;
                }
                tr.odd { background: #f9fafb; }
                tr.even { background: #fff; }
                tr:hover { background: #e8f0fe; }
                .location { font-family: monospace; white-space: nowrap; color: #d63384; }
                code {
                    background: #f1f3f5;
                    padding: 0.15rem 0.4rem;
                    border-radius: 3px;
                    font-size: 0.85rem;
                }
                .footer {
                    margin-top: 2rem;
                    text-align: center;
                    color: #999;
                    font-size: 0.8rem;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Drupal 12 Readiness Report</h1>
                <div class="meta">
                    <span>Command: <strong>{$commandNameEscaped}</strong></span>
                    <span>Path: <strong>{$scannedPathEscaped}</strong></span>
                    <span>Files scanned: <strong>{$totalFiles}</strong></span>
                    <span>Generated: <strong>{$date}</strong></span>
                </div>
                <div class="summary {$statusClass}">{$statusLabel}</div>
                {$tableSection}
                <div class="footer">
                    Generated by <strong>Drupal 12 Readiness CLI</strong> v1.0.0
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Write the HTML report to a file.
     *
     * @param string $html
     *   The HTML content to write.
     * @param string $outputPath
     *   The file path to write to.
     *
     * @return string
     *   The absolute path of the written file.
     */
    public function writeToFile(string $html, string $outputPath): string
    {
        file_put_contents($outputPath, $html);
        return realpath($outputPath) ?: $outputPath;
    }
}
