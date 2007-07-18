#!/bin/bash

# $Id: updatestats.sh,v 1.1 2006/03/02 20:25:18 ledjon Exp $

# Update stats from a crontab
# crontab entry (daily, at 6ami):
# 0 6 * * * /usr/local/servadmin/servadmin_backend/util/awstats/updatestats.sh > /var/log/updatestats.sh.log

cd `dirname $0`

# make sure all the needed config files exist
./create_config_files.pl

# update stats now
awstats_updateall.pl now 
