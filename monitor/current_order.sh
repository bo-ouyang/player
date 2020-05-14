#!/bin/bash
ps_qty=`ps aux | grep '/data/web/sdrpay.vip/monitor/current_order.sh' | grep -v grep | awk '{ print  }' | head -20 | wc -l`
let ps_qty=$ps_qty-2
echo the current number of script current_order monitoring processes is $ps_qty
if [ $ps_qty -gt 0 ];then
{
	echo script current_order monitor is running,bye...
	exit 0
}
fi
while [ 1 ]; do

port=`ps aux | grep '/data/web/sdrpay.vip/think order current' | grep -v grep | awk '{ print  }' | head -20 | wc -l`

if [ $port -lt 1 ];then
{
	/usr/bin/php /data/web/sdrpay.vip/think order current
	echo script current_order restart at `date`
}
fi
sleep 5
done
