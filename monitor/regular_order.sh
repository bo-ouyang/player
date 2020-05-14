#!/bin/bash
ps_qty=`ps aux | grep '/data/web/sdrpay.vip/monitor/regular_order.sh' | grep -v grep | awk '{ print  }' | head -20 | wc -l`
let ps_qty=$ps_qty-2
echo the current number of script regular_order monitoring processes is $ps_qty
if [ $ps_qty -gt 0 ];then
{
	echo script regular_order monitor is running,bye...
	exit 0
}
fi
while [ 1 ]; do

port=`ps aux | grep '/data/web/sdrpay.vip/think order regular' | grep -v grep | awk '{ print  }' | head -20 | wc -l`

if [ $port -lt 1 ];then
{
	/usr/bin/php /data/web/sdrpay.vip/think order regular
	echo script regular_order restart at `date`
}
fi
sleep 5
done
