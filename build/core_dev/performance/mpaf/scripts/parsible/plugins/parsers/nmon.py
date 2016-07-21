import sys, os, socket, re, time, logging
from collections import defaultdict
from plugins.outputs.PerformanceStats import *

#a dictionary of an array of stats, indexed by their stat class; e.g. CPU, MEM, NETWORK, etc...
nmon_stats = defaultdict(list)

#last sample time parsed from the NMON file, should preceed all snapshots
sampleTime = time.localtime()

logger = logging.getLogger('parsible')

#example timestamp from NMON 09:47:18,23-NOV-2013
#expected input is time fields "09:47:18,23-NOV-2013"
def parseDateTime(timeString, dateString):
    global sampleTime #we'll modify the global copy of sampleTime
    dateTime = timeString + "," + dateString

    pTime = time.strptime(dateTime, "%H:%M:%S,%d-%b-%Y")
    sampleTime = time.mktime(pTime)

def defineStats(fields):
    global logger
    statClass = fields[0]
    statNames = fields[1:len(fields)]

    nmon_stats[statClass] = statNames

    logger.debug("Defining Stat Bucket [%s] => %s", statClass, statNames);

def parse_nmon(line, statPrefix):
    global sampleTime, logger

    #print "Now parsing line: " + line

    line=line.rstrip('\r\n')
    fields = line.split(',')

    #the stat bucket is the first field in the CSV
    statClass = fields[0]

    # example timestamp preceeding a snaphot: ZZZZ,T0001,09:47:18,23-NOV-2013
    if (fields[0] == 'ZZZZ'):
        parseDateTime(fields[2], fields[3])

    #have we already parsed the field names for this bucket of stats
    timeFieldRegex = re.compile("T\d+")

    if (nmon_stats.has_key(statClass) & (bool)(timeFieldRegex.match(fields[1]))):
        #get the stat keys for this class of stats
        stat_keys=nmon_stats[statClass]

        #the stat values are 1...len(fields)
        stat_values=fields[1:len(fields)]

        try:
            sampleTime
        except NameError:
            logger.error("Error parsing line %s, sampleTime was not yet defined.  Default to localtime.", line)
            sampleTime = time.localtime()

        metric = MetricInstance()
        metric.host = socket.gethostname()
        metric.application = None
        metric.producer = 'system'
        metric.metricClass = statClass
        metric.metricTime = sampleTime

        for x in range(0, len(stat_keys)):
            if (not (bool)(timeFieldRegex.match(stat_values[x]))):
                metric.addValue(stat_keys[x], stat_values[x])

        #metric.publishToGraphite()
        return metric
    else:
        #define the stats
        defineStats(fields)


