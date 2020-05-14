function imgLoad(obj){
	var obj=(typeof obj=='object')?obj:document.getElementById(obj.substr(1));
		height=document.documentElement.clientHeight,
		imglist=obj.getElementsByTagName('img');
	for(var i=0;i<imglist.length;i++){
		imglist[i].height = 300
	};
	function xsrc(){
		var slltop = document.documentElement.scrollTop||document.body.scrollTop;
		for(var i=0;i<imglist.length;i++){
			if(imglist[i].getAttribute('xsrc') != null && slltop + height >= setTop(imglist[i])){
				setprogress(imglist[i]);
			}
		};
		function setprogress(elem){
			var n=0;
			
			var img = new Image();
			img.onload = function(){
				elem.src = this.src
				elem.removeAttribute('xsrc');
				elem.removeAttribute('height');
				function progress(){
					if(n<20){
						n++;
					}else{
						clearInterval(timer);
					};
					elem.style.opacity=0.05*n;
					elem.style.filter='alpha(opacity='+5*n+')';
				};
				var timer=setInterval(progress,5);
			}
			img.src = elem.getAttribute('xsrc');
				
		};
		/*
		* 获得图片节点位置高度
		*/
		function setTop(elem){
			var top = elem.offsetTop,
				parent = elem.offsetParent;
			while(parent!==null){
				top += parent.offsetTop,
				parent = parent.offsetParent;
			};
			return top;
		};
	};
	/* 运行加载函数 */
	xsrc();
	window.onscroll=function(){ xsrc() };
};
