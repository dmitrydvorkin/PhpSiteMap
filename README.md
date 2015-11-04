# PhpSiteMap
PHP sitemap generator using Apache access log data


## sm_notify.php

Sends the notification to search engines about the location of your SiteMap.

Usage:
```
sm_notify.php sm_notify.conf <SitemapURL>
```
Requires: php_curl, php_simplexml.

Exit code: == 0 - success, != 0 - error.

Output: STDOUT - programm execution information.

Run example:
```
# ./sm_notify.php ./sm_notify.conf http://myhost.com/googlemap.myhost.com.xml
Google  : OK (200)
Yandex  : ERR (301)
Bing    : OK (200)
Yahoo   : ERR (0)
Baidu   : ERR (301)
Ask     : ERR (0)

```

## sm_parser.php

Parses Apache access log and creates SiteMap XML.

Usage:
```
sm_parser.php <SiteMapConfig>
```

Requires: php_simplexml.

Exit code: == 0 - success, != 0 - error.

Output: STDOUT - SiteMap contents, STDERR - programm execution information.

Run example:
```
# ./sm_parser.php ./sm.myhost.conf > /var/www/myhost.com/map.xml
Processing /var/www/myhost.com-log...DONE
--- Stats
Lines: 74725
Code 301: 3492
Code 404: 6957
Code 304: 301
Code 206: 65
Code 401: 538
Code 302: 253
Code 423: 92
Code 403: 26
Code 500: 1
Code 405: 8
Code /: 1
Dropped by filter: 37396
Total URLs: 1371
Total hits: 25594
```

## SiteMapConfig

Is an sm_parser.php xml configuration file. General structure:
```
<site base="siteBaseUrl" [site_options]>

 <accesslog path="apachelog0" />
 <accesslog path="apachelog1" />
 ...
 <accesslog path="apachelogN" />

 <!-- pattern is the PREG pattern for URL path -->
 <!-- ignore URL pathes matches patterns -->
 <filter action="drop" type="regexp" pattern=".*test.php" />
 <filter action="drop" type="regexp" pattern="^/robots.*" />

 <!-- ignore BAD (non-200) http code for matching URL path patterns below -->
 <!-- for example some documentation system may use internal redirects -->
 <ignore code="301" pattern="/docs/.*.html" />
 <ignore code="304" pattern="/docs/.*.html" />

 <!-- group URL pathes by language -->
 <!-- for example, our default language is EN, without prefix -->
 <!-- all non-default translated pages have the same paths, -->
 <!-- but with language prefix -->
 <!-- page: /technology/solutions.html -->
 <!-- Russian translated page: /ru/technology/solutions.html -->
 <!-- Chineese translated page: /zh/technology/solutions.html -->
 <!-- group all of them into one group of hits and mark -->
 <!-- translated versions as 'alternate' -->
 <alternate pattern="^(/ru)/" replace="/" hreflang="ru" />
 <alternate pattern="^(/zh)/" replace="/" hreflang="zh" />

</site>
```

Where:

siteBaseUrl is the base URL for all paths. Ex.: "http://host.com"

Other [site options]:

priority="true" : add hits koefficient to each location found.

out_dbg="./dbg_file.log" : output information for each non-200 parsed line

out_flt="./flt_file.log" : output filter processing information

## Processing speed

Highly depends on two factors:

- debug enabled;
- patterns number and complexity.

In tests:

- 16 patterns, full debug enabled and 18Mb of access log (78000 lines): 5 seconds
