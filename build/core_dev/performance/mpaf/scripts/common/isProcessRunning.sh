#!/bin/sh

# # # # # # # # # # # # # # #
# RETURN CODES
#  0 - successful exit, check stdout for "yes" or "no" 
#      indicating whether the process is running or not, respectively.
#  100 - can't find PID_FILE
#
# # # # # # # # # # # # # # #

baseDir=`dirname $0`

# COMMON FUNCTIONS
COMMON_HOME=${baseDir}/../../scripts/common

if [ -f ${COMMON_HOME}/commonFunctions.sh ];
then
   . ${COMMON_HOME}/commonFunctions.sh
fi

# # # # # # # # # # # # # # #
# Usage message and function
# The usage() function does a sed on this file and ouputs any lines starting
# with a double pound sign (##). All such lines will be printed verbatim 
# # # # # # # # # # # # # # #

## Usage: isProcessRunning.sh -s <pid-file>
##
## Required Parameters
##   -p   PID_FILE is the full path to pid file for the process that is to be checked
##
## Options:
##   -h, --help        Display this message.
##

# # # # # # # # # # # # # # #
# The parseArgs() function iterates over the input parameters and looks
# for specific flags. An unknown parameter will produce the usage message
# # # # # # # # # # # # # # #

parseArgs() {
	[[ $# -eq -0 ]] && usage
	while [ $# -gt 0 ]
	do
		case $1 in
			(-h|--help) usage;;
			(-p) shift; PID_FILE=$1; shift;;
			(-*) usage "unknown option: $1";;
			(*) break;;
		esac
	done
}

#  MAIN 
parseArgs $@

echo pidFile=$PID_FILE > /dev/null

if [ -f ${PID_FILE} ];
then
	PID=`cat ${PID_FILE}`
	if ps -p ${PID} > /dev/null 
	then
		echo "yes"
	else
		echo "no"
	fi
	exit 0
else
	exit 100
fi