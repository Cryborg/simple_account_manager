#!/usr/bin/env php
<?php

/**
 * Test Runner
 * Executes all tests and generates a report
 */

// Start output buffering to control test output
ob_start();

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║             Mes Comptes - Test Suite Runner                 ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";

$startTime = microtime(true);

// Track all results
$allResults = [];
$totalPassed = 0;
$totalFailed = 0;

// Find all test files
$testDirs = [
    'unit' => __DIR__ . '/unit',
    'integration' => __DIR__ . '/integration'
];

foreach ($testDirs as $type => $dir) {
    if (!is_dir($dir)) {
        continue;
    }

    $testFiles = glob($dir . '/*Test.php');

    if (empty($testFiles)) {
        continue;
    }

    echo "\n";
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ " . strtoupper($type) . " TESTS" . str_repeat(" ", 53 - strlen($type)) . "│\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";

    foreach ($testFiles as $testFile) {
        $testName = basename($testFile, '.php');

        try {
            // Include and run the test
            $testRunner = require $testFile;

            if ($testRunner instanceof TestFramework) {
                $results = $testRunner->run();
                $allResults[$testName] = $results;

                // Count passed/failed
                foreach ($results as $result) {
                    if ($result['status'] === 'passed') {
                        $totalPassed++;
                    } else {
                        $totalFailed++;
                    }
                }
            }
        } catch (Exception $e) {
            echo "\n💥 Error running {$testName}: {$e->getMessage()}\n";
            $totalFailed++;
        }
    }
}

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

// Final summary
echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                        FINAL REPORT                          ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Total Tests Run: " . ($totalPassed + $totalFailed) . "\n";
echo "✓ Passed: {$totalPassed}\n";
echo "✗ Failed: {$totalFailed}\n";
echo "Duration: {$duration}s\n";
echo "\n";

if ($totalFailed === 0) {
    echo "🎉 All tests passed!\n";
    $exitCode = 0;
} else {
    echo "⚠️  Some tests failed. Please review the output above.\n";
    $exitCode = 1;
}

echo "\n";

// Clean up output buffer
ob_end_flush();

exit($exitCode);
