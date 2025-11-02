<?php

/**
 * Simple Test Framework
 * Lightweight testing framework following KISS principle
 */

class TestFramework {
    private $tests = [];
    private $results = [];
    private $currentTest = '';

    public function test(string $name, callable $callback): void {
        $this->tests[$name] = $callback;
    }

    public function run(): array {
        echo "\n🧪 Running Tests...\n";
        echo str_repeat("=", 60) . "\n\n";

        $passed = 0;
        $failed = 0;

        foreach ($this->tests as $name => $callback) {
            $this->currentTest = $name;

            try {
                $callback($this);
                $this->results[$name] = ['status' => 'passed', 'message' => ''];
                $passed++;
                echo "✓ {$name}\n";
            } catch (AssertionException $e) {
                $this->results[$name] = ['status' => 'failed', 'message' => $e->getMessage()];
                $failed++;
                echo "✗ {$name}\n";
                echo "  → {$e->getMessage()}\n";
            } catch (Exception $e) {
                $this->results[$name] = ['status' => 'error', 'message' => $e->getMessage()];
                $failed++;
                echo "💥 {$name}\n";
                echo "  → Error: {$e->getMessage()}\n";
            }
        }

        echo "\n" . str_repeat("=", 60) . "\n";
        echo "Results: {$passed} passed, {$failed} failed\n";

        return $this->results;
    }

    // Assertions

    public function assertTrue($condition, string $message = ''): void {
        if (!$condition) {
            throw new AssertionException($message ?: 'Expected true, got false');
        }
    }

    public function assertFalse($condition, string $message = ''): void {
        if ($condition) {
            throw new AssertionException($message ?: 'Expected false, got true');
        }
    }

    public function assertEquals($expected, $actual, string $message = ''): void {
        if ($expected !== $actual) {
            $msg = $message ?: "Expected " . var_export($expected, true) . ", got " . var_export($actual, true);
            throw new AssertionException($msg);
        }
    }

    public function assertNotEquals($expected, $actual, string $message = ''): void {
        if ($expected === $actual) {
            $msg = $message ?: "Expected value to not equal " . var_export($expected, true);
            throw new AssertionException($msg);
        }
    }

    public function assertContains($needle, $haystack, string $message = ''): void {
        if (is_array($haystack)) {
            if (!in_array($needle, $haystack)) {
                throw new AssertionException($message ?: "Array does not contain expected value");
            }
        } elseif (is_string($haystack)) {
            if (strpos($haystack, $needle) === false) {
                throw new AssertionException($message ?: "String does not contain '{$needle}'");
            }
        } else {
            throw new AssertionException("Haystack must be array or string");
        }
    }

    public function assertNotEmpty($value, string $message = ''): void {
        if (empty($value)) {
            throw new AssertionException($message ?: 'Expected non-empty value');
        }
    }

    public function assertNull($value, string $message = ''): void {
        if ($value !== null) {
            throw new AssertionException($message ?: 'Expected null, got ' . var_export($value, true));
        }
    }

    public function assertNotNull($value, string $message = ''): void {
        if ($value === null) {
            throw new AssertionException($message ?: 'Expected non-null value');
        }
    }

    public function assertInstanceOf(string $class, $object, string $message = ''): void {
        if (!($object instanceof $class)) {
            throw new AssertionException($message ?: "Object is not instance of {$class}");
        }
    }

    public function assertCount(int $expected, $array, string $message = ''): void {
        $actual = count($array);
        if ($actual !== $expected) {
            throw new AssertionException($message ?: "Expected count {$expected}, got {$actual}");
        }
    }

    public function assertArrayHasKey($key, $array, string $message = ''): void {
        if (!array_key_exists($key, $array)) {
            throw new AssertionException($message ?: "Array does not have key '{$key}'");
        }
    }
}

class AssertionException extends Exception {}
