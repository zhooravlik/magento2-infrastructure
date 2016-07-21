#!/bin/sh
JMETER_SAMPLE=$1
DEBUG="--debug 1"
PYTHON_HOME=/usr

${PYTHON_HOME}/bin/python ../parsible.py --log-file ${JMETER_SAMPLE} --parser parse_jmeter --batch-mode 1 ${DEBUG} --pid-file /tmp/parsible.pid --stat-prefix sample-test

if [ -f /tmp/parsible.pid ];
then
    rm /tmp/parsible.pid
fi

