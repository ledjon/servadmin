#!/usr/bin/perl

# $Id: create_config_files.pl,v 1.1 2006/03/02 20:25:18 ledjon Exp $

# Create awstats conf files for every
# log file in /var/log/apache/vhost (default)
# by Jon Coulter

use strict;
use FileHandle;
use DirHandle;
use File::Copy;

use constant LOG_BASE => '/var/log/apache/vhost';
use constant AWSTATS_BASE => '/etc/awstats';

my $d = DirHandle->new( LOG_BASE );

while(my $file = $d->read)
{
	my $full_path = sprintf("%s/%s", LOG_BASE, $file );

	if( -d $full_path )
	{
		next;
	}

	# get rid of ip-address based ones
	if($file =~ /^\d+\.\d+\.\d+\.\d+\.log/)
	{
		# unlink($full_path) or warn "$full_path: $!\n";
		next;
	}

	# get the domain based on the input file
	my ($domain) = $file =~ /^(.*)\.log$/;
	$domain = lc($domain);
	next if !$domain;

	# the location of where our conf file should be
	my $conf_file = sprintf("%s/awstats.%s.conf", AWSTATS_BASE, $domain);

	# it doesn't exist? create it !
	if(!-e $conf_file)
	{
		my $source = sprintf("%s/awstats.model.conf", AWSTATS_BASE);
		print "Creating $conf_file based on $source for [$domain]\n";

		if(!-f $source)
		{
			die("Unable to find source file: $source\n");
		}

		my $fh = FileHandle->new( $source ) or die $!;

		my $ofh = FileHandle->new( $conf_file, 'w' ) or die $!;

		while(my $line = $fh->getline)
		{
			# replace __hostname__ with real hostname
			$line =~ s/__hostname__/$domain/ig;

			$ofh->print($line);
		}
		$_->close for($fh, $ofh);
	}
}

1;
__END__
