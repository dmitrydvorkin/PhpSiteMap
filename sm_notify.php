#!/usr/bin/php
<?php
 $config_file = $argv[ 1];
 $map_url = $argv[ 2];
 if ( $map_url == '') {
   echo( 'Usage: ' . $argv[ 0] . ' <config> <mapurl>' . "\n");
   exit( 1);   }
 
 libxml_use_internal_errors( true);
 if ( !( $xmlconf = simplexml_load_file( $config_file))) {
   echo( 'Can not load xml xonfig file:' . $config_file . "\n");
   var_dump( libxml_get_errors());
   exit( 1);  }
// var_dump( $xmlconf);

 $site = array();
 foreach ( $xmlconf->attributes() as $k=>$v) $site[ $k] = trim( $v);
 $notify = array();
 foreach( $xmlconf->notify as $k=>$v) {
   $xa = array();
   foreach ( $v->attributes() as $fk=>$fv) $xa[ $fk] = trim( $fv);
   $notify[] = $xa;
 }

 foreach ( $notify as $k=>$v) {
   $data = array();
   $data[ 'URL'] = $map_url;
   $r = send_req( $v[ 'url'], $data, $ret);
   echo( $v[ 'name'] . "\t: " . ( $r ? 'OK' : 'ERR') . ' (' . $ret . ')' . "\n");
 }

 exit( 0);

function send_req( $_url, &$_data, &$_ret) {
 // initiate curl and set options
 $ch = curl_init();
 $user_agent = 'Dv sm_notify (https://github.com/dmitrydvorkin)';
 curl_setopt( $ch, CURLOPT_USERAGENT, $user_agent);
 $url = $_url;
 foreach ( $_data as $k=>$v) {
   $url = str_replace( '{' . $k . '}', $v, $url);
 }
 curl_setopt( $ch, CURLOPT_URL, $url);
 curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
 curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
 $data = curl_exec( $ch);
 $headers = curl_getinfo( $ch);
 // close curl
 curl_close( $ch);
 $out = simplexml_load_string( $data);
 // return XML data
 $_ret = $headers[ 'http_code'];
 if ( $headers[ 'http_code'] != '200') return( false);
 return( true);  }