a:123:{i:0;a:3:{i:0;s:14:"document_start";i:1;a:0:{}i:2;i:0;}i:1;a:3:{i:0;s:6:"header";i:1;a:3:{i:0;s:20:"Local DNS/NTP Server";i:1;i:1;i:2;i:1;}i:2;i:1;}i:2;a:3:{i:0;s:12:"section_open";i:1;a:1:{i:0;i:1;}i:2;i:1;}i:3;a:3:{i:0;s:6:"p_open";i:1;a:0:{}i:2;i:1;}i:4;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:101:"EC2 instances can be started and stopped on demand; we don't need to pay for stopped instances. But, ";}i:2;i:36;}i:5;a:3:{i:0;s:11:"strong_open";i:1;a:0:{}i:2;i:137;}i:6;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:69:"restarting an EC2 instance will change the IP address of the instance";}i:2;i:139;}i:7;a:3:{i:0;s:12:"strong_close";i:1;a:0:{}i:2;i:208;}i:8;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:80:", which makes it difficult to run HBase. We can resolve this issue by running a ";}i:2;i:210;}i:9;a:3:{i:0;s:7:"acronym";i:1;a:1:{i:0;s:3:"DNS";}i:2;i:290;}i:10;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:111:" server to provide a name service to all EC2 instances in our HBase cluster. We can update name records on the ";}i:2;i:293;}i:11;a:3:{i:0;s:7:"acronym";i:1;a:1:{i:0;s:3:"DNS";}i:2;i:404;}i:12;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:53:" server every time other EC2 instances are restarted.";}i:2;i:407;}i:13;a:3:{i:0;s:7:"p_close";i:1;a:0:{}i:2;i:460;}i:14;a:3:{i:0;s:13:"section_close";i:1;a:0:{}i:2;i:463;}i:15;a:3:{i:0;s:6:"header";i:1;a:3:{i:0;s:31:"Local Domain Name Server Config";i:1;i:2;i:2;i:463;}i:2;i:463;}i:16;a:3:{i:0;s:12:"section_open";i:1;a:1:{i:0;i:2;}i:2;i:463;}i:17;a:3:{i:0;s:12:"preformatted";i:1;a:1:{i:0;s:954:"CONFIGURE BIND9
We put all bind9 configuration files under conf/bind.
To configure bind9 on Amazon EC2,
  - Replace your /etc/bind/named.conf.local and /etc/bind/named.conf.options with the same files under conf/bind.
  - Put zone files (db.hbase-admin-cookbook.com & db.10) under /var/lib/bind
  - Generate your secret key
    $ dnssec-keygen -a HMAC-MD5 -b 512 -n USER your.domain.com.
    this will create two files like this:
      Kuser.hbase-admin-cookbook.com.+157+44141.key
      Kuser.hbase-admin-cookbook.com.+157+44141.private
    Replace secret key of named.conf.local to the content of generated private key.
  - Change domain name to your domain name for all the configuration files
  - Change IP of DNS server to your server address
  - Change forwarder address to address of DNS of your EC2 region
  - Restart bind9 by typing /etc/init.d/bind9 restart
    You must increment the serial number every time you make changes to the zone file.";}i:2;i:506;}i:18;a:3:{i:0;s:13:"section_close";i:1;a:0:{}i:2;i:1495;}i:19;a:3:{i:0;s:6:"header";i:1;a:3:{i:0;s:10:"NTP Config";i:1;i:2;i:2;i:1495;}i:2;i:1495;}i:20;a:3:{i:0;s:12:"section_open";i:1;a:1:{i:0;i:2;}i:2;i:1495;}i:21;a:3:{i:0;s:12:"preformatted";i:1;a:1:{i:0;s:293:"CONFIGURE NTP
We put all NTP configuration files under conf/ntp.
To configure NTP server on Amazon EC2
  - Use ntp.conf.server as template
  - Replace NTP servers to public NTP servers of your location
  - Put ntp.conf.server as /etc/ntp.conf
  - Restart ntp daemon
    /etc/init.d/ntp restart";}i:2;i:1517;}i:22;a:3:{i:0;s:12:"preformatted";i:1;a:1:{i:0;s:292:"To configure NTP client on Amazon EC2
  - Use ntp.conf.client as template
  - Replace server setting to IP address of your NTP server
  - Replace restrict setting to IP address of your NTP server
  - Put ntp.conf.client as /etc/ntp.conf
  - Restart ntp daemon
    /etc/init.d/ntp restart
    ";}i:2;i:1828;}i:23;a:3:{i:0;s:13:"section_close";i:1;a:0:{}i:2;i:2138;}i:24;a:3:{i:0;s:6:"header";i:1;a:3:{i:0;s:39:"What needs to be done with new instance";i:1;i:2;i:2;i:2138;}i:2;i:2138;}i:25;a:3:{i:0;s:12:"section_open";i:1;a:1:{i:0;i:2;}i:2;i:2138;}i:26;a:3:{i:0;s:10:"listo_open";i:1;a:0:{}i:2;i:2189;}i:27;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:1;}i:2;i:2189;}i:28;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:2189;}i:29;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:63:" NTP client setup and setting NTP server IP on netp.conf.client";}i:2;i:2193;}i:30;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:2256;}i:31;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:2256;}i:32;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:1;}i:2;i:2256;}i:33;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:2256;}i:34;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:1:" ";}i:2;i:2260;}i:35;a:3:{i:0;s:7:"acronym";i:1;a:1:{i:0;s:3:"DNS";}i:2;i:2261;}i:36;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:6:" setup";}i:2;i:2264;}i:37;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:2270;}i:38;a:3:{i:0;s:10:"listo_open";i:1;a:0:{}i:2;i:2270;}i:39;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:2;}i:2;i:2270;}i:40;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:2270;}i:41;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:43:" /root/bin contains following shell script.";}i:2;i:2276;}i:42;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:2319;}i:43;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:2319;}i:44;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:2;}i:2;i:2319;}i:45;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:2319;}i:46;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:1:" ";}i:2;i:2325;}i:47;a:3:{i:0;s:12:"externallink";i:1;a:2:{i:0;s:96:"https://github.com/uprush/hac-book/blob/master/1-setting-up-hbase-cluster/script/ec2-hostname.sh";i:1;N;}i:2;i:2326;}i:48;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:2422;}i:49;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:2422;}i:50;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:2;}i:2;i:2422;}i:51;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:2422;}i:52;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:26:" /root/etc contains dnskey";}i:2;i:2428;}i:53;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:2454;}i:54;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:2454;}i:55;a:3:{i:0;s:11:"listo_close";i:1;a:0:{}i:2;i:2454;}i:56;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:2454;}i:57;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:1;}i:2;i:2454;}i:58;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:2454;}i:59;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:106:" Master needs to register openssh formatted private key for passphrase-less ssh communication with slaves.";}i:2;i:2458;}i:60;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:2564;}i:61;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:2564;}i:62;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:1;}i:2;i:2564;}i:63;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:2564;}i:64;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:25:" /etc/dhcp/dhclient.conf ";}i:2;i:2568;}i:65;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:2593;}i:66;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:2593;}i:67;a:3:{i:0;s:11:"listo_close";i:1;a:0:{}i:2;i:2593;}i:68;a:3:{i:0;s:13:"section_close";i:1;a:0:{}i:2;i:2596;}i:69;a:3:{i:0;s:6:"header";i:1;a:3:{i:0;s:54:"What needs to be done when all instances are restarted";i:1;i:2;i:2;i:2596;}i:2;i:2596;}i:70;a:3:{i:0;s:12:"section_open";i:1;a:1:{i:0;i:2;}i:2;i:2596;}i:71;a:3:{i:0;s:13:"section_close";i:1;a:0:{}i:2;i:2663;}i:72;a:3:{i:0;s:6:"header";i:1;a:3:{i:0;s:14:"NTP/DNS Server";i:1;i:3;i:2;i:2663;}i:2;i:2663;}i:73;a:3:{i:0;s:12:"section_open";i:1;a:1:{i:0;i:3;}i:2;i:2663;}i:74;a:3:{i:0;s:10:"listo_open";i:1;a:0:{}i:2;i:2687;}i:75;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:1;}i:2;i:2687;}i:76;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:2687;}i:77;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:59:" Change dns ns1 server ip at /var/lib/bind/db.pmp-hbase.com";}i:2;i:2691;}i:78;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:2750;}i:79;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:2750;}i:80;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:1;}i:2;i:2750;}i:81;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:2750;}i:82;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:80:" Restart bind9 service after all changes are made at dns clients in the cluster.";}i:2;i:2754;}i:83;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:2834;}i:84;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:2834;}i:85;a:3:{i:0;s:11:"listo_close";i:1;a:0:{}i:2;i:2834;}i:86;a:3:{i:0;s:13:"section_close";i:1;a:0:{}i:2;i:2835;}i:87;a:3:{i:0;s:6:"header";i:1;a:3:{i:0;s:9:"All other";i:1;i:3;i:2;i:2835;}i:2;i:2835;}i:88;a:3:{i:0;s:12:"section_open";i:1;a:1:{i:0;i:3;}i:2;i:2835;}i:89;a:3:{i:0;s:10:"listo_open";i:1;a:0:{}i:2;i:2854;}i:90;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:1;}i:2;i:2854;}i:91;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:2854;}i:92;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:41:" Change dns ip at /etc/dhcp/dhclient.conf";}i:2;i:2858;}i:93;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:2899;}i:94;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:2899;}i:95;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:1;}i:2;i:2899;}i:96;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:2899;}i:97;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:43:" Change dns ip at /root/bin/ec2-hostname.sh";}i:2;i:2903;}i:98;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:2946;}i:99;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:2946;}i:100;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:1;}i:2;i:2946;}i:101;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:2946;}i:102;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:38:" Change ntp server ip at /etc/ntp.conf";}i:2;i:2950;}i:103;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:2988;}i:104;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:2988;}i:105;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:1;}i:2;i:2988;}i:106;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:2988;}i:107;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:36:" sync time: ntpudpate -d 'server ip'";}i:2;i:2992;}i:108;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:3028;}i:109;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:3028;}i:110;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:1;}i:2;i:3028;}i:111;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:3028;}i:112;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:20:" check time: ntpq -p";}i:2;i:3032;}i:113;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:3052;}i:114;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:3052;}i:115;a:3:{i:0;s:13:"listitem_open";i:1;a:1:{i:0;i:1;}i:2;i:3052;}i:116;a:3:{i:0;s:16:"listcontent_open";i:1;a:0:{}i:2;i:3052;}i:117;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:39:" restart or execute: sh ec2-hostname.sh";}i:2;i:3056;}i:118;a:3:{i:0;s:17:"listcontent_close";i:1;a:0:{}i:2;i:3095;}i:119;a:3:{i:0;s:14:"listitem_close";i:1;a:0:{}i:2;i:3095;}i:120;a:3:{i:0;s:11:"listo_close";i:1;a:0:{}i:2;i:3095;}i:121;a:3:{i:0;s:13:"section_close";i:1;a:0:{}i:2;i:3095;}i:122;a:3:{i:0;s:12:"document_end";i:1;a:0:{}i:2;i:3095;}}