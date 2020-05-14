<?php
return [
	// +----------------------------------------------------------------------
    // | 文件上传配置
    // +----------------------------------------------------------------------
    // 图片
    'image' => [
        'savekey'  => '/uploads/image/{first}/{second}/{filemd5}{.suffix}',
        'maxsize'  => '5mb',// 5M
        'mimetype' => 'jpg,png,jpeg,gif,bmp,ico,swf',// *表示允许所有
    ],
    // 视频
    'video' => [
        'savekey'  => '/uploads/video/{first}/{second}/{filemd5}{.suffix}',
        'maxsize'  => '20mb',// 20M
        'mimetype' => 'avi,wmv,mpeg,mp4,mov,mkv,flv,f4v,m4v,rmvb,rm,3gp,dat,ts,mts,vob',// *表示允许所有
    ],
    // 音频
    'audio' => [
        'savekey'  => '/uploads/audio/{first}/{second}/{filemd5}{.suffix}',
        'maxsize'  => '10mb',// 10M
        'mimetype' => 'cd,ogg,mp3,asf,wma,wav,mp3pro,rm,real,ape,module,midi,vqf',// *表示允许所有
    ],
];
