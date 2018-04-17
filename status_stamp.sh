#!/bin/bash                                                                                  

## create an overall health page that can be externally monitored                            
## first line of output MUST be on of the following: OK, WARNING, CRITICAL, UNKNOWN          
## timestamp:<value> is required but can be anywhere in the output                           

now=`date +%s`
overall_status=0

## check that required processes are running                                                 

cmsdstat=`ps -e | grep cmsd | awk '{print $4}' | tail -1`
if [ $cmsdstat ]; then
   cstat="yes"
else
   overall_status=2
   cstat="no"
fi

xrdstat=`ps -e | grep xrootd | awk '{print $4}' | tail -1`
if [ $xrdstat ]; then
   xstat="yes"
else
   overall_status=2
   xstat="no"
fi

kern=`uname -r`
utime=` cat /proc/uptime | awk '{ print $1 }'`

if [ $overall_status -eq 0 ]; then
   echo "OK" > /var/www/html/stamp
fi
if [ $overall_status -eq 1 ]; then
   echo "WARNING" > /var/www/html/stamp
fi

if [ $overall_status -eq 2 ]; then
   echo "CRITICAL" > /var/www/html/stamp
fi
echo "timestamp:$now"  >> /var/www/html/stamp
human=`date`
echo "generated:$human"  >> /var/www/html/stamp
echo "uptime:$utime"  >> /var/www/html/stamp
echo "kernel:$kern"  >> /var/www/html/stamp
echo "cmsd_running :$cstat" >> /var/www/html/stamp
echo "xrootd_running :$xstat" >> /var/www/html/stamp

