#!/usr/bin/perl

# $Id: phttpd.pl,v 1.3 2005/11/03 16:22:59 ledjon Exp $

# perl httpd
# written for servadmin
# by Jon Coulter

use strict;
use Socket;
use HTTP::Daemon;
use HTTP::Status;
use Getopt::Std;
use DirHandle;
use POSIX qw(strftime WNOHANG);
use IPC::Open2;
use IO::Handle;
use File::Basename;
use Cwd;

# turn off output buffering for stdout/stderr
$|++;
select((select(STDERR), $|++)[0]);

# set ourself
$0 = basename($0);

# -p x -- port, default 81
# -l x.x.x.x -- default 0.0.0.0
# -h /path/to/home
# -H .php=/path/to/php,.a=/blah
# -d -- turn on debugging
# -D -- show output from cgi scripts (dangerious!!)
# -M x -- max children... defaults to 10
# -F (regex) -- filter out things based on regex

getopts('dDp:l:h:H:M:F:', \my %opts);

my $MAXKIDS = int($opts{'M'}) || 10;
my $CURKIDS = 0;
debug("Starting with maxkids=$MAXKIDS");

# find ourbase directory
my $basedir = $opts{'h'} or die "Need at least -h /path/to/home\n";
my $filter = $opts{'F'};

# configure our handlers
my %handlers = ( );
for my $p (split(/\s*,\s*/, $opts{'H'}))
{
	my ($ext, $handler) = split(/=/, $p);
	$ext = lc($ext);

	if(!-x $handler)
	{
		debug("$handler cannot be executed, ignoring handler($p)");
		next;
	}

	$ext =~ s/\.//g;

	debug("adding handler $ext -> $handler");
	$handlers{$ext} = $handler;
}

my $DONE = 0;
$/ = "\r\n";

$SIG{'INT'} = $SIG{'TERM'} = sub { debug("cought @_"); $DONE++; };
$SIG{'CHLD'} = \&reaper;

debug("starting connection...");
my $d = HTTP::Daemon->new(
		LocalAddr => $opts{'l'} || '0.0.0.0',
		LocalPort => $opts{'p'} || 81,
		'Reuse'	  => 1
	) or die "Unable to listen: $!\n";
debug("listening on " . $d->url . ' with basedir=' . $basedir);

