#!/bin/sh
#
NMON_EXE=nmon

WHOAMI=`whoami`

#try to kill this users nmon processes, we assume only 1 test runs at a time
CMD="-u ${WHOAMI} ${NMON_EXE}"

#try to kill this user's instances of NMON
pkill ${CMD}
NMON_INSTANCES=`pgrep ${CMD}`

while [ "x${NMON_INSTANCES}" != "x" ]; do
  sleep 3
  pkill ${CMD}
  NMON_INSTANCES=`pgrep ${CMD}`
done
