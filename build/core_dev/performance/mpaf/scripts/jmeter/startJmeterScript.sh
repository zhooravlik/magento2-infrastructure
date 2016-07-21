#!/bin/sh

# # # # # # # # # # # # # # #
# RETURN CODES
#  100 - can't find common functions
#  200 - can't find JMETER in expected PATH
#
# # # # # # # # # # # # # # #

# # # # # # # # # # # # # # #
# This shell script executes a jmeter script that exists on the local file
# system and saves the results, and log files, locally.
# # # # # # # # # # # # # # #

# JAVA / PYTHON HOME
export JAVA_HOME=/usr/bin/java
export PYTHON_HOME=/usr
export PATH=${JAVA_HOME}/bin:${PYTHON_HOME}/bin:${PATH}

SCRIPT_PATH=`dirname $0`

#where does parsible live
PARSIBLE_HOME=${SCRIPT_PATH}/../parsible

if [ ! -f ${JMETER_EXEC} ];
then
   FULLPATH=$(dirname $(readlink -e ${JMETER_HOME}))
   echo
   echo ---------------------------------------
   echo "ERROR: Failed to find start script (bin/jmeter.sh) in expcted path: "
   echo "       `readlink -f ${JMETER_HOME}`"
   echo ---------------------------------------
   echo
   exit 200
fi

baseDir=`dirname $0`
debugDir=${baseDir}/../../logs/jmeter
debugLogFile=$debugDir/startJmeterScript.log
jmeterStdout=${debugDir}/jmeter.stdout.log
jmeterStderr=${debugDir}/jmeter.stderr.log

# JMETER_HOME is the location of the jmeter files
JMETER_HOME=""

# RESULTS_PATH is the path where we we will log the results
RESULTS_PATH=""

# SCRIPT is the jmeter script we will execute
SCRIPT=""

# ARGS are the args we'll pass on the JMeter cmd-line
ARGS=""

# Do we run this with no hangup
BLOCK="false"

# COMMON FUNCTIONS
COMMON_HOME=${baseDir}/../../scripts/common

if [ -f ${COMMON_HOME}/commonFunctions.sh ];
then
   . ${COMMON_HOME}/commonFunctions.sh
else
   echo
   echo ----------------------------------------------------------------
   echo "Error finding ${COMMON_HOME}/commonFunctions.sh, exiting..."
   echo ----------------------------------------------------------------
   echo
   exit 100
fi


# # # # # # # # # # # # # # #
# Usage message and function
# The usage() function does a sed on this file and ouputs any lines starting
# with a double pound sign (##). All such lines will be printed verbatim
# # # # # # # # # # # # # # #

## Usage: startJmeterScript.sh [options] -s <jmeter-script.jmx> -o <results output path> -a <1..N JMeter cmd-line args>
##
## Required Parameters
##   -s   SCRIPT       is the JMeter script to execute
##   -o   RESULTS_PATH is the path to log the results including Jmeter Logs and Sampler Results
##   -a   ARGS         1..N arguments passed on the cmd-line to JMeter; e.g. -args -Jprop1 -Dprop2 etc...
##   -j   JMETER_HOME  The root folder for the jmeter application.
##
## Options:
##   -b, --block       Block on completion of this script until the spawned jmeter script finishes
##   -h, --help        Display this message.
##

# # # # # # # # # # # # # # #
# The parseArgs() function iterates over the input parameters and looks
# for specific flags. An unknown parameter will produce the usage message
# # # # # # # # # # # # # # #

parseArgs() {
	[[ $# -eq 0 ]] && usage
	while [ $# -gt 0 ]
	do
		case $1 in
			(-h|--help) usage;;
            (-b|--block) shift; BLOCK="true";;
            (-j) shift; JMETER_HOME=$1; shift;;
			(-s) shift; SCRIPT=$1; shift;;
			(-o) shift; RESULTS_PATH=$1; shift;;
            (-a|--args) shift; ARGS=$@; break;;
           	(-*) usage "unknown option: $1";;
			(*) break;;
		esac
	done
}


#  MAIN
parseArgs $@

#where does jmeter live
JMETER_EXEC=${JMETER_HOME}/bin/jmeter.sh

# create our log files
createLogFile $debugLogFile 777
createLogFile $jmeterStdout 777
createLogFile $jmeterStderr 777

# Add space after last entry if there is one
logMsg " " $debugLogFile
logMsg "Starting Jmeter..." $debugLogFile
logMsg "JMETER_HOME: $JMETER_HOME" $debugLogFile
logMsg "JMeter Script: $SCRIPT" $debugLogFile
logMsg "Results Directory: $RESULTS_PATH" $debugLogFile
logMsg "JMeter Args: $ARGS" $debugLogFile
logMsg "Debug Logs: $debugLogFile" $debugLogFile
logMsg "Blocking until JMeter is finished: ${BLOCK}"
#OK if this already exists
createDirectory $RESULTS_PATH 777

CMD="nohup ${JMETER_EXEC} ${ARGS} -n -t ${SCRIPT} -j ${RESULTS_PATH}/jmeter.log -l ${RESULTS_PATH}/jmeter-results.jtl"

logMsg "Starting JMeter with CMD: ${CMD}" $debugLogFile
logMsg "Redirecting STDOUT / STDERR: $jmeterStdout / $jmeterStderr" $debugLogFile

#start the command in the background, then grab the PID
nohup ${CMD} >> $debugLogFile&

PID=$!
echo ${PID} > ${RESULTS_PATH}/jmeter.pid
echo "${RESULTS_PATH}/jmeter.pid"

logMsg "Started JMeter Process: ${PID}"

if [ ${BLOCK} = "true" ];
then
    logMsg "Blocking until JMeter Test is finished, PID: ${PID}" $debugLogFile
    while ps -p $PID >/dev/null 2>&1; do
        sleep 10;
    done
    logMsg "JMeter Test (${PID}) is finished, exiting now..." $debugLogFile

    #our test is done, so kill the parsible process if one existed
    if [ -f ${RESULTS_PATH}/parsible-jmeter.pid ];
    then
        PARSIBLE_PID=`cat ${RESULTS_PATH}/parsible-jmeter.pid`
        ps -p ${PARSIBLE_PID} > /dev/null

        if [ $? -eq 0 ]; then
            logMsg "Found Parsible Process ${PARSIBLE_PID}, killing now that our test is over..." $debugLogFile
            kill -s USR2 ${PARSIBLE_PID}
        else
            logMsg "Failed to find Parsible Process ${PARSIBLE_PID}, nothing to stop" $debugLogFile
        fi
    fi
fi

# Verify no errors in jmeter.log
if [ -f ${RESULTS_PATH}/jmeter.log ]; then
grep -A10 'ERROR' ${RESULTS_PATH}/jmeter.log
if [ $? -eq 0 ]; then
echo "Error detected in ${RESULTS_PATH}/jmeter.log"
tail -1 ${RESULTS_PATH}/jmeter-results.jtl
fi
else
echo "Unable to locate ${RESULTS_PATH}/jmeter.log file"
fi
