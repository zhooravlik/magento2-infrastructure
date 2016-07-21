from plugins.outputs.PerformanceStats import MetricInstance

def process_perfstat(line):

    if (isinstance(line, MetricInstance)):
        line.publish()
    
