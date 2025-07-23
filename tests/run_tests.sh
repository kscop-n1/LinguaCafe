#!/bin/bash

# Usage: 
# ./run_tests.sh [--skip-language]

CONTAINER_NAME="linguacafe-webserver-dev"
SKIP_LANGUAGE=true
if [[ "$1" == "--with-language-installs" ]]; then
    read -p "This will delete and install every language package again. Are you sure? (y/n): " confirm
    if [[ "$confirm" =~ ^[Yy]$ ]]; then
        SKIP_LANGUAGE=false
    else
        exit
    fi
fi

# tests
if [ "$SKIP_LANGUAGE" = false ]; then
    docker exec -t $CONTAINER_NAME php artisan test --filter=LanguageInstallTest
fi

docker exec -t $CONTAINER_NAME php artisan test --filter=TextImportTest