# main code
while(! $DONE)
{
	next unless my $c = $d->accept;

	# now fork!
	if(!wait_for_slot())
	{
		debug("giving up in this request!!");

		next;
	}

	die "Unable to fork: $!\n" unless defined(my $pid = fork);

	if($pid > 0)
	{
		$CURKIDS++;
		debug("parent's done it's job... created $pid");
		next;
	}

	debug("child[$$] started");

	if(my $r = $c->get_request)
	{
		my $path = $r->url->path;
		$0 = "$0 [$path]";
		debug("got request for $path");
		debug("query-string: " . $r->url->query);
		# debug("content: " . $r->content);
		
		$path =~ s!^/+!!;
		$path =~ s!\.\.!!g;
		1 while($path =~ s#/$##);

		debug("path is $path ($basedir/$path)");

		if($path eq ''
		    && -f $basedir . '/index.html')
		{
			$path = $basedir . '/index.html';
		}
		else
		{
			$path = $basedir . '/' . $path;

			if(-d $path
			    && -f $path . '/index.html')
			{
				debug("found index page!");
				$path .= '/index.html';
				debug("\t-> $path");
			}
		}

		my $done = 0;

		# filtered out?
		if($filter)
		{
			if($path =~ /.*$filter.*/i)
			{
				$c->send_error(RC_FORBIDDEN);
				$done++;
			}
		}

		# doesn't exist?
		if(!-e $path)
		{
			debug("$path does not exist, sending 404");
			$c->send_error(RC_NOT_FOUND) unless -e $path;
			$done++;
		}
		
		# log request?

		# handle handlers?
		if($done == 0
		    && $path =~ /\.([a-z\d]+)$/i)
		{
			my $ext = lc $1;
			debug("possible handler? $ext");

			if(exists $handlers{$ext})
			{
				debug("found handler for $ext ($path) -> " . $handlers{$ext});

				# do it
				$done++ if handlecgi( $path, $handlers{$ext}, $r, $c );
			}
			else
			{
				debug("No handler found for $ext (likely default to file-response)");
			}
		}

		if($done == 0)
		{
			debug("file-response");
			$c->send_file_response($path);
		}

	}

	$c->force_last_request;
	debug("finished with request");

	$c->close;
	debug("finished with client");

	exit;
}

# handle dir listings
sub HTTP::Daemon::ClientConn::send_dir
{
	my ($self, $path) = @_;

	debug("$path is a directory? " . int(-d $path));

	return $self->send_error(RC_NOT_FOUND) unless -d $path;

	my $reldir = $path;
	$reldir =~ s/^\Q$basedir\E//;
	1 while($reldir =~ s#/$##);

	$self->send_basic_header;
	$self->print("Content-Type: text/html" . $/);
	$self->print($/);

	$self->print("<h2>Index of " . ($reldir || '/') . "</h2>");
	$self->print("<hr><pre>");

	my $dir = DirHandle->new($path);
	while(my $file = $dir->read)
	{
		next if $file eq '.';

		if($filter)
		{
			next if $file =~ /.*$filter.*/i;
		}

		$file .= '/' if (-d $path . '/' . $file);
		my $link = '<a href="' . $reldir . '/' . $file . '">'.
				substr($file, 0, 30) . '</a>' .
				(' ' x (30 - length($file)));

		my ($size, $mtime) = (stat _)[7,9];
		$self->print($link . ' ' .
			strftime('%c', localtime($mtime)) .
			' ' . $size . ' bytes' . $/
		);
	}
	$dir->close;

	$self->print("</pre><hr>");
        $self->print("<i>Web Server Info Here</i>");

	debug("done sending directory!");
}

sub handlecgi
{
	my ($path, $handler, $r, $c) = @_;

	my $relpath = $path;
	$relpath =~ s/^\Q$basedir\E\/?//i;

	debug("got cgi request for $path, $handler, $r");
	debug("whole request:", split(/\n/, $r->as_string));

	# setup env
	my $env_path = $ENV{"PATH"};
	local %ENV = (
		PATH 			=> $env_path,
		SCRIPT_FILENAME		=> basename($r->url->path) || '',
		SERVER_PROTOCOL		=> 'HTTP/1.0',
		SERVER_SOFTWARE		=> 'phttpd.pl',
		GATEWAY_INTERFACE	=> 'CGI/1.1',
		REMOTE_ADDR		=> '0.0.0.0',
		SCRIPT_NAME		=> $r->url->path || '',
		SERVER_NAME		=> $r->headers->header('Host') || '',
		QUERY_STRING		=> $r->url->query || '',
		CONTENT_TYPE		=> $r->headers->content_type || '',
		REQUEST_URI		=> $r->url->path_query || '',
		SERVER_PORT		=> $opts{'p'} || 81,
		CONTENT_LENGTH		=> $r->headers->content_length || 0,
		REQUEST_METHOD		=> $r->method || '',
		DOCUMENT_ROOT		=> $basedir,
		HTTP_ACCEPT		=> $r->headers->header('Accept') || '*/*',
		HTTP_REFERER		=> $r->headers->referer || '',
		HTTP_ACCEPT_LANGUAGE	=> $r->headers->header('Accept-Language') || '',
		HTTP_CONTENT_TYPE	=> $r->headers->content_type || '',
		HTTP_ACCEPT_ENCODING	=> $r->headers->header('Accept-Encoding') || '',
		HTTP_USER_AGENT		=> $r->user_agent || '',
		HTTP_HOST		=> $r->headers->header('Host') || '',
		HTTP_CONTENT_LENGTH	=> $r->headers->content_length || '',
		HTTP_CONNECTION		=> $r->headers->header('Connection') || '',
		HTTP_CACHE_CONTROL	=> $r->headers->header('Cache-Control') || ''
	);

	map { debug("ENV[$_] = " . $ENV{$_}) } sort keys %ENV;

	debug("going to open $handler -> $path");

	# current dir
	my $olddir = getcwd( );

	# change to child directory
	chdir( dirname($path) ) or warn "Unable to chdir()\n";

	# for timing
	my $start = time;

	# wait_for_slot( );
	my $pid = open2(
		my $read = IO::Handle->new,
		my $write = IO::Handle->new,
		$handler,
		$path
	) or die "Unable to open2: $!\n";

	$CURKIDS++;
	debug("CGIPID: $pid");

	$write->print($r->content);
	$write->close;
	
	$c->send_basic_header;
	my $in;
	#while(my $in = $read->getline)
	while(my $rc = $read->sysread($in, 1024))
	{
		debug("CGI: $in") if $opts{'D'};
		$c->print($in);
	}

	debug(time - $start . " seconds to execute cgi script");

	# back to our own dir
	chdir( $olddir );
	
	return 1;
}

sub reaper
{
	while((my $kid = waitpid(-1, WNOHANG)) > 0)
	{
		$CURKIDS--;
		debug("Reaped child with PID $kid (cur: $CURKIDS)");
	}
}

sub debug
{
	return unless $opts{'d'};
	my $msg;
	while(my $line = shift)
	{
		$msg .= "DEBUG [$$]: " . $line . "\n";
	}

	print STDERR $msg;

	return 1;
}

sub wait_for_slot
{
	# check on the number of children
	my $i = 0;
	while(1)
	{
		if(++$i > 20)
		{
			debug("warning: $$ has waited more then 20 seconds to get a slot, giving up!");
			return 0;
		}
		if($CURKIDS >= $MAXKIDS)
		{
			debug("warning: waiting on more children to exit ($CURKIDS/$MAXKIDS/$i)");
			sleep 1;
			next;
		}
		
		last;
	}

	return 1;
}


#END
{
	debug("exiting...");
	if($d)
	{
		$d->shutdown(2);
		$d->close;
	}
}

END
{
	debug("process[$$] ending");
}

1;
__END__
