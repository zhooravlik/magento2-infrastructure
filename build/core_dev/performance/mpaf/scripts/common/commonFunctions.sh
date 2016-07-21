#!/bin/sh
#
#
# This script provides common functions for the lnp automation framework
#
# 2013/11/06 Sturtevant Created
# 2015/08/06 Avosper Added logic to check for executables in default location of /usr/bin/
#


#log a msg and exit with an error code
# $1 - the msg to log
# $2 - the error code to exit with
exitWithError() {
   msg=$1
   code=$2

   echo
   echo ----------------------------------------------------------------
   echo $msg
   echo ----------------------------------------------------------------
   echo
   exit $code
}

usage() {
  [ "$*" ] && echo ""; echo "$*"; echo ""
  sed -n '/^##/,/^$/s/^## \{0,1\}//p' "$0"
  exit 2
}
verifyExe() {
  exe=$1
  description=$2

  if [ ! -f $exe -a ! -f /usr/bin/$exe ]
  then
     echo "Error verifying executable $exe exists"
  fi
}

# Verify a parameter exists, if it does not then log a message and call usage().
#
# $1 parameter to verify
# $2 descrption which will be logged if the var is not defined
verifyParameter() {
  var=$1
  description=$2

  #we always pass in a description, so we know if $description == "" then the value for var was empty
  if [ "x$description" == "x" ]
  then
    echo
    echo "Error, parameter $var is not defined"
    usage
  fi
}

#
# verify an optional parameter is defined, if not log a warning message
# but continue to run.
#
# $1 parameter to verify
# $2 message to log if the parameter is not defined
# $3 logFile if log file is defined then call logMsg with a log file
#
verifyOptionalParameter() {
  var=$1
  description=$2
  logFile=$3

  #we always pass in a description, so we know if $description == "" then the value for var was empty
  if [ "x$description" == "x" ]
  then
    echo
    echo $var
  fi
}

#
# Verify a file exists, if it does not log a message and then exit with a return code
#
# $1 - File to verify
# $2 - Message to log if file does not exist
# $3 - Return code, if file does not exist
#
verifyFile() {
  file=$1
  msg=$2
  rc=$3

  if [ ! -f $file ];
  then
     echo $msg
     exit $rc
  fi
}

#create a new direction with the supplied mode
#
# $1 - path to create
# $2 - mode (e.g. 777, 775, etc...)
createDirectory() {
  path=$1
  mode=$2

  if [ ! -d $path ]
  then
     logMsg "Creating directory ${path} with mode ${mode}"
     mkdir --mode=${mode} -p ${path}
  else
     logMsg "Won't create directory ${path}, directory already exists"
  fi
}

# create a new file with the supplied mode
#
# $1 - absolute path to file to create
# $2 - mode to create the file with
createLogFile() {
   file=$1
   mode=$2

   dir=`dirname $file`

   #create the directory if needed
   createDirectory $dir $mode

   if [ ! -f $file ]
   then
      logMsg "Creating empty log file ${file} with permissions ${mode}"
      touch $file
      chmod $mode $file
   else
      logMsg "Won't create log file ${file}, file already exists"
   fi
}

#####
# logs a message, with a datestamp, to stdout and tees the output to a log file
#
# $1 msg to log
# $2 debug log file
#####
logMsg() {
        msg=$1
        log=$2

        if [ "x$log" = "x" ];
        then
           echo [`date`] $msg
        else
           echo [`date`] $msg | tee -a $log
	fi
}


