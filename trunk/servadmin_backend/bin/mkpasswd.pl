#!/usr/bin/perl

# take from Shawn Harken!

use MD5;

print md5crypt(@ARGV) . "\n";

sub md5crypt {
    #
    # Based on Poul-Henning Kamp (<phk@login.dknet.dk>)'s Code
    # Crypt::PasswdMD5
    #

    my ( $text, $salt ) = @_;

    my $magic = '$1$';

    if (!$salt) { $salt = makesalt(8); }

    $salt =~ s/^\Q$magic//;
    $salt =~ s/^(.*)\$.*$/$1/;
    $salt = rschop($salt,8);

    my $crypt = new MD5;
    $crypt->add($text);
    $crypt->add($magic);
    $crypt->add($salt);

    my $final = new MD5;
    $final->add($text);
    $final->add($salt);
    $final->add($text);
    $final = $final->digest;

    for (my $textlen = length($text); $textlen > 0; $textlen -= 16) {
        $crypt->add(substr($final, 0, $textlen > 16 ? 16 : $textlen));
    }

    for (my $i = length($text); $i; $i >>= 1) {
        if ($i & 1) { $crypt->add(pack("C", 0)); }
        else { $crypt->add(substr($text, 0, 1)); }
    }

    $final = $crypt->digest;

    for ( my $i = 0; $i < 1000; $i++) {
        $crypt1 = new MD5;
        if ($i & 1) { $crypt1->add($text); }
        else { $crypt1->add(substr($final, 0, 16)); }
        if ($i % 3) { $crypt1->add($salt); }
        if ($i % 7) { $crypt1->add($text); }
        if ($i & 1) { $crypt1->add(substr($final, 0, 16)); }
        else { $crypt1->add($text); }
        $final = $crypt1->digest;
    }

    my $passwd;
    $passwd .= _md5_to64(int(unpack("C", (substr($final, 0, 1))) << 16) | int(unpack("C", (substr($final, 6, 1))) << 8) | int(unpack
("C", (substr($final, 12, 1)))), 4);
    $passwd .= _md5_to64(int(unpack("C", (substr($final, 1, 1))) << 16) | int(unpack("C", (substr($final, 7, 1))) << 8) | int(unpack("C", (substr($final, 13, 1)))), 4);
    $passwd .= _md5_to64(int(unpack("C", (substr($final, 2, 1))) << 16) | int(unpack("C", (substr($final, 8, 1))) << 8) | int(unpack("C", (substr($final, 14, 1)))), 4);
    $passwd .= _md5_to64(int(unpack("C", (substr($final, 3, 1))) << 16) | int(unpack("C", (substr($final, 9, 1))) << 8) | int(unpack("C", (substr($final, 15, 1)))), 4);
    $passwd .= _md5_to64(int(unpack("C", (substr($final, 4, 1))) << 16) | int(unpack("C", (substr($final, 10, 1))) << 8) | int(unpack("C", (substr($final, 5, 1)))), 4);
    $passwd .= _md5_to64(int(unpack("C", substr($final, 11, 1))), 2);

    return $magic . $salt . '$' . $passwd;
}

sub _md5_to64 {
    my ( $v, $n ) = @_;
    my $_md5_itoa64 = "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    my $ret = '';
    while (--$n >= 0) {
        $ret .= substr($_md5_itoa64, $v & 0x3f, 1);
        $v >>= 6;
    }
    return $ret;
}

sub makesalt {
    my ( $chars ) = @_;
    if (!@_makesalt_keys) { @_makesalt_keys = split(//,'aA1bB!cC2dD@eE3fF#gG4hH.iI5jJ%kK6lL^mM7nN&oO8pP*qQ9rR(tT0uU)vV-xX~yY[zZ]'); 
}
    if ($chars !~ /^\d+$/) { $chars = 8; }
    my ( $crypt, $a );
    for ( $a = 0; $a < $chars; $a++ ) {
        $crypt .= $_makesalt_keys[(int(rand(@_makesalt_keys)))];
    }
    return $crypt;
}

sub rschop { my($string,$length) = @_; $length = 1 unless $length; return substr($string,0,$length); }

1;
__END__
