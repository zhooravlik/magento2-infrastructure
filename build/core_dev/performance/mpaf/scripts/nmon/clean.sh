#!/bin/sh
#

NMON_HOME=`dirname $0` #NMON must be installed in the same path as this script
COMMON_HOME=${NMON_HOME}/../../scripts/common
LOG_DIR=${NMON_HOME}/../../logs/nmon
LOG=${LOG_DIR}/clean.log

if [ -f ${COMMON_HOME}/commonFunctions.sh ];
then
   . ${COMMON_HOME}/commonFunctions.sh
fi

if [ ! -d ${LOG_DIR} ]; 
then
  echo "Making log directory ${LOG_DIR}"
  mkdir --mode=777 -p ${LOG_DIR}
fi
if [ ! -f ${LOG} ];
then
  touch ${LOG}
  chmod 777 ${LOG}
fi

if [ $# -eq 1 ]; then
  CLEAN=$1
  echo "Cleaning logs from ${CLEAN}...." | tee -a ${LOG}

  #these are the nmon pid files
  for pid in $CLEAN/nmon*.pid; do
      P=`cat $pid`
     
      if [ "x${P}" = "x" ];
      then
         continue
      fi

      ps -p ${P} > /dev/null
      if [ $? -eq 0 ]; then
        logMsg "Found NMON PID File ${pid}, killing NMON PID: ${P}"
        kill -USR2 ${P}
      fi
      rm ${pid}
  done

  #these are the parsible pid files
  for pid in $CLEAN/parsible*.pid; do
      P=`cat $pid`

      if [ "x${P}" = "x" ];
      then
        continue
      fi

      ps -p ${P} > /dev/null
      if [ $? -eq 0 ]; then
        logMsg "Found Parsible PID File ${pid}, killing Parsible PID: ${P}"
        kill -USR1 ${P}
      fi
      rm ${pid}
  done

  #now clean the data files
  for file in $CLEAN; do
      logMsg "Cleaning NMON File [rm -R $file]" | tee -a ${LOG}
      rm -r $file
  done

  if [ -d $CLEAN ] 
  then 
     rmdir $CLEAN
  fi
else
  echo "Invalid arguments, Usage: clean.sh <dir|file>" | tee -a ${LOG}
fi
