#!/bin/bash

if ! which java > /dev/null; then
  echo 'Installing Java...' &&
  yum -y install java-1.7.0-openjdk
fi &&

if ! php -v | grep 'PHP 5.5' > /dev/null; then
  echo 'Installing php...' &&
  rpm -Uvh https://mirror.webtatic.com/yum/el6/latest.rpm;
  yum -y install php55w php55w-xml
fi &&

if ! [ -x /usr/local/bin/jmeter ]; then
  echo 'Installing jmeter...' &&
  rm -rf apache-jmeter* /usr/local/bin/jmeter &&
  wget http://www.us.apache.org/dist//jmeter/binaries/apache-jmeter-2.13.tgz -O /opt/apache-jmeter-2.13.tgz &&
  tar -xf /opt/apache-jmeter-2.13.tgz -C /opt/ &&
  ln -s /opt/apache-jmeter-2.13/bin/jmeter /usr/local/bin/jmeter
fi &&

echo -e '\033[0;32mSuccess\033[0m' || { echo -e '\033[1;31mFAILED\033[0m'; exit 1; }


