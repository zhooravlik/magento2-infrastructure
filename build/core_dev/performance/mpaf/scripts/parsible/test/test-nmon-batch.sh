#!/bin/sh
NMON_SAMPLE=$1
MODE=$2
DEBUG=$3

if [ "x${MODE}" = "xbatch" ]; 
then
    MODE="--batch-mode True"
elif [ "x${MODE}" = "xbatch-reload" ];
then
    MODE="--batch-reload True"
elif [ "x${MODE}" = "x-d" ];
then
    MODE=""
    DEBUG="--debug 1"
fi

if [ "x${DEBUG}" = "x-d" ]; 
then
    DEBUG="--debug 1"
fi

python ../parsible.py --log-file ${NMON_SAMPLE} --parser parse_nmon ${MODE} ${DEBUG} --pid-file /tmp/parsible.pid --debug 1

if [ -f /tmp/parsible.pid ];
then
    rm /tmp/parsible.pid
fi

