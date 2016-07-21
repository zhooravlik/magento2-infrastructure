import logging, socket, logging
from PerformanceStats import MetricInstance
from plugins.outputs.StatsdClient import StatsdClient

#a dictionary of the XML attributes that can appear in JMeter results
#taken from JMeter docs - http://jmeter.apache.org/usermanual/listeners.html
xmlLabelDict = {
    'by' : 'bytes',
    'de' : 'data-encoding',
    'dt' : 'data-type',
    'ec' : 'error-count', #0 or 1
    'hn' : 'hostname',  #where the sample was generated
    'it' : 'idle-time', #time not spent sampling (milliseconds) (generally 0)
    'lb' : 'label',     #transaction label
    'lt' : 'latency', # time to initial response (milliseconds) - not all samplers support this
    'na' : 'threads-all', #active threads for all thread groups
    'ng' : 'threads-group', #active threads in this group
    'rc' : 'response-code', #e.g. 200
    'rm' : 'response-message', #e.g. OK
    's'  : 'success',  #true | false
    'sc' : 'sample-count', #1, unless multiple samples are aggregated
    't'  : 'elapsed-time', #milliseconds
    'tn' : 'thread-name', #thread name
    'ts' : 'timestamp', #milliseconds since midnight Jan 1, 1970 UTC
    'f'  : 'failure'    #failure, we're synthesizing this stat if 's' != "true"
}

statsdDictMapping = {
    'by' : 'gauge',
    'lt' : 'timer',
    'na' : 'gauge',
    'ng' : 'gauge',
    's'  : 'counter',
    'f'  : 'counter',
    't'  : 'timer',
    'rc' : 'counter'
}

class JMeterMetric (MetricInstance):
   
    #the whitelist of metrics we'll track within a jmeter sample
    whitelist = []

    #the transaction name these metrics describe
    transaction = None
    
    def __init__(self, transaction):
        super(JMeterMetric, self).__init__()    
        
        self.logger = logging.getLogger('parsible')
        self.whitelist = ['by' , 'lt' , 'na', 'ng', 'rc', 's', 'f', 't']
        self.timerMetrics = ['lt', 't']
        self.countMetrics = ['s', 'f', 'rc']
        self.gaugeMetrics = ['na', 'ng']

        self.host = socket.gethostname()
        self.application = None
        self.producer = 'jmeter'
        self.transaction = transaction
        
    def addValue(self, statName, statValue):
        
        if statName in self.whitelist:
            if statName == 's':
                if statValue.lower() == 'true':
                    statValue = 1
                else:
                    #count failing transactions in addition to success
                    statName = 'f'
                    statValue = 1

            MetricInstance.addValue(self, statName, statValue)
        else:
            self.logger.debug("Won't add Metric %s, metric not found in whitelist" % statName)

    def publishToGraphite(self):
        self.logger.info ("JmeterMetric::publishToGraphite()")

    def setTimeMs(self, statTimeMs):
        self.metricTime = int(int(statTimeMs) / 1000)

    def setTransName(self, jmeterTransName):
        self.jmeterTransaction = jmeterTransName

    def publish(self):
        client = StatsdClient()

        prefix = MetricInstance.buildGraphiteID(self)
        for s in self.values:
            mName = "%s.%s.%s" % (prefix, self.transaction, xmlLabelDict[s])
            mName = MetricInstance.sanitizeForGraphite(self, mName)
            mVal = self.values[s]
            mType = statsdDictMapping[s]
           
            #count the return codes based on type of return code
            if s == 'rc': 
                mName = "%s.%s" % (mName, mVal)

            if mType == 'counter':
                client.increment(mName, mVal)
            elif mType == 'gauge':
                client.gauge(mName, mVal)
            elif mType == 'timer':
                client.timing(mName, mVal)

            self.logger.debug("Published statsd timer %s=%s, Type=%s" % (mName, mVal, mType))

           
            
    def dumpMetricStats(self):

        if not self.logger.isEnabledFor(logging.DEBUG):
            return

        prefix = MetricInstance.buildGraphiteID(self)
        
        statList = "Stat Dump:\n"
        for s in self.values:
            mVal = self.values[s]
            mName = "%s.%s.%s" % (prefix, self.transaction, xmlLabelDict[s])
            mName = MetricInstance.sanitizeForGraphite(self, mName)
            mName = "\t%s %s %s\n" % (self.metricTime, mName, mVal)
            mName = mName.lower()
            statList += mName
        
        self.logger.debug(statList)
                 
                                  
        
