#!/usr/bin/perl
use strict;
use warnings;
use 5.010;

use POSIX ":sys_wait_h";
use X11::GUITest qw/:ALL/;

my $wid = shift @ARGV;
$wid = 401 unless ($wid);
print "window id [$wid]\n";
if ( (substr($wid,0,2) eq "0x" ) || (substr($wid,0,2) eq "0X" ) ) {
	my $n_wid = hex($wid);
	$wid = $n_wid;
}
my ($x, $y , $width, $height, $border, $scr) = GetWindowPos($wid);
if ( defined $x) {
	print "x $x y $y width $width h $height b $border s $scr\n";	
} else {
	print "Not valid window id\n";
	$width = 1000;
}

for (my $i = 10 ; $i < $width ; $i += 20) {
	MoveMouseAbs $i , 100;
}


my $root_win = GetRootWindow(0);
print "root window $root_win\n";

while (1) {
	my ($mx, $my, $mscr) = GetMousePos();
	print "Current mouse at $mx, $my\n";
	sleep(1);
}
