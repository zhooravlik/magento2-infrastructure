#!/bin/sh

HOME=`dirname $0`
JMETER_CMDRUNNER_JAR=
LOCAL_RESULTS_DIR=
INCLUDE_LABELS_OPTION=
RAMP_UP_OPTION=

## Execution of JMeterPluginsCMD Command Line Tool
##
## List of valid plugin-type:
##    AggregateReport = JMeter's native Aggregate Report, can be saved only as CSV
##    SynthesisReport = mix between JMeter's native Summary Report and Aggregate Report, can be saved only as CSV
##    ThreadsStateOverTime = Active Threads Over Time
##    BytesThroughputOverTime
##    HitsPerSecond
##    LatenciesOverTime
##    PerfMon = PerfMon Metrics Collector
##    ResponseCodesPerSecond
##    ResponseTimesDistribution
##    ResponseTimesOverTime
##    ResponseTimesPercentiles
##    ThroughputVsThreads
##    TimesVsThreads = Response Times VS Threads
##    TransactionsPerSecond
##    PageDataExtractorOverTime
## see - http://jmeter-plugins.org/wiki/JMeterPluginsCMD/ for complete usage

LOCAL_RESULTS_DIR=$1
RAMP_UP_OPTION="--start-offset $2"
INCLUDE_LABELS_OPTION="--include-label-regex true --include-labels $3"
JMETER_CMDRUNNER_JAR=$4

PLUGIN_LIST=("ResponseTimesOverTime" "ResponseTimesPercentiles" "ThreadsStateOverTime" "TransactionsPerSecond" "ResponseTimesDistribution")

for plugin in ${PLUGIN_LIST[@]}
do
    java -jar ${JMETER_CMDRUNNER_JAR} --tool Reporter --generate-png ${LOCAL_RESULTS_DIR}/jmeter-$plugin.png --input-jtl ${LOCAL_RESULTS_DIR}/jmeter-results.jtl --plugin-type $plugin --width 960 --height 540 ${INCLUDE_LABELS_OPTION} ${RAMP_UP_OPTION}
done

java -jar ${JMETER_CMDRUNNER_JAR} --tool Reporter --generate-csv ${LOCAL_RESULTS_DIR}/jmeter-aggregate-results.csv --input-jtl ${LOCAL_RESULTS_DIR}/jmeter-results.jtl --plugin-type AggregateReport ${INCLUDE_LABELS_OPTION} ${RAMP_UP_OPTION}
