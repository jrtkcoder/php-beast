<?php
/**
* 使用说明：首先在config.php 配置相关项，然后运行命令： php myencode.php 可以对指定文件(暂不支持文件夹)进行混淆并加密
* author: jrtkcoder
* date: 2019-07-31
* version: 1.0.0
*/
error_reporting(E_ERROR);
//检测beast.so扩展是否启用
if(!ini_get('beast.enable')){
	echo 'php.ini---->beast.enable must eq "On"!'.PHP_EOL;
	exit;
}
include './enphp/func_v2.php';
if(!is_file('config.php')){
	echo 'config.php not existed!'.PHP_EOL;
	exit;
}
$config = include 'config.php';

if(!is_dir($config['app_dir'])){
	echo 'app_dir not existed!'.PHP_EOL;
	exit;
}
if($config['work_mode'] != 'develop' && $config['work_mode'] != 'deploy'){
	echo 'work_mode must be: develop or deploy .'.PHP_EOL;
	exit;
}
$encypt_app_dir = $config['app_dir'];
//开发模式，加密测试单独保存，防止源码被覆盖
if($config['work_mode'] == 'develop'){
	$encypt_app_dir = rtrim($config['app_dir'],'/').'_encode/';
}

//对指定的所有文件进行加密
foreach($config['encode_files'] as $file){
	$src_file = $config['app_dir']. $file;
	if(!is_file($src_file)){
		echo 'src_file: '.$src_file.' not existed!'.PHP_EOL;
		continue;
	}
	$dst_file = $encypt_app_dir. $file;
	$dst_dir = dirname($dst_file);
	if(!is_dir($dst_dir)){
		$result = mkdir($dst_dir,0777,true);
		if(!$result){
			echo 'mkdir dst_dir: '.$dst_dir.' failed!'.PHP_EOL;
			exit;
		}
	}
	if($config['work_mode'] == 'develop'){
		//文件混淆
		if($config['enable_enphp']){
			do_enphp_file($src_file,$dst_file);
			if(!is_file($dst_file)){
				echo 'dst_file: '.$dst_file.' not existed!'.PHP_EOL;
				continue;
			}
			//对混淆文件加密
			if($config['enable_beast']){
				beast_encrypt_file($dst_file,$dst_file,$datetime=0,$config['beast_type']);
			}
		}else{
			//只进行文件加密
			if($config['enable_beast']){
				beast_encrypt_file($src_file,$dst_file,$datetime=0,$config['beast_type']);
			}
		}
		
	}else{
		//文件混淆
		if($config['enable_enphp']){
			do_enphp_file($src_file,$src_file);
		}
		//文件加密
		if($config['enable_beast']){
			if(!is_file($src_file)){
				echo 'src_file: '.$src_file.' not existed!'.PHP_EOL;
				continue;
			}
			beast_encrypt_file($src_file,$src_file,$datetime=0,$config['beast_type']);
		}
	}
}

//文件混淆
function do_enphp_file($src_file,$dst_file){
	$_SERVER['starttime'] = microtime(1);
	$starttime            = explode(' ', $_SERVER['starttime']);
	$_SERVER['time']      = $starttime[1];

	ob_implicit_flush(1);
	$gen_count = 0;
	chdir(dirname($dst_file));
	echo "\r\n", str_repeat("===", 5), "\r\n\r\n";
	$file = $src_file;
	$target_file = $dst_file;
	$options = array(
		//混淆方法名 1=字母混淆 2=乱码混淆
		'ob_function'        => 2,
		//混淆函数产生变量最大长度
		'ob_function_length' => 3,
		//混淆函数调用 1=混淆 0=不混淆 或者 array('eval', 'strpos') 为混淆指定方法
		'ob_call'            => 1,
		//随机插入乱码
		'insert_mess'        => 0,
		//混淆函数调用变量产生模式  1=字母混淆 2=乱码混淆
		'encode_call'        => 2,
		//混淆class
		'ob_class'           => 0,
		//混淆变量 方法参数  1=字母混淆 2=乱码混淆
		'encode_var'         => 2,
		//混淆变量最大长度
		'encode_var_length'  => 5,
		//混淆字符串常量  1=字母混淆 2=乱码混淆
		'encode_str'         => 2,
		//混淆字符串常量变量最大长度
		'encode_str_length'  => 3,
		// 混淆html 1=混淆 0=不混淆
		'encode_html'        => 2,
		// 混淆数字 1=混淆为0x00a 0=不混淆
		'encode_number'      => 1,
		// 混淆的字符串 以 gzencode 形式压缩 1=压缩 0=不压缩
		'encode_gz'          => 1,
		// 加换行（增加可阅读性）
		'new_line'           => 0,
		// 移除注释 1=移除 0=保留
		'remove_comment'     => 1,
		// debug
		'debug'              => 1,
		// 重复加密次数，加密次数越多反编译可能性越小，但性能会成倍降低
		'deep'               => 1,
		// PHP 版本
		'php'                => 7,
	);
	// encode target
	enphp_file($file, $target_file, $options);
	log::info('encoded', $target_file);
/*
	//文件如果包含文件，单文件逐个加密监测会不准确，暂时注释掉
	$old_output = $output = array();
	// run encoded & old script
	echo 'php -d error_reporting=0 "' . $target_file . '"'.PHP_EOL;
	exec('php -d error_reporting=0 "' . $target_file . '"', $output);
	echo 'php -d error_reporting=0 "' . $file . '"'.PHP_EOL;
	exec('php -d error_reporting=0 "' . $file . '"', $old_output);

	$output     = implode("\n", $output);
	$old_output = implode("\n", $old_output);
	$old_output = strtr($old_output, [realpath($file) => realpath($target_file)]);
	// compare result
	if ($old_output == $output) {
		log::info('SUCCESS_TEST');
	} else {
		log::info('FAILURE_TEST');
		echo str_repeat('===', 5);
		echo "\r\nold=", trim($old_output), "\r\n";
		echo str_repeat('===', 5);
		echo "\r\nnew=", trim($output), "\r\n";
		break;
	}
*/	
	
}

