#!/bin/sh
#
INSTALL_HOME=`dirname $0`
COMMON_HOME=${INSTALL_HOME}/../../scripts/common
LOGS_HOME=${INSTALL_HOME}/../../logs/install

LOG=${LOGS_HOME}/export-magento-db.log

if [ -f ${COMMON_HOME}/commonFunctions.sh ]; 
then
   . ${COMMON_HOME}/commonFunctions.sh
else
   echo "Error finding ${COMMON_HOME}/commonFunctions.sh, exiting..."
   exit 100
fi

#
# Set default parameters
#
DB_USER=magento
DB_PASSWORD=magento
DB_NAME=magento
EXPORT_FILENAME=magento-db.sql

#
## A script to generate a test data set based on a one of the predefined profiles.
##
## Usage: generate-test-data.sh [-p profile] [-w web_root]
##
## Required Parameters
##
##    None
##
## Options:
##   -h, --help :   Show this message
##   -u db_user		The database user to use for exporting the database table. (Default: magento)
##   -p db_password	The database user's password. (Default: magento)
##   -n db_name		The name of the database to export. (Default: magento)
##   -f filename	The name of the export file. (Default: magento-db)
##
   
parseArgs() {

  if [ $# -lt 0 ];
  then
    usage
  fi

  while [ $# -gt 0 ]
  do
	case $1 in
		(-h|--help) usage;;
		(-u) shift; export DB_USER=$1; shift;;
		(-p) shift; export DB_PASSWORD=$1; shift;;
		(-n) shift; export DB_NAME=$1; shift;;
		(-f) shift; export EXPORT_FILENAME=$1; shift;;
		(-*) usage "unknown option: $1";;
		(*) break;;
	esac
  done
}

# parse the cmd-line arguments
parseArgs $@

#create our logs & directories with a+rwx privs
createLogFile $LOG "777"

WORKING_DIR=${INSTALL_HOME}/../../working
if [ ! -d ${WORKING_DIR} ]; then
    #logMsg "ERROR: Directory ${WORKING_DIR} does not exist."
    #exit 100
    logMsg "Creating directory ${WORKING_DIR}."
    mkdir ${WORKING_DIR}
fi

DATASET_DIR=${WORKING_DIR}/dataset
if [ ! -d ${DATASET_DIR} ]; then
    logMsg "Creating directory ${DATASET_DIR}."
    mkdir ${DATASET_DIR}
fi

logMsg "Exporting database..."
mysqldump -u ${DB_USER} -p${DB_PASSWORD} ${DB_NAME} | gzip > ${DATASET_DIR}/${EXPORT_FILENAME}.gz

chmod 777 ${DATASET_DIR}/${EXPORT_FILENAME}.gz

logMsg "Done."

