#!/bin/bash

# People Management Service - Local Development Server
# Starts PHP built-in server on localhost:8000

echo "Starting People Management Service..."
echo "Server will be available at: http://localhost:8000"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

# Check if .env file exists
if [ ! -f .env ]; then
    echo "Warning: .env file not found!"
    echo "Please create .env file with your configuration"
    echo "See LOCAL_SETUP.md for details"
    echo ""
fi

# Check if shared-auth exists
if [ ! -d shared-auth ]; then
    echo "Warning: shared-auth directory not found!"
    echo "Please set up shared-auth (symlink or copy)"
    echo "See LOCAL_SETUP.md for details"
    echo ""
fi

# Start PHP server
cd public
php -S localhost:8000

