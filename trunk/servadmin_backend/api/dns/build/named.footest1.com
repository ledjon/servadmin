$TTL 300
;
; zone file for [footest1.com]
; build by ServerAdmin at [2005-11-13 20:57:46]
;
; Do not manually edit this BIND file
; if you want to include your own entries, create
; a file of the same name with a .include suffix (named.footest1.com.include)
; and it will be included on the next build
;

@	IN	SOA	localhost hostmaster.localhost. (
			1131933466
			10800
			3600
			604800
			86400 )
@		IN	NS	ns1.enhosting.com.
@		IN	NS	ns2.enhosting.com.
@		IN	MX	10	127.0.0.1
@		IN	A	192.168.1.160
realsub		IN	A	192.168.1.160
www		IN	A	192.168.1.160
fakesub		IN	CNAME	realsub.footest1.com.
*		IN	A	127.0.0.1
