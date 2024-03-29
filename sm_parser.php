#!/usr/bin/php
<?php
 $config_file = $argv[ 1];
 libxml_use_internal_errors( true);
 if ( !( $xmlconf = simplexml_load_file( $config_file))) {
   fwrite( STDERR, 'Can not load xml xonfig file:' . $config_file . "\n");
   var_dump( libxml_get_errors());
   exit( 1);  }
// var_dump( $xmlconf);

 $site = array();
 foreach ( $xmlconf->attributes() as $k=>$v) $site[ $k] = trim( $v);
 $logs = array();
 foreach( $xmlconf->accesslog as $k=>$v) {
   $xa = array();
   foreach ( $v->attributes() as $fk=>$fv) $xa[ $fk] = trim( $fv);
   $logs[] = $xa;
 }
 $filter = array();
 foreach( $xmlconf->filter as $k=>$v) {
   $xa = array();
   foreach ( $v->attributes() as $fk=>$fv) $xa[ $fk] = trim( $fv);
   $filter[] = $xa;
 }
 $ignore_codes = array();
 foreach( $xmlconf->ignore as $k=>$v) {
   $xa = array();
   foreach ( $v->attributes() as $fk=>$fv) $xa[ $fk] = trim( $fv);
   $ignore_codes[] = $xa;
 }
 $alternate = array();
 foreach( $xmlconf->alternate as $k=>$v) {
   $xa = array();
   foreach ( $v->attributes() as $fk=>$fv) $xa[ $fk] = trim( $fv);
   $alternate[] = $xa;
 }
 $priority = array();
 foreach( $xmlconf->priority as $k=>$v) {
   $xa = array();
   foreach ( $v->attributes() as $fk=>$fv) $xa[ $fk] = trim( $fv);
   $priority[] = $xa;
 }

 $Pa = $Aa = array();
 $total_hits = 0;
 $stats = array();
 $stats[ 'errors'] = array();
 $fp_out_d = $fp_out_f = null;
 if ( $site[ 'out_dbg'] != '') $fp_out_d = fopen( $site[ 'out_dbg'], 'w');
 if ( $site[ 'out_flt'] != '') $fp_out_f = fopen( $site[ 'out_flt'], 'w');

 foreach ( $logs as $lk=>$lv) {
   if ( ( $fp = @fopen( $lv[ 'path'], 'r')) === FALSE) {
     fwrite( STDERR, 'Error opening file ' . $lv[ 'path'] . "\n");
     continue;  }
   fwrite( STDERR, 'Processing ' . $lv[ 'path'] . '...');
   while ( !feof( $fp) && ( $b = fgets( $fp, 16384)) !== false) {
     if ( !array_key_exists( 'lines', $stats)) $stats[ 'lines'] = 0;
     $stats[ 'lines']++;
     $La = explode( ' ', $b);
     // exclude bad status codes
     $process = true;
     switch ( $La[ 8]) {
       case 200: break;
       case 301: 
       default:
         // some redirects may be valid
         if ( is_ignore( $ignore_codes, ( int)$La[ 8], $La[ 6])) {
            if ( $fp_out_f) fwrite( $fp_out_f, 'mark valid (code:' . $La[ 8] . '): ' . $La[ 6] . "\n");
	    break;  }
	 $process = false;
	 break;
     }
     if ( !$process) {
       if ( !array_key_exists( $La[ 8], $stats[ 'errors'])) $stats[ 'errors'][ $La[ 8]] = 0;
       $stats[ 'errors'][ $La[ 8]]++;
       if ( $fp_out_d) fwrite( $fp_out_d, 'code:' . ( int)$La[ 8] . ' for ' . $La[ 6] . ' b:' . $b . "\n");
       if ( $fp_out_f) fwrite( $fp_out_f, 'ERR: (code:' . $La[ 8] . '): ' . $La[ 6] . "\n");
       continue;  }
     // get url 'path' part
     $up = @parse_url( $La[ 6], PHP_URL_PATH);
     if ( filter_out( $filter, $La[ 6], $up, $rsn)) {
       if ( $fp_out_f) fwrite( $fp_out_f, 'ERR: (filt:' . $rsn . '): ' . $La[ 6] . "\n");
       if ( !array_key_exists( 'dropped', $stats)) $stats[ 'dropped'] = 0;
       $stats[ 'dropped']++;  continue;  }
     if ( !array_key_exists( 'hits', $stats)) $stats[ 'hits'] = 0;
     $stats[ 'hits']++;
     // resolve alternates if exists
     $lng = find_alternate( $alternate, $up, $upu);
     if ( $lng != '') {
       if ( !array_key_exists( $upu, $Aa)) $Aa[ $upu] = array();
       $Aa[ $upu][ $lng] = $up;  }
     if ( !array_key_exists( $upu, $Pa)) $Pa[ $upu] = 0;
     $Pa[ $upu]++;
     $total_hits++;
   }
   fclose( $fp);
   fwrite( STDERR, "DONE\n");
 }
 if ( $fp_out_d) fclose( $fp_out_d);
 if ( $fp_out_f) fclose( $fp_out_f);
 fwrite( STDERR, '--- Stats' . "\n");
 fwrite( STDERR, 'Lines: ' . $stats[ 'lines'] . "\n");
 foreach ( $stats[ 'errors'] as $k=>$v) fwrite( STDERR, 'Code ' . $k . ': ' . $v . "\n");
 fwrite( STDERR, 'Dropped by filter: ' . $stats[ 'dropped'] . "\n");
 fwrite( STDERR, 'Total URLs: ' . count( $Pa) . "\n");
 fwrite( STDERR, 'Total hits: ' . $total_hits . "\n");
 
 // resolve alternates if exists /
 arsort( $Pa);
 echo( '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
 echo( '<urlset' . "\n");
// echo( ' xmlns="http://www.google.com/schemas/sitemap/0.84"' . "\n");
// echo( ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n");
// echo( ' xsi:schemaLocation="http://www.google.com/schemas/sitemap/0.84' . "\n");
// echo( '         http://www.google.com/schemas/sitemap/0.84/sitemap.xsd">' . "\n");
 echo( ' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n");
 echo( ' xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n");
 foreach ( $Pa as $k=>$v) {
   if ( $k[ 0] != '/') {
     fwrite( STDERR, 'Wrong index in loc: \'' . print_r( $k, true) . "'\n");
     continue;  }
   $hits = $v;
   $koef = ( ( int)( $hits*10000/$total_hits))/10000;
   echo( '<url>' . "\n");
   echo( ' <loc>' . $site[ 'base'] . $k . '</loc>' . "\n");
   if ( $site[ 'priority'] != '') {
     $koef = find_priority( $priority, $k, $koef);
     echo( ' <priority>' . $koef . '</priority>' . "\n");
   }
   if ( array_key_exists( $k, $Aa) && count( $Aa[ $k]) > 0) foreach ( $Aa[ $k] as $lk=>$lv) {
     echo( ' <xhtml:link rel="alternate" hreflang="' . $lk . '" href="' . $site[ 'base'] . $lv . '" />' . "\n");
   }
   echo( '</url>' . "\n");
 }
 echo( '</urlset>' . "\n");

 exit( 0);

function find_priority( &$_priority, $_path, $_koef) {
 foreach ( $_priority as $k=>$v) {
   if ( $v[ 'pattern'] == '') continue;
   if ( $v[ 'value'] == '') continue;
//   $p = preg_quote( $v[ 'pattern'], '/');
   $p = str_replace( '/', '\/', $v[ 'pattern']);
   $m = preg_match( '/' . $p . '/i', $_path);
   if ( $m === FALSE) {
     echo( 'Error in p:' . $p . ' of ' . $_path . "\n");
     exit( 1);  }
   if ( $m) return( $v[ 'value']);
 }
 return( $_koef);  }

function is_ignore( &$_ignore, $_code, $_path) {
 foreach ( $_ignore as $k=>$v) {
   if ( ( int)$v[ 'code'] != $_code) continue;
   if ( $v[ 'pattern'] == '') continue;
//   $p = preg_quote( $v[ 'pattern'], '/');
   $p = str_replace( '/', '\/', $v[ 'pattern']);
   $m = preg_match( '/' . $p . '/i', $_path);
   if ( $m === FALSE) {
     echo( 'Error in p:' . $p . ' of ' . $_path . "\n");
     exit( 1);  }
   if ( $m) return( true);
 }
 return( false);  }

function find_alternate( &$_alternates, $_path, &$_def) {
 $_def = $_path;
 foreach ( $_alternates as $k=>$v) {
   if ( $v[ 'pattern'] == '') continue;
//   $p = preg_quote( $v[ 'pattern'], '/');
   $p = str_replace( '/', '\/', $v[ 'pattern']);
   $m = preg_match( '/' . $p . '/i', $_path, $ma, PREG_OFFSET_CAPTURE);
   if ( $m === FALSE) {
     echo( 'Error in p:' . $p . ' of ' . $_path . "\n");
     exit( 1);  }
   if ( $m == 0) continue;
   $_def = preg_replace( '/' . $p . '/', $v[ 'replace'], $_path);
   return( $v[ 'hreflang']);
 }
 return( NULL);  }
 
// false - ok, true - delete
function filter_out( &$_filter, $_url, $_path, &$_rsn) {
 $_rsn = 'EXT';
 $ext = pathinfo( $_path, PATHINFO_EXTENSION);
 if ( strcasecmp( $ext, 'svg' ) == 0) return( true);
 if ( strcasecmp( $ext, 'css' ) == 0) return( true);
 if ( strcasecmp( $ext, 'js'  ) == 0) return( true);
 if ( strcasecmp( $ext, 'pdf' ) == 0) return( true);
 if ( strcasecmp( $ext, 'png' ) == 0) return( true);
 if ( strcasecmp( $ext, 'gif' ) == 0) return( true);
 if ( strcasecmp( $ext, 'jpg' ) == 0) return( true);
 if ( strcasecmp( $ext, 'tif' ) == 0) return( true);
 if ( strcasecmp( $ext, 'ico' ) == 0) return( true);
 if ( strcasecmp( $ext, 'flv' ) == 0) return( true);
 if ( strcasecmp( $ext, 'woff') == 0) return( true);
 if ( strcasecmp( $ext, 'ttf' ) == 0) return( true);
 if ( strcasecmp( $ext, 'eot' ) == 0) return( true);
 if ( strcasecmp( $ext, 'kmz' ) == 0) return( true);
 if ( strcasecmp( $ext, 'bin' ) == 0) return( true);
 if ( strcasecmp( $ext, 'exe' ) == 0) return( true);
 if ( strcasecmp( $ext, 'rpm' ) == 0) return( true);
 if ( strcasecmp( $ext, 'gz'  ) == 0) return( true);
 if ( strcasecmp( $ext, 'zip' ) == 0) return( true);
 if ( strcasecmp( $ext, 'xml' ) == 0) return( true);
 if ( strcasecmp( $ext, 'swf' ) == 0) return( true);
 if ( strcasecmp( $ext, 'mp4' ) == 0) return( true);
 if ( strcasecmp( $ext, 'cdr' ) == 0) return( true);
 $_rsn = 'EXP';
 foreach ( $_filter as $k=>$v) {
   if ( $v[ 'pattern'] == '') continue;
//   $p = preg_quote( $v[ 'pattern'], '/');
   $p = str_replace( '/', '\/', $v[ 'pattern']);
   $m = preg_match( '/' . $p . '/i', $_path);
   if ( $m === FALSE) {
     echo( 'Error in p:' . $p . ' of ' . $_path . "\n");
     exit( 1);  }
   if ( $m) return( true);
 }
 return( false);  }