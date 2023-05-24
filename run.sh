#!/bin/bash

export CPU=$1
export START=$2
export END=$3
export STACK=100

# php ./cli.php -- -s {} -l ${max} -m product

seq ${START} ${STACK} ${END} | \
    xargs -P${CPU} -I{} sh -c \
	'max=$(($(({} + ${STACK})) - 1));echo "stack: {} ${max}"; php ./cli.php -- -s {} -l ${max} -m product -v'
