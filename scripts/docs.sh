#!/usr/bin/env bash
cd "$(dirname $0)/.."
./scripts/phpdoc.sh \
    --directory src \
    --target docs \
    --no-interaction \
    --no-ansi
exit $?
