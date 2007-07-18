#!/bin/bash

# $Id: start.sh,v 1.7 2007/07/18 17:59:49 ledjon Exp $

# don't forget to set doc_root in your php.ini
# file to match whatever document root (-h) you define here

# for development
php=/usr/local/servadmin/usr/bin/php

if [ "`hostname`" = "intlx1" ]; then
	php=/usr/bin/php-cgi
fi

./phttpd.pl -h /usr/local/servadmin/servadmin_backend/interface \
		-l localhost \
		-H .php=$php,.x=$php \
		-M 10 \
		-F '(CVS|bin|.metadata)' \
		$*
		# ,.x=/usr/local/servadmin/usr/src/phttpd/testin.pl
