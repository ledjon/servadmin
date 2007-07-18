#!/usr/bin/perl

use strict;
use Data::Dumper;

open(F, ">/tmp/in.txt") or die $!;
print F Dumper(\%ENV);
print F join("-", <STDIN>);
close F;

1;
__END__
