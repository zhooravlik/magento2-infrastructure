#!/bin/bash

################################################################################
# Function: cutAndReplace
# Description: This function cuts out the first line that matches the regular 
#              expression passed in $2 with the line in $3
# Arguments: 
#  $1 - Filename
#  $2 - RegEx to match
#  $3 - Replacement string
################################################################################
cutAndReplace() {
   CUT_FILE=$1
   CUT_EXP=$2
   export CUT_FILE
   export CUT_EXP

   # Find the line of our regular expression
   LINE_NO=
   LINE_NO=`grep -n "$CUT_EXP" "$CUT_FILE" | cut -d: -f1`

   # Make sure the line exists
   if [ ! "X$LINE_NO" = "X" ]; then
     let LINE_NO=LINE_NO-1
     head -n$LINE_NO $1 > $1.sstmp
     echo "$3" >> $1.sstmp

     let LINE_NO=LINE_NO+2
     tail -n +$LINE_NO $1 >> $1.sstmp

     mv $1.sstmp $1
   fi
}   

################################################################################
# Function: cutLine
# Description: This functions cuts out the first line that matches the regex
#              passed in $2
################################################################################
cutLine() {
   CUT_FILE=$1
   CUT_EXP=$2
   export CUT_FILE
   export CUT_EXP

   # Find the line of our regular expression
   LINE_NO=
   LINE_NO=`grep -n "$CUT_EXP" "$CUT_FILE" | cut -d: -f1`

   # Make sure the line exists
   if [ ! "X$LINE_NO" = "X" ]; then
     let LINE_NO=LINE_NO-1
     head -n$LINE_NO $1 > $1.sstmp

     let LINE_NO=LINE_NO+2
     tail -n +$LINE_NO $1 >> $1.sstmp

     mv $1.sstmp $1
   fi
}

################################################################################
# Function: insertAfter
# Description: This function inserts the second string specified $4 lines after
#              the first occurrence of the string specified in the file.
# Arguments : 
#  $1 - Filename
#  $2 - RegEx to match
#  $3 - String to insert
#  $4 - Number of lines to insert after the line
################################################################################
insertAfter() {
   CUT_FILE=$1
   CUT_REGEX=$2
   export CUT_FILE
   export CUT_REGEX

   # Find the line of our regexp
   LINE_NO=
   LINE_NO=`grep -n "$CUT_REGEX" "$CUT_FILE" | cut -d: -f1`

   # Default to the line after if no 4th parameter was passed
   if [ "X$4" = "X" ]; then
     $4 = 1
   fi

   # Make sure the line exists
   if [ ! "X$LINE_NO" = "X" ]; then
     let LINE_NO=LINE_NO+$4
        
     head -n$LINE_NO $CUT_FILE > $CUT_FILE.sstmp
     echo "$3" >> $CUT_FILE.sstmp

     let LINE_NO=LINE_NO+1
     tail -n +$LINE_NO $CUT_FILE >> $CUT_FILE.sstmp

     mv $CUT_FILE.sstmp $CUT_FILE
   fi
}

################################################################################
# Function: insertBefore
# Description: This function inserts the second string specified $4 lines before
#              the first occurrence of the string specified in the file.
# Arguments : 
#  $1 - Filename
#  $2 - String to search for
#  $3 - String to insert
#  $4 - Number of lines to insert before the line
################################################################################
insertBefore() {
   CUT_FILE=$1
   CUT_EXP=$2
   export CUT_FILE
   export CUT_EXP

   # Find the line of our regexp
   LINE_NO=
   LINE_NO=`grep -n "$CUT_EXP" "$CUT_FILE" | cut -d: -f1`

   # Default to the line after if no 4th parameter was passed
   if [ "X$4" = "X" ]; then
     $4 = 1
   fi

   # Make sure the line exists
   if [ ! "X$LINE_NO" = "X" ]; then
     let LINE_NO=LINE_NO-$4

     head -n$LINE_NO $1 > $1.sstmp
     echo "$3" >> $1.sstmp

     let LINE_NO=LINE_NO+1
     tail -n +$LINE_NO $1 >> $1.sstmp

     mv $1.sstmp $1
   fi
}


