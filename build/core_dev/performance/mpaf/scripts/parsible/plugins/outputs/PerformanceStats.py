import time, socket, logging, re
from types import *

class MetricInstance(object):
    """A class that describes one or more performance metrics"""        

    graphiteHost = 'stage2lp48.qa.paypal.com'
    graphitePort = 2003

    #what host does this metric describe, can be None
    host = None

    #what application does this metric describe, can be None
    application = None
   
    #who produced this stat; e.g. NMON, JMETER, CAL, etc...
    producer = None

    #what class of stat is this; e.g CPU is a class of metrics while %user is a metric
    metricClass = None

    #the time the metric was captured
    metricTime = None

    #the list of metrics at this point in time, where whe have a class of metrics we
    #may have multiple metrics at one time; e.g. NMON-CPU %system, %user, %idle, etc...
    values = {}

    #regex to sanitize the metrics we send to graphite
    reSanitizeMetric = None

    def __init__(self):
        self.host = None
        self.application = None
        self.producer = None
        self.metricClass = None
        self.metricTime = None
        self.values = {}
        self.logger = logging.getLogger('parsible')

        self.reSanitizeMetric = re.compile("r'([^\.\w])+'")

    def addValue(self, statName, statValue):
        self.values[statName] = statValue

    def clearValues(self):
        self.values.clear()

    def toString(self):
        print "-------------------------"
        self.printIfDefined("Time=>", self.metricTime)
        self.printIfDefined("Host=>", self.host)
        self.printIfDefined("Producer=>", self.producer)
        self.printIfDefined("App=>", self.application)
        self.printIfDefined("MetricClass=>", self.metricClass)

        for k, v in self.values.items():
            print "\t" + k + "=>" + v
    
    def printIfDefined(self, description, variable):
        if self.isDefined(variable):
            print variable

    def isDefined(self, variable):
        try:
            if variable is not None:
                return True
        except NameError:
            return False
        else:
            return False


    def dumpMetricStats(self):

        if not self.logger.isEnabledFor(logging.DEBUG):
            return

        id = self.buildGraphiteID()
        metrics = self.buildGraphiteMessage(id)

        self.logger.debug("[dumpMetricStats]\n%s" % metrics)

    def buildGraphiteID(self):
        metricID=[self.host, self.application, self.producer, self.metricClass]
        metricID = filter(None, metricID)
        metricID = [x.lower() for x in metricID]

        #graphite id is '.' delimited fields
        graphiteID = ".".join(metricID)

        #strip offending chars
        return self.sanitizeForGraphite(graphiteID)

    def buildGraphiteMessage(self, graphiteID):

        if not self.isDefined(self.metricTime):
            self.logger.info("metricTime not defined, setting metric time to localtime")

            lt = time.localtime()
            self.metricTime = int(time.mktime(lt))
        
        metricLines = []
        for k, v in self.values.items():
            k=self.sanitizeForGraphite(k)

            #self.logger.debug("key=%s, graphiteID=%s" % (k, graphiteID))

            metricLines.append("%s.%s %s %d" % (graphiteID, k, v, int(self.metricTime)))
       
        graphiteMessage = '\n'.join(metricLines) + '\n' #all lines must end in a newline

        return graphiteMessage

    def publish(self):

        #builds a metric id that is prefixed to each of our stats
        id = self.buildGraphiteID()

        #build the payload with all stats we're sending to Graphite
        payload = self.buildGraphiteMessage(id)

        self.logger.debug("Graphite Payload:\n%s" % payload)

        try:
            s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            s.connect((self.graphiteHost, self.graphitePort))
        except socket.error, (value,message): 
            if s:
                s.close()

            self.logger.error( "Error opening socket to Graphite [%s:%d]: %s" % 
                (self.graphiteHost, self.graphitePort, message) )
            return
        try:
            if len(payload) > 0:
                s.sendall(payload)
            else:
                self.logger.error( "Failed to send metrics to Graphite, no Metrics Found")
        except socket.error, (value,message): 
            self.logger.error( "Error sending metrics to Graphite [%d]: %s" % (value, message) )

    def sanitizeForGraphite(self, string):
        if string is not None:
            s = re.sub(r'([^\.\w%])+', '-', string)
            return s.lower()
        else:
            p
            return None
