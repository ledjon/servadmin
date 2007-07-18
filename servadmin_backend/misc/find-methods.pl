#!/usr/bin/perl

use strict;
use File::Find;

my $dir = shift or die "Usage: $0 /path/to/base/dir\n";

finddepth(\&wanted, $dir);

sub wanted
{
	return unless /\.php$/;
	my $file = $File::Find::name;

	if(-e $file)
	{
		printf("# file = %s\n", $file);
		open(FH, $file) or die $!;
		while(my $l = <FH>)
		{
			if($l =~ /^\s*class +(\S+)/i)
			{
				printf("%s\n", $1);
				next;
			}

			if($l =~ /^\s*function +([^\)]+\))/i)
			{
				printf("  %s\n", $1);
				next;
			}
		}
		close(FH);
		print "\n";
	}
}