parseArgs() {

  if [ $# -lt 4 ];
  then
    usage; exit 2
  fi

  while [ $# -gt 0 ]
  do
	case $1 in
		(-h) shift; export HOSTNAME=$1; shift;;
		(-p) shift; export PORT=$1; shift;;
		(-*) usage "unknown option: $1"; exit 2;;
		(*) break;;
	esac
  done
}

usage() {
echo "## Usage: enable-redis.sh -h hostname -p port"
echo "##"
echo "## Required Parameters"
echo "##   -h host    Hostname of the redis server"
echo "##   -p port    port of the redis server"
echo "##"
echo "## $1"
}

# parse the cmd-line arguments
parseArgs $@

if [ -f env.php ]; then
cp -f env.php env.php.backup.before-redis
else
echo "env.php not found, exiting..."
exit 1
fi

insertBefore env.php "'save' => 'db'," "    'save' => 'redis'," 1
insertBefore env.php "'save' => 'db'," "    'save_path' => 'tcp://${HOSTNAME}:${PORT}?timeout=2.5&database=2'," 1

cutLine env.php "'save' => 'db',"

insertBefore env.php "'modules' =>" "  'cache' => [" 1
insertBefore env.php "'modules' =>" "    'frontend' => [" 1
insertBefore env.php "'modules' =>" "      'default' => [" 1
insertBefore env.php "'modules' =>" "        'backend' => 'Cm_Cache_Backend_Redis'," 1
insertBefore env.php "'modules' =>" "        'backend_options' => [" 1
insertBefore env.php "'modules' =>" "          'server' => '${HOSTNAME}'," 1
insertBefore env.php "'modules' =>" "          'port' => '6380'," 1
insertBefore env.php "'modules' =>" "          'persistent' => ''," 1
insertBefore env.php "'modules' =>" "          'database' => 0," 1
insertBefore env.php "'modules' =>" "          'password' => ''," 1
insertBefore env.php "'modules' =>" "          'force_standalone' => 0," 1
insertBefore env.php "'modules' =>" "          'connect_retries' => 1," 1
insertBefore env.php "'modules' =>" "          'read_timeout' => 10," 1
insertBefore env.php "'modules' =>" "          'automatic_cleaning_factor' => 0," 1
insertBefore env.php "'modules' =>" "          'compress_data' => 1," 1
insertBefore env.php "'modules' =>" "          'compress_tags' => 1," 1
insertBefore env.php "'modules' =>" "          'compress_threshold' => 20480," 1
insertBefore env.php "'modules' =>" "          'compression_lib' => 'gzip'," 1
insertBefore env.php "'modules' =>" "          'use_lua' => 0," 1
insertBefore env.php "'modules' =>" "        ]," 1
insertBefore env.php "'modules' =>" "      ]," 1
insertBefore env.php "'modules' =>" "      'page_cache' => [" 1
insertBefore env.php "'modules' =>" "        'backend' => 'Cm_Cache_Backend_Redis'," 1
insertBefore env.php "'modules' =>" "        'backend_options' => [" 1
insertBefore env.php "'modules' =>" "          'server' => '${HOSTNAME}'," 1
insertBefore env.php "'modules' =>" "          'port' => '${PORT}'," 1
insertBefore env.php "'modules' =>" "          'persistent' => ''," 1
insertBefore env.php "'modules' =>" "          'database' => 0," 1
insertBefore env.php "'modules' =>" "          'password' => ''," 1
insertBefore env.php "'modules' =>" "          'force_standalone' => 0," 1
insertBefore env.php "'modules' =>" "          'connect_retries' => 1," 1
insertBefore env.php "'modules' =>" "          'read_timeout' => 10," 1
insertBefore env.php "'modules' =>" "          'automatic_cleaning_factor' => 0," 1
insertBefore env.php "'modules' =>" "          'compress_data' => 1," 1
insertBefore env.php "'modules' =>" "          'compress_tags' => 1," 1
insertBefore env.php "'modules' =>" "          'compress_threshold' => 20480," 1
insertBefore env.php "'modules' =>" "          'compression_lib' => 'gzip'," 1
insertBefore env.php "'modules' =>" "          'use_lua' => 0," 1
insertBefore env.php "'modules' =>" "        ]," 1
insertBefore env.php "'modules' =>" "      ]," 1
insertBefore env.php "'modules' =>" "    ]," 1
insertBefore env.php "'modules' =>" "  ]," 1
