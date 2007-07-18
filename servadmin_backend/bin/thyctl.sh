#!/bin/sh

LISTEN="0.0.0.0/81"
#LISTEN="127.0.0.1/81"

# probably don't need to change
PID="/usr/local/servadmin/var/run/thy.pid"

[ "$THY" = "" ] && THY="/usr/local/servadmin/usr/sbin/thy"

# thy -o cgi --add-handler .php=/usr/bin/php --uid=0 --webroot=/sysadmin -l/90 -i index.php

start() {
	$THY -o cgi,dirindex \
        -w /usr/local/servadmin/usr/htdocs -U 0 \
        -H .php=/usr/local/servadmin/usr/bin/php \
	-H .x=/usr/local/servadmin/usr/src/phttpd/testin.pl \
        -i index.html \
        -P $PID \
	-l $LISTEN 

       exit $?
}

stop() {
	kill `cat $PID 2>/dev/null` 2>/dev/null

	exit $?
}

[ "$1" = "start" ] && start;
[ "$1" = "stop" ] && stop;

echo "Usage: $0 {start|stop}";
exit 1;
