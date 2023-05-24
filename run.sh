#!/bin/bash

CPU=$1
export stack=100
# php ./cli.php -- -s {} -l ${max} -m product

seq 500 ${stack} 500000 | xargs -P${CPU} -I{} sh -c 'max=$(({} + ${stack}));echo "stack: {} ${max}"; php ./cli.php -- -s {} -l ${max} -m product'
