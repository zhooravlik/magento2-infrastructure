#!/bin/bash

export BASE_DIR="$1"
export PHPUNIT_COMMAND="${@:2}"

cd ${BASE_DIR}/dev/tests/unit/
execute() {
    dir=$1
    FOLDER=${dir/.\/testsuite\/Magento\//};
    FOLDER=${FOLDER//[^a-zA-Z]/_};
    RESULT="--log-junit ${BASE_DIR}/unit_tests_magento_${FOLDER}.xml"
    echo -e "\nRunning ${dir} tests \n";
    $PHPUNIT_COMMAND $RESULT $dir
}
export -f execute
ls -dX ./testsuite/Magento/* | grep -v _files | grep -v Framework | ${BASE_DIR}/dev/build/bin/parallel --gnu -P 3 'execute {}' || exit 1
ls -dX ./testsuite/Magento/Framework/* | grep -v _files | ${BASE_DIR}/dev/build/bin/parallel --gnu -P 3 'execute {}' || exit 1
cd ${BASE_DIR}

unset -f execute
unset BASE_DIR
unset PHPUNIT_COMMAND