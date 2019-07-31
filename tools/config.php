<?php
return array(
	'work_mode' => 'develop', //develop--开发模式，整个目录copy到deploy目录，在deploy目录加密   deploy--发布模式，直接在项目所在目录加密
	'app_dir' => '/root/lzm/php-beast/php-beast/tools/',//项目根目录
	'enable_enphp' => true,//是否启用enphp混淆
	'enable_beast' => true,//是否启用beast加密
	'beast_type' => 'DES',//beast加密类型，支持：DES，AES，BASE64
	'encode_files' => array( //项目根目录下，需要加密的单个文件，暂不支持目录加密
		't.php',
		'test/hello.php',
		'test/world/world.php',		
	),
);
/*
return array(
	'work_mode' => 'develop', //develop--开发模式，整个目录copy到deploy目录，在deploy目录加密   deploy--发布模式，直接在项目所在目录加密
	'app_dir' => '/data/wwwroot/develop.rltest.cn/1.2.7/',//项目根目录
	'enable_enphp' => true,//是否启用enphp混淆
	'enable_beast' => true,//是否启用beast加密
	'beast_type' => 'DES',//beast加密类型，支持：DES，AES，BASE64
	'encode_files' => array( //项目根目录下，需要加密的单个文件或者文件夹
		'index.php',
		'jrtp/stub.php',
		'jrtp/ThinkPHP/ThinkPHP.php',		
	),
);
*/