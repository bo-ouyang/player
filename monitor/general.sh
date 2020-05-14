#!/bin/bash
ps_qty=`ps aux | grep '/data/web/sdrpay.vip/monitor/general.sh' | grep -v grep | awk '{ print  }' | head -20 | wc -l`
let ps_qty=$ps_qty-2
echo the current number of workerman general monitoring processes is $ps_qty
if [ $ps_qty -gt 0 ];then
{
	echo workerman general monitor is running,bye...
	exit 0
}
fi
while [ 1 ]; do

port=`ps aux | grep '/data/web/sdrpay.vip/cli/general.php' | grep -v grep | awk '{ print  }' | head -20 | wc -l`

if [ $port -lt 1 ];then
{
	/usr/bin/php /data/web/sdrpay.vip/cli/general.php start -d
	echo workerman general restart at `date`
}
fi
sleep 5
done
