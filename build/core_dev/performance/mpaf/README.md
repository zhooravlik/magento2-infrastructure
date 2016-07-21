## Magento Performance Automation Framework

### Overview

The *Magento Performance Automation Framework* is designed to automate many of the common functions and concerns involved in performance testing and analysis.  It has as its primary goal the enablement and support of frequent, automated performance measurements of the Magento product throughout the development life cycle.  The framework, coupled with a properly configured and isolated hardware/software test environment, is intended to provide regular, repeatable and reliable results for characterizing the performance of the product.  A secondary goal is to make these capabilities easily accessible to experienced Performance Engineers, and ultimately, to QA and/or Software Engineers interested in performance testing.

#### The framework is...
* ... a clonable project that includes all necessary tools and scripts to support:
  * the installation and setup of the Magento product on a web node in the test environment,
  * the execution of a performance test script on a load generator in the test environment, and 
  * the collection, analysis and reporting of results produced after one or more performance test runs.
* ... a "work in progress."  It is not complete, but is being developed in an iterative, Agile fashion in order to provide much needed, early support for generating repeatable, reliable test results.  The first iterations will require more manual setup and execution than will be provided in future releases.
* ... built with, and uses, the following tools:
  * *Apache JMeter* is provided as part of this project for use as the load generator.  The embedded version does not include any extensions or customizations.
  * *WPT* and *YSlow* are included for client side performance measurements of page load time and overall page composition.
  * *NMON* is provided as part of this project for use in collecting detailed system level performance metrics, including CPU, disk, memory and network utilization.
  * The *Magento Performance Toolkit* is used for generating the specified target DB profile.

