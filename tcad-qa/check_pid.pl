#!/usr/bin/perl
use strict;
use warnings;
use 5.010;

use POSIX ":sys_wait_h";
use X11::GUITest qw/:ALL/;
 
my $dbWin_pid = $ARGV[0];
find_child_pid($dbWin_pid);
show_by_pid($dbWin_pid);
sub find_child_pid
{
	my $ppid = $_[0];
	my @child = ();
	my $ps_output = `ps --ppid $ppid`;
	print "$ps_output\n";
	my @ppid_lines = split /\n/ , $ps_output;
	for my $oneline (@ppid_lines) {
		if ($oneline =~ /^ *(\d+) /) {
			my $parsed_pid = $1;
			push(@child , $parsed_pid);
			find_child_pid($parsed_pid);
		}
	}
	for my $cid (@child) {
		end_by_pid($cid,'{pause 500}%(f){pause 500}x');
	}
	return \@child;
}
sub end_by_pid
{
	my $cid = $_[0];
	my $msg = $_[1];
	my $wid_ref = show_by_pid($cid);
	for my $t_wid (@$wid_ref) {
		my $cid_exist = `ps $cid`;
		my @cids = split /\n/ , $cid_exist;
		for my $oneline (@cids) {
			if ($oneline =~ /^ *(\d+) /) {
				if ($cid == $1) {					
					end_wid($t_wid,$msg);
					last;
				}
			}
		}
	}
}
sub show_by_pid {
	my $check_pid = $_[0];
	print "show_by_pid from $check_pid   =============\n";
	my @top_wid = ();
	my @children = ();
	print "GetWindowsFromPid_ext [$check_pid]\n";


	eval {
		@children = GetWindowsFromPid_ext($check_pid);		
#		@children = FindWindowLike(".");
	};
	
	if ($@) {
		print "[[[[ show_by_pid eval ]]]\n$@\n";
		return \@top_wid;
	}

	for my $tmp (@children) {
		print "children [$tmp]\n";
	}
	
	my $area = 0;
	for my $wid (@children) {
#		print "GetWindowName [$wid]\n";
		my $wname = GetWindowName($wid);
#		print "GetParentWindow [$wid]\n";
		my $pwin = GetParentWindow($wid) if ($wid);
#		print "GetParentWindow [$pwin]\n";
#		my $ppwin = GetParentWindow($pwin) if ($pwin);
#		print "GetWindowPid [$wid]\n";
		my $pid = GetWindowPid($wid);
		print "wid [$wid] parent [$pwin] pid [$pid] wname [$wname] view [" . IsWindowViewable($wid) ."]\n" if ($pid == $check_pid);
		my ($x, $y , $width, $height, $border, $scr) = GetWindowPos($wid);
#		print "x $x y $y width $width h $height b $border s $scr\n\n";	
#		print "IsWindow $wid [" . IsWindow($wid) . "] IsWindowViewable [" . IsWindowViewable($wid) . "]\n";
		#if ($area < ($width * $height) and IsWindowViewable($wid) ) {
#		if ( IsWindowViewable($wid) ) {
#			#$area = $width * $height;
#			push(@top_wid , $wid);
#			print "ttttop win [$wid]\n";
#		}
	}
	return \@top_wid;
}

sub end_wid
{
	my $win_id = $_[0];
	my $message = $_[1];
	print "end_wid , $win_id , $message\n";
	#ClickWindow($win_id , 10, -10);
	SetInputFocus($win_id);
	#ClickWindow($win_id , 10, -10);
	sleep(1);
	#RaiseWindow($win_id);
	SendKeys($message);
	#SendKeys('{pause 1500}%({F4})');
	print "*** Send Alt-f x to $win_id\n";
	sleep(1);
}
