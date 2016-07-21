#!/bin/sh

WORKING_PATH=`dirname $0`

JMETER_ANALYSIS=${WORKING_PATH}/../../scripts/jmeter/analysis
NMON_ANALYSIS=${WORKING_PATH}/../../scripts/nmon/analysis

# Analyze jmeter files
for jmeterFile in *.jtl
do
    java -Xms1g -Xmx1g -jar ${JMETER_ANALYSIS}/JMeterCsvResultsParser-1.1.0.jar -f $jmeterFile -format "json" -s "|" -t0 120
done

# Analyze nmon files
for nmonFile in *.nmon
do
    java -Xms1g -Xmx1g -jar ${NMON_ANALYSIS}/NmonResultsProcessor-1.1.0.jar -f $nmonFile -format "json" -t0 120
done
