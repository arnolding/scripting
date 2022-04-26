#!/usr/bin/perl
use strict;
use warnings;
##use 5.010;

use POSIX ":sys_wait_h";
use X11::GUITest qw/:ALL/;
 
getwindows();



sub getwindows
{
	my @wins = GetWindows_ext();
	my $count = 0;
	if ( @wins) {
	  for my $win_info_ref (@wins) {
		print "$win_info_ref->{pid}, $win_info_ref->{wid}, $win_info_ref->{width},$win_info_ref->{height},$win_info_ref->{border},[$win_info_ref->{wname}], [$win_info_ref->{pname}]\n";
		$count++;

#		if ($win_info_ref->{wname} eq "Tonyplot") {
#		my $raise = RaiseWindow $win_info_ref->{wid};
#		my $setfocus = SetInputFocus $win_info_ref->{wid};
#		my $click = ClickWindow $win_info_ref->{wid};
#		print "raise [$raise] setfocus [$setfocus] click [$click]\n";
#
#		#system("xraise $win_info_ref->{wid}");
#		sleep(1);
#		}
	  }
	}

	print "\n\nTotal Windows with pid and wname: [$count]\n";
}


sub GetWindows_ext {
	my @wins = ();

	my @all_wins = GetChildWindows(GetRootWindow(0));
	my $arnold=0;
	foreach my $aw (@all_wins) {
#print "check 3 $aw , $arnold\n";
		my $aw_pid = 0;
		
		if (IsWindowViewable($aw)) {
			$aw_pid = GetWindowPid($aw);
#print "check 4 $aw_pid\n";
			my $wname = GetWindowName($aw) || "XX";
			my $pname = "XX";
			if ($aw_pid > 0) {
				$pname=`ps -o cmd= -p $aw_pid`;
				$pname =~ /^(\S+)[ \n]/;
				$pname = $1;
				$pname = "" unless (defined $pname);
			}
		my ($x, $y, $width, $height, $borderWidth, $screen) = GetWindowPos($aw);
				my %win_info = (pid => $aw_pid , wid => $aw , wname => $wname, pname => $pname , x =>$x, y =>$y, width =>$width, height=>$height, border=>$borderWidth);
				
				push @wins, \%win_info;
			
		}
		$arnold++;
	}
	my @sort = sort {$a->{pid} <=> $b->{pid} or $a->{wid} <=> $b->{wid}} @wins;
	return(@sort);
}

