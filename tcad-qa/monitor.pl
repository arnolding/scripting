#!/usr/bin/perl
use strict;
use warnings;
##use 5.010;

use IO::Socket;
use POSIX ":sys_wait_h";
use POSIX qw(strftime);
use Cwd 'abs_path';
use File::Basename;
use File::Path;
use File::Find;
use Date::Parse;
use ProductInfo;
#use ProductX11;
use RegUtil;
use DeckInfo;
use Getopt::Long;
use Data::Dumper;

open F , ">/tmp/monitor.log";
print F "ARGV0:$ARGV[0]\n";
print F "ARGV1:$ARGV[1]\n";
print F "===\n";
close F;
die "Need an argument for regression current folder" if ( ! $ARGV[0]);
monitor($ARGV[0] , $ARGV[1]);
exit;




	


sub convert_minute
{
	my $min_str = shift;
	my $total;
	my @rv = split /:/ , $min_str;
	if ($#rv == 2) {
		$total = 3600 * $rv[0] + 60 * $rv[1] + 1 * $rv[2];
	} elsif ($#rv ==1) {
		$total = 60* $rv[0] + 1* $rv[1];
	} else {
		$total = 1*$rv[0];
	}

	return $total;
}



sub time_tag
{
	my ($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime(time);
	my $StartTime = sprintf("%s %s %02d %02d:%02d:%02d %4d", 
       ("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat")[$wday],
       ("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec")[$mon], $mday, $hour, $min, $sec, 1900+$year);

	return $StartTime;
}

sub machine_info
{

	my $lscpu = `lscpu`;
	my @cpu_info = split $lscpu;
	for my $i1 (@cpu_info) {
		print "[$i1]\n";
	}
}
sub config
{
	my $fn = shift;
	my $config_f = dirname($0) . "/" . $fn;
	if (! -f $config_f) {
		die "cannot open file [$config_f]";
	}
	my $str = `cat $config_f`;
	my $VAR1;
	eval $str;
	for my $k (keys %$VAR1) {
		$ENV{$k} = $VAR1->{$k};
	}
}

sub print_top
{
	my $fh = shift;
	my $top = `top -b -n 1`;
	my @top = split /\n/ , $top;
	my $max_line = 17;
	for (@top) {
		print $fh "$_\n";
		last if (! $max_line--);
	}
}



sub print_active_ptree
{
	my $ppid = shift;
	my $fh = shift;
	my $ptree_h = ProcWin::make_tree_from_pid( $ppid );
	if (%$ptree_h) {
 		my $track_children = ProcWin::print_ptree_by_pid($ptree_h, $ppid, "", 1);
		print $fh $track_children;
		print $fh "\n**\n";
		#print $fh $self->print_windows_str();
		#close($fh);
		return 1;
	} else {
		return 0;
	}
}
sub monitor
{
	my $OutPath = shift;
	my $ppid = shift;
	
	$ppid = getppid() if (not defined $ppid);
	my $m_fh;
	open $m_fh , ">$OutPath/monitor.log" or die $!;

	print $m_fh "start from $ppid\n";
	print $m_fh "monitor is $$\n";
	while (1) {
		$m_fh->flush();
		sleep(3);
		print_top($m_fh);
		if (0 == print_active_ptree($ppid, $m_fh)) {
			print $m_fh "since parent perl is gone, exit gracefully\n";
			last;
		}
	}

	close $m_fh;
	exit 0;
}
