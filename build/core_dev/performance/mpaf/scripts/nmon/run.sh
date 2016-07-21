#!/bin/sh
#
NMON_HOME=`dirname $0`
COMMON_HOME=${NMON_HOME}/../../scripts/common
PARSIBLE_HOME=${NMON_HOME}/../../scripts/parsible

#python home
PATH=/usr/bin:${PATH}
#puts the logs into ${lnp-automation-home}/logs/nmon
LOGS_HOME=${NMON_HOME}/../../logs/nmon

#build the output name
HOSTNAME=`hostname`
DATETIME=`date +"%Y%m%d_%H%M"`
UUID="${HOSTNAME}_${DATETIME}"
OUTPUT=${UUID}.nmon

if [ -f ${COMMON_HOME}/commonFunctions.sh ]; 
then
   . ${COMMON_HOME}/commonFunctions.sh
else
   echo "Error finding ${COMMON_HOME}/commonFunctions.sh, exiting..."
   exit 100
fi

## A script to launch the system monitoring utility NMON
##
## Usage: run.sh <-s samples> <-f frequency (s)> <-o path> [-g]
##
## Required Parameters
##   -s samples:    The total number of samples to take
##   -f frequency:  The frequency,in seconds, at which to sample
##   -o output:     The path to log results
## Options:
##   -h, --help :   Show this message
##
##
   
parseArgs() {

  if [ $# -lt 2 ];
  then
    usage
  fi

  while [ $# -gt 0 ]
  do
	case $1 in
		(-h|--help) usage;;
		(-s) shift; export SAMPLES=$1; shift;;
		(-f) shift; export FREQUENCY=$1; shift;;
		(-o) shift; export DATA_DIR=$1; shift;;
		(-*) usage "unknown option: $1";;
		(*) break;;
	esac
  done
}

# parse the cmd-line arguments
parseArgs $@

verifyExe nmon

verifyParameter $SAMPLES "samples"
verifyParameter $FREQUENCY "frequency"
verifyParameter $DATA_DIR "results path"

#creates the directories if needed
LOG=${LOGS_HOME}/run.log

#create our logs & directories with a+rwx privs
createLogFile $LOG "777"
createDirectory $DATA_DIR "777"

#absolute path to the NMON results file
NMON_OUTPUT=${DATA_DIR}/${OUTPUT}

echo nmon -f -s $FREQUENCY -c $SAMPLES -F ${NMON_OUTPUT} | tee -a ${LOG}
nmon -f -s $FREQUENCY -c $SAMPLES -F ${NMON_OUTPUT} -p > ${DATA_DIR}/nmon.pid&



