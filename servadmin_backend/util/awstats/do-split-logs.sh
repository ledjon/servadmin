#!/bin/bash

cd /var/log/apache/vhost

/usr/bin/split-logfile < ../access_log
