<?php
/**
 * 系统配文件
 * 所有系统级别的配置
 */
return array(
    /* 模板相关配置 */
	'TMPL_PARSE_STRING' => array(
			'__UPLOAD__' => __ROOT__ . '/Uploads/Picture/',
			'__ADDONS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/Addons',
			'__IMG__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/images',
			'__CSS__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/css',
			'__JS__'     => __ROOT__ . '/Public/' . MODULE_NAME . '/js',
			'__FANCYBOX__'     => __ROOT__ . '/Public/' . MODULE_NAME . '/fancybox',
			'__STATIC__'  => __ROOT__ . '/Public/static'
	),
	'WL_INFO' => array(
			'ttkd' => '天天快递',
			'stkd' => '申通快递',
			'ytkd' => '圆通快递',
			'ydkd' => '韵达快递',
			'sfkd' => '顺风快递',
	),
	//不用登陆
	'NO_LOGIN_CONTROLLER'=>array('Api'),
	//不用登录
	'NO_LOGIN_METHOD'	=>array(
		'register','login','test','server','randList','jd','dfk','dfh','dqr','wc','modify','djd','details','tasks','task_rand_list'
	),
	//TOKEN 重复提交设置
	'TOKEN_ON'      =>    true,  // 是否开启令牌验证 默认关闭
	'TOKEN_NAME'    =>    '__hash__',    // 令牌验证的表单隐藏字段名称，默认为__hash__
	'TOKEN_TYPE'    =>    'md5',  //令牌哈希验证规则 默认为MD5
	'TOKEN_RESET'   =>    true,  //令牌验证出错后是否重置令牌 默认为true

	//随机数据库多少条数据
	'RAND_DATA_NUMBER'=>   10,
	
	//
);
