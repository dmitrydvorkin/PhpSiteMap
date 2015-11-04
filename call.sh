#!/bin/sh

out_f="googlemap.myhost.com.xml"
out="/var/www/myhost.com/${out_f}"
time /home/dv/sm_parser/sm_parser.php /home/dv/sm_parser/sm.myhost.conf > $out
chown user:group $out
/home/dv/sm_parser/sm_notify.php /home/dv/sm_parser/sm_notify.conf http://myhost.com/${out_f}
