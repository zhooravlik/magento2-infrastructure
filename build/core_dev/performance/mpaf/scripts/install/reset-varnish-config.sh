#!/bin/bash

parseArgs() {

  if [ $# -lt 2 ];
  then
    usage; exit 2
  fi

  while [ $# -gt 0 ]
  do
	case $1 in
		(-h) shift; export HOSTNAME=$1; shift;;
		(-*) usage "unknown option: $1"; exit 2;;
		(*) break;;
	esac
  done
}

usage() {
echo "## Usage: reset-varnish-config.sh -h hostname"
echo "##"
echo "## Required Parameters"
echo "##   -h host    Hostname of the redis server"
echo "##"
echo "## $1"
}

# parse the cmd-line arguments
parseArgs $@

rm -f /etc/varnish/default.vcl > /dev/null

echo "import std;
# The minimal Varnish version is 3.0.5

backend default {
    .host = \"$HOSTNAME\";
    .port = \"80\";
}" > /etc/varnish/default.vcl
