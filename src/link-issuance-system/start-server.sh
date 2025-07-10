#!/bin/bash
# Start the local PHP development server

# Make the script executable if it's not already
if [ ! -x "$0" ]; then
    chmod +x "$0"
fi

# Start the server
php start-local-server.php
