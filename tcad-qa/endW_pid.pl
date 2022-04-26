#!/usr/bin/perl
use strict;
use warnings;
use 5.010;

use POSIX ":sys_wait_h";
use X11::GUITest qw/:ALL/;
 
my $dbWin_pid = $ARGV[0];
my $command = $ARGV[1];  ## 'K' kill, 'G' print out windod id and window name to output
if ($command eq 'G') {
	getwindow_by_pid($dbWin_pid);
}
if ($command eq 'K') {
	end_by_pid($dbWin_pid);
}

if ($command eq 'Q') {
	query_by_wid($dbWin_pid);
}

if ($command eq 'M') {
	my $message = $ARGV[2];
	win_message($dbWin_pid , $message);
}
if (! defined($command)) {
	end_by_pid($dbWin_pid);
}

sub win_message {
	my $win = shift;
	my $msg = shift;
	return if ($win <= 0);

	my $res = SetInputFocus($win);
    	my $res2 = SendKeys($msg);
print "win_message [$win] [$msg]\n";
}	
sub getwindow_by_pid
{
	my $pid = $_[0];
	my @wins = GetWindowsFromPid_ext($pid);
	if ( @wins) {
	  for my $win_info_ref (@wins) {
		print "$pid, $win_info_ref->{id}, [$win_info_ref->{name}]\n";
	  }
	} else {
		my $process_name = `ps -o cmd= -p $pid`;
		chomp $process_name;
		print "$pid, NO windows, $process_name\n";
	}
}
sub end_by_pid
{
	my $pid = $_[0];
	my @msg = ('%(f)' , 'x' ,'{ENT}');
	my @wins = GetWindowsFromPid_ext($pid);
	for my $win_info_ref (@wins) {
		for my $m1 (@msg) {
			loge("$win_info_ref->{id} $win_info_ref->{name} [$m1]");
			end_wid($win_info_ref->{id},$m1);
		}
	}
}

sub query_by_wid
{
	my $wid = $_[0];

	my $exist = "none";
	
	my @all_wins = GetChildWindows(GetRootWindow(0));

	for my $win (@all_wins) {
		if ($win == $wid) {
			$exist = "exist";
		}
	}
	print "$exist\n";
}

sub show_by_pid {
	my $pid = $_[0];
	print "show_by_pid from $pid   =============\n";
	my @top_wid = ();
	my @children = ();
	print "GetWindowsFromPid_ext [$pid]\n";

	@children = GetWindowsFromPid_ext($pid);		

	for my $tmp (@children) {
		print "children [$tmp]\n";
	}
	
	my $area = 0;
	for my $wid (@children) {
#		print "GetWindowName [$wid]\n";
		my $wname = GetWindowName($wid);
#		print "GetParentWindow [$wid]\n";
		my $pwin = GetParentWindow($wid);
#		print "GetParentWindow [$pwin]\n";
#		my $ppwin = GetParentWindow($pwin);
#		print "GetWindowPid [$wid]\n";
		my $pid = GetWindowPid($wid);
		print "wid [$wid] parent [$pwin] pid [$pid] wname [$wname]\n";
		my ($x, $y , $width, $height, $border, $scr) = GetWindowPos($wid);
		print "x $x y $y width $width h $height b $border s $scr\n\n";	
		print "IsWindow $wid [" . IsWindow($wid) . "] IsWindowViewable [" . IsWindowViewable($wid) . "]\n";
		#if ($area < ($width * $height) and IsWindowViewable($wid) ) {
		if ( IsWindowViewable($wid) ) {
			#$area = $width * $height;
			push(@top_wid , $wid);
			print "ttttop win [$wid]\n";
		}
	}
	return \@top_wid;
}


sub show_by_pid_0 {
	my $pid = $_[0];
	print "show_by_pid from $pid   =============\n";
	my @top_wid = ();
	my @children = ();
	print "GetWindowsFromPid
_ext [$pid]\n";


	eval {
		@children = GetWindowsFromPid_ext($pid);		
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
		my $pwin = GetParentWindow($wid);
#		print "GetParentWindow [$pwin]\n";
#		my $ppwin = GetParentWindow($pwin);
#		print "GetWindowPid [$wid]\n";
		my $pid = GetWindowPid($wid);
		print "wid [$wid] parent [$pwin] pid [$pid] wname [$wname]\n";
		my ($x, $y , $width, $height, $border, $scr) = GetWindowPos($wid);
		print "x $x y $y width $width h $height b $border s $scr\n\n";	
		print "IsWindow $wid [" . IsWindow($wid) . "] IsWindowViewable [" . IsWindowViewable($wid) . "]\n";
		#if ($area < ($width * $height) and IsWindowViewable($wid) ) {
		if ( IsWindowViewable($wid) ) {
			#$area = $width * $height;
			push(@top_wid , $wid);
			print "ttttop win [$wid]\n";
		}
	}
	return \@top_wid;
}

sub end_wid
{
	my $win_id = $_[0];
	my $message = $_[1];
	print "end_wid , $win_id , $message\n";
	#ClickWindow($win_id , 10, -10);
	if (SetInputFocus($win_id)) {
		SendKeys($message);
	} else {
		loge("Error of SetInputFocus $message");
	}
}

sub GetWindowsFromPid_ext {
	my $pid = shift;
	my @wins = ();

	if ($pid <= 0) {
		return(undef);
	}

#print "check 2 $pid\n";
#system("ps -p $pid");
	my @all_wins = GetChildWindows(GetRootWindow(0));
	my $arnold=0;
	foreach my $aw (@all_wins) {
#print "check 3 $aw , $arnold\n";
		my $aw_pid = 0;
		
		if (IsWindowViewable($aw)) {
			$aw_pid = GetWindowPid($aw);
#print "check 4 $aw_pid\n";
			if ($aw_pid == $pid) {
				my $wname = GetWindowName($aw);
				my %win_info = (id => $aw , name => $wname);
				push @wins, \%win_info;
			}
		}
		$arnold++;
	}
	return(@wins);
}
sub loge
{
        my $msg = $_[0];

	my $log_fname = "/build/arnoldh/endW/end.log";
        open(LOG , ">> $log_fname");
        my $time_mark = localtime;
        print LOG "$time_mark :\n$msg\n";
        close(LOG);
}