//加密单个文件
function beast_encrypt_file($oldfile,$newfile,$datetime=0,$encrypt='DES'){
	switch ($encrypt) {
	case 'DES':
		$type = BEAST_ENCRYPT_TYPE_DES;
		break;
	case 'AES':
		$type = BEAST_ENCRYPT_TYPE_AES;
		break;
	case 'BASE64':
		$type = BEAST_ENCRYPT_TYPE_BASE64;
		break;
	default:
		$type = BEAST_ENCRYPT_TYPE_DES;
		break;
	}

	if (empty($oldfile) || !file_exists($oldfile)) {
		echo "Encrypt file `{$oldfile}' not found!\n";
		exit(1);
	}

	if (empty($newfile)) {

		$paths = explode('.', basename($oldfile));

		$exten = $paths[count($paths)-1];

		unset($paths[count($paths)-1]);

		$name = implode('.', $paths);

		$newfile = dirname($oldfile) . '/' . $name . '_enc.' . $exten;
	}

	echo "Starting encrypt `{$oldfile}' and save to `{$newfile}'\n";
	echo "Expire time: {$datetime}, using encrypt type: $encrypt\n";

	$expire = $datetime ? strtotime($datetime) : 0;

	if (beast_encode_file($oldfile, $newfile, $expire, $type)) {
		echo "Encrypt file success!\n";
	} else {
		echo "Encrypt file failure!\n";
	}
}

//加密文件夹
function beast_encrypt_folder($src_path,$dst_path,$expire=0,$encrypt_type='DES'){
	$src_path     = trim($src_path);
	$dst_path     = trim($dst_path);
	$expire       = trim($expire);
	$encrypt_type = strtoupper(trim($encrypt_type));

	if (empty($src_path) || !is_dir($src_path)) {
		exit("Fatal: source path `{$src_path}' not exists\n\n");
	}

	if (empty($dst_path)
		|| (!is_dir($dst_path)
		&& !mkdir($dst_path, 0777)))
	{
		exit("Fatal: can not create directory `{$dst_path}'\n\n");
	}

	switch ($encrypt_type)
	{
	case 'AES':
		$entype = BEAST_ENCRYPT_TYPE_AES;
		break;
	case 'BASE64':
		$entype = BEAST_ENCRYPT_TYPE_BASE64;
		break;
	case 'DES':
	default:
		$entype = BEAST_ENCRYPT_TYPE_DES;
		break;
	}

	printf("Source code path: %s\n", $src_path);
	printf("Destination code path: %s\n", $dst_path);
	printf("Expire time: %s\n", $expire);
	printf("------------- start process -------------\n");

	$expire_time = 0;
	if ($expire) {
		$expire_time = strtotime($expire);
	}

	$time = microtime(TRUE);

	calculate_directory_schedule($src_path);
	encrypt_directory($src_path, $dst_path, $expire_time, $entype);

	$used = microtime(TRUE) - $time;

	printf("\nFinish processed encrypt files, used %f seconds\n", $used);
}
/**
 * Encode files by directory
 * @author: liexusong
 */

$nfiles = 0;
$finish = 0;

function calculate_directory_schedule($dir)
{
    global $nfiles;

    $dir = rtrim($dir, '/');

    $handle = opendir($dir);
    if (!$handle) {
        return false;
    }

    while (($file = readdir($handle))) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        $path = $dir . '/' . $file;

        if (is_dir($path)) {
            calculate_directory_schedule($path);

        } else {
            $infos = explode('.', $file);

            if (strtolower($infos[count($infos)-1]) == 'php') {
                $nfiles++;
            }
        }
    }

    closedir($handle);
}

function encrypt_directory($dir, $new_dir, $expire, $type)
{
    global $nfiles, $finish;

    $dir = rtrim($dir, '/');
    $new_dir = rtrim($new_dir, '/');

    $handle = opendir($dir);
    if (!$handle) {
        return false;
    }

    while (($file = readdir($handle))) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        $path = $dir . '/' . $file;
        $new_path =  $new_dir . '/' . $file;

        if (is_dir($path)) {
            if (!is_dir($new_path)) {
                mkdir($new_path, 0777);
            }

            encrypt_directory($path, $new_path, $expire, $type);

        } else {
            $infos = explode('.', $file);

            if (strtolower($infos[count($infos)-1]) == 'php'
                && filesize($path) > 0)
            {
                if ($expire > 0) {
                    $result = beast_encode_file($path, $new_path,
                                                $expire, $type);
                } else {
                    $result = beast_encode_file($path, $new_path, 0, $type);
                }

                if (!$result) {
                    echo "Failed to encode file `{$path}'\n";
                }

                $finish++;

                $percent = intval($finish / $nfiles * 100);

                printf("\rProcessed encrypt files [%d%%] - 100%%", $percent);

            } else {
                copy($path, $new_path);
            }
        }
    }

    closedir($handle);
}

