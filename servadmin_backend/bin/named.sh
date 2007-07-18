#!/bin/sh

# $Id: named.sh,v 1.2 2005/11/04 05:18:10 ledjon Exp $

# good god, this is truely bare-bones ;p
# by Jon Coulter

if [ -e "/etc/init.d/bind9" ]; then
	exec /etc/init.d/bind9 $*	
fi

named=`which named`

[ "$named" = "" ] && exit 1;

#[ "$1" = "start" ] && $named -u bind -g bind;
[ "$1" = "start" ] && $named -u bind;
[ "$1" = "stop" ] && killall named;

#exit $?;
exit 0;
