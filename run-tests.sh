#!/bin/bash

# Test Runner Script for People Management Service
# This script helps run the test suite

echo "People Management Service - Test Runner"
echo "========================================"
echo ""

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "Error: Composer is not installed. Please install Composer first."
    exit 1
fi

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo "Installing dependencies..."
    composer install
    echo ""
fi

# Check if PHPUnit is available
if [ ! -f "vendor/bin/phpunit" ]; then
    echo "Error: PHPUnit is not installed. Running composer install..."
    composer install
    echo ""
fi

# Run tests
echo "Running test suite..."
echo ""

if [ "$1" == "--coverage" ]; then
    echo "Running tests with coverage report..."
    vendor/bin/phpunit --coverage-html coverage/
    echo ""
    echo "Coverage report generated in coverage/ directory"
elif [ "$1" == "--verbose" ]; then
    vendor/bin/phpunit --verbose
elif [ "$1" == "--forms" ]; then
    echo "Running form tests only..."
    vendor/bin/phpunit tests/Forms
elif [ -n "$1" ]; then
    echo "Running specific test: $1"
    vendor/bin/phpunit "$1"
else
    vendor/bin/phpunit
fi

echo ""
echo "Tests completed!"


