#!/usr/bin/perl

use strict;
use threads;
use threads::shared;
use SOAP::Lite;

my $slock : shared = 0; # soap lock
my $soap = SOAP::Lite->proxy('http://localhost:81/admin/soapserver.php');

my @threads = ( );

for(1..5) {
	push(
		@threads,
		threads->new( \&fetch )
	);
}

$_->join for @threads;

sub fetch {
	printf("who? [%d]: %s\n", threads->tid, $soap->whoami->result);
	for(1..10) {
		my $soap = SOAP::Lite->proxy('http://localhost:81/admin/soapserver.php');
		my $ret = $soap->reverse('asdf');
	
		if($ret->fault) {
			#die("error: " . $ret->faultstring);
			printf("WARN: [%d] %s\n", threads->tid, $ret->faultstring);
		} else {
			printf "[%d] done: %s\n", threads->tid, $ret->result;
		}
	}
}

1;
__END__
