#!/bin/bash

# Test Runner Script
# Simple wrapper for running tests

set -e

echo "🧪 Running Mes Comptes Test Suite..."
echo ""

# Change to script directory
cd "$(dirname "$0")"

# Run tests
php tests/run_tests.php

# Exit with test exit code
exit $?
