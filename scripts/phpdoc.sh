#!/usr/bin/env bash
PHPDOC="$(dirname $0)/.phpdoc.phar"

trap cleanup SIGINT SIGTERM ERR EXIT
cleanup() {
    [[ -f .tmp ]] && rm .tmp
}

if ! [[ -f $PHPDOC ]]; then
    wget https://phpdoc.org/phpDocumentor.phar -O .tmp || exit $?
    mv .tmp $PHPDOC
fi

chmod +x $PHPDOC
$PHPDOC $@
exit $?
