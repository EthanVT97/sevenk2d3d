#!/bin/bash

# Check if nginx is running
if ! pgrep nginx > /dev/null; then
    echo "Nginx is not running"
    exit 1
fi

# Try to fetch the index page
response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:$PORT)

if [ "$response" = "200" ]; then
    echo "Health check passed"
    exit 0
else
    echo "Health check failed with status code: $response"
    exit 1
fi 