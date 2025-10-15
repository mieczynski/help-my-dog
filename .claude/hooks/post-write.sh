#!/bin/bash

# Hook to format PHP files with PHP CS Fixer after file creation/modification
# This runs after Write, Edit, or NotebookEdit tools are used

set -e

# Get the file path from the hook parameters
FILE_PATH="$1"

# Only process PHP files
if [[ "$FILE_PATH" =~ \.php$ ]]; then
    echo "Formatting PHP file with PHP CS Fixer: $FILE_PATH"

    # Run PHP CS Fixer via Docker
    docker-compose -f /home/lmieczynski/PhpstormProjects/pdm-backend/docker-compose.yml \
        exec -T php \
        vendor/bin/php-cs-fixer fix "$FILE_PATH" \
        --config=/app/.php-cs-fixer.dist.php \
        --quiet \
        2>&1 || {
            echo "Warning: PHP CS Fixer failed for $FILE_PATH"
            exit 0  # Don't fail the hook
        }

    echo "✓ Formatted: $FILE_PATH"
fi

exit 0
