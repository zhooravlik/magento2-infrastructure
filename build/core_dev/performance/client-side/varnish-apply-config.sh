#!/bin/bash

echo 'Downloading and replacing varnish config.'

php -f varnish-download.php > varnish.vcl

COMPILER_RESULT=$(varnishd -C -f varnish.vcl 2>&1)

if ! echo $COMPILER_RESULT | grep 'VCC-compiler failed'>/dev/null; then
    \cp varnish.vcl /etc/varnish/default.vcl
else
    echo 'Fatal: Varnish configuration file obtained from Magento instance is broken!';
    echo $COMPILER_RESULT
    exit 2
fi

echo 'Restarting varnish'

sudo service varnish restart
