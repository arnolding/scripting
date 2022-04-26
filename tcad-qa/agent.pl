#!/usr/bin/perl
use strict;
use warnings;
use 5.010;

use POSIX ":sys_wait_h";
use X11::GUITest qw/:ALL/;
use Getopt::Long;
use Data::Dumper;
$Data::Dumper::Sortkeys = 1;
$Data::Dumper::Deepcopy = 1;

## agent for X11 command
# See print_help

my $pid = 0;
my $cursor = '';
my $wid;
my $kill = '';
my $query = '';
my $xprop = '';
my $help = '';
my $message = '';
my $exist = '';
my $size = '';
my $root = '';
my $getcursor = 0;
my $key_delay = 0;
GetOptions(
	'pid=s' => \$pid,
	'wid=i' => \$wid,
	'delay=i' => \$key_delay,
	'kill' => \$kill,
	'query' => \$query,
	'xprop' => \$xprop,
	'message=s' => \$message,
	'exist' => \$exist,
	'size' => \$size,
	'root' => \$root,
	'cursor=s' => \$cursor,
	'getcursor' =>\$getcursor,
	'help' => \$help
	) || die "--pid <pid>|--wid <wid> --kill --query --message --help";

if ($help) {
	print_help();
	exit 0;
}
sub print_help
{
	print "$0 -h|--h|-help|--help , for this help messages\n";
	print "$0 -k -w wid|-p pid, close the windows by window id or process id\n";
	print "$0 -q -w wid|-p pid, query info by window id or process id\n";
	print "$0 -m 'msg' -w wid -d ms, send message to window id with optional delay in milliseconds\n";
	print "$0 -e -w wid, check exist or none by window id\n";
	print "$0 -s -w wid, get size of window id\n";
	print "$0 -r -w wid, get root window\n";
	print "$0 -c x,y, move mouse to x,y\n"
}

if ($query) {
	if ($wid) {
		getwindow_by_wid($wid);
	}
	if ($pid) {
		#sleep(1);
		getwindow_by_pid($pid);
	}
}
if ($xprop) {
	if ($wid) {
		getwindow_by_wid($wid);
	}
	if ($pid) {
		xprop_by_pid($pid);
	}
}
if ($kill) {
	if ($wid) {
		end_by_wid($wid)
	}
	if ($pid) {
		end_by_pid($pid);
	}
}

if ($exist && $wid) {
	query_by_wid($wid);
}

if ($size && defined $wid) {
	size_by_wid($wid);
}

if ($root) {
	if ($size) {
		size_by_wid(0);
	} else {
	if ($root && $wid) {
		find_root_by_wid($wid);
	} else {
		find_root_wid($wid);
	}
	}
}

if ($message && $wid) {
	win_message($wid , $message, $key_delay);
}

if ($cursor) {
	MoveCursor($cursor);
}
if ($getcursor) {
	GetCursor();
}

sub win_message {
	my $win = shift;
	my $msg = shift;
	my $delay = shift;
	return if ($win <= 0);

	
	SetKeySendDelay($delay) if ($delay);
	my $delay_get = GetKeySendDelay();
#print "delay = [$delay_get]\n";

	my $res = `xraise $win`;
	my $status = $?;
	if ($status == 0) {
		my $res2 = SendKeys($msg);
		print "agent.pl: win_message [$win] [$msg]\n";
		loge("win_message [$win] [$msg]");
	} else {
		print "agent.pl: win_message [$win] failed since raise window\n";
	}
}
sub getwindow_by_pid
{
	my $pid = shift;
	my @pid_array = split /,/ , $pid;
	my @wins = GetWindowsFromPid_ext($pid);
	my @wins_pid = ();

	for my $p1 (@pid_array) {
		my $out = 0;
		if ( @wins) {
			for my $w_href (@wins) {
				if ($w_href->{pid} == $p1) {
					push @wins_pid , $w_href;
					$out++;
				}
	  		}
		}
		if ($out == 0) {
			#print "$p1, 0, [NOWINDOW], [$process_name]\n";
			push @wins_pid , {
				pid => $p1, wid => 0,
				wname => "[NOWINDOW]"};
		}
	}
	print Data::Dumper->Dump([\@wins_pid]);
}
sub xprop_by_pid
{
	my $pid = shift;
	my @pid_array = split /,/ , $pid;
	my @wins_pid = ();
	my @wins;

	my $xp_out = `xprop -allw _NET_WM_NAME _NET_WM_PID _NET_WM_STATE _NET_WM_WINDOW_TYPE WM_TRANSIENT_FOR`;

	my @xp_lines = split /\n/ , $xp_out;
	my $w1 = undef;
	for my $xp1 (@xp_lines) {
		if ($xp1 =~ /^wid \[(\d+)\]/) {
			if ($w1) {
				push @wins , $w1;
			}
			$w1 = {};
			$w1->{wid} = $1;
		} elsif ($xp1 =~ /^_NET_WM_PID.*=\s+(\d+)/) {
			$w1->{pid} = $1;
		} elsif ($xp1 =~ /^_NET_WM_NAME.*=\s+"(.+)"/) {
			$w1->{wname} = $1;
		} elsif ( $xp1 =~ /^_NET_WM_STAT/ and 
					$xp1 =~	/_NET_WM_STATE_MODAL/) {
			$w1->{_NET_WM_STATE_MODAL} = 1;
		} elsif ( $xp1 =~ /WM_TRANSIENT_FOR.*id # ([\dxa-f]+)$/) {
			my $transient_for = $1;
			$w1->{WM_TRANSIENT_FOR} = hex($transient_for);
		} elsif ( $xp1 =~ /^_NET_WM_WINDOW_TYPE.+= (.+)$/) {
#_NET_WM_STATE(ATOM) = _NET_WM_STATE_MODAL, _NET_WM_STATE_SKIP_TASKBAR
				my $type = "NORMAL";
				my $win_type = $1;
				my @win_types = split /,/ , $win_type;
				my @known_type = (
							"_NET_WM_WINDOW_TYPE_DIALOG",
							"_NET_WM_WINDOW_TYPE_POPUP_MENU",
							"_NET_WM_WINDOW_TYPE_NORMAL"
					);
				for my $wt1 (@win_types) {
						for my $k1 (@known_type) {
							if ($wt1 eq $k1) {
								if ($type eq "NORMAL") {
									$type = substr($k1 , 20);
								} else {
									$type .= substr($k1 , 20);
								}
							}
						}
				}
				$w1->{_NET_WM_WINDOW_TYPE} = $type;
		}
	}
		
	for my $p1 (@pid_array) {
		my $out = 0;
		if ( @wins) {
			for my $w_href (@wins) {
				next if (not exists $w_href->{pid});
				if ($w_href->{pid} == $p1) {
					push @wins_pid , $w_href;
					$out++;
				}
	  		}
		}
		if ($out == 0) {
			push @wins_pid , {
				pid => $p1, wid => 0,
				wname => "[NOWINDOW]"};
		}
	}
	print Data::Dumper->Dump([\@wins_pid]);
}
sub getwindow_by_pid0
{
	my $pid = shift;
	my @pid_array = split /,/ , $pid;
	my @wins = GetWindowsFromPid_ext($pid);
	my @wins_pid = ();

	for my $p1 (@pid_array) {
		my $out = 0;
		if ( @wins) {
			for my $w_href (@wins) {
				if ($w_href->{pid} == $p1) {
					push @wins_pid , $w_href;
					$out++;
				}
	  		}
		}
		if ($out == 0) {
			print "$p1, 0, [NOWINDOW]\n";
			push @wins_pid , {
				pid => $p1, wid => 0,
				wname => "[NOWINDOW]"};
		}
	}
	#print Data::Dumper->Dump([\@wins_pid]);
}
sub size_by_wid
{
	my $wid = shift;
	if ($wid == 0) {
		$wid = GetRootWindow(0);
	}
	my ($x, $y , $width, $height, $border, $scr) = GetWindowPos($wid);
	my $wname = GetWindowName($wid) || "XX";
	my %win_size = (
		wid	=> $wid,
		x	=> $x,
		y	=> $y,
		width	=> $width,
		height	=> $height,
		border	=> $border,
		screen	=> $scr,
		wname	=> $wname
		);
	print Data::Dumper->Dump([\%win_size]);
}
sub find_root_wid
{
	my $rootwin = GetRootWindow(0);
	print "root window id, [$rootwin]\n";
}
sub find_root_by_wid
{
	my $wid = shift;
	my $pwin = GetParentWindow($wid);
	my $rootwin = GetRootWindow(0);
	my $wname = GetWindowName($pwin) || "XX";
	print "$wid, $pwin, [$wname], $rootwin\n";
}
sub getwindow_by_wid
{
	my $wid = shift;
	my @wins = GetWindows_ext();
	if ( @wins) {
		for my $w_href (@wins) {
			if ($w_href->{wid} == $wid) {
				print "$w_href->{pid}, $wid, [$w_href->{wname}]\n";
			}
		}
	} else {
		print "\n";
	}
}
sub end_by_pid
{
	my $pid = shift;
	my @msg = ('%(f)' , 'x' ,'{ENT}');
	my @wins = GetWindowsFromPid_ext($pid);
	for my $win_info_ref (@wins) {
		for my $m1 (@msg) {
			loge("$win_info_ref->{id} $win_info_ref->{name} [$m1]");
			end_wid($win_info_ref->{id},$m1);
		}
	}
}

sub end_by_wid
{
	my $wid = shift;
	my @msg = ('%(f)' , 'x' ,'{ENT}');
	my @wins = GetWindowsFromPid_ext($pid);
	
	for my $m1 (@msg) {
		loge("$wid [$m1]");
		end_wid($wid,$m1);
	}
}

sub query_by_wid
{
	my $wid = shift;
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
		my $wname = GetWindowName($wid) || "XX";
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
	print "GetWindowsFromPid_ext [$pid]\n";


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
		my $wname = GetWindowName($wid) || "XX";
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
	my $win_id = shift;
	my $message = shift;
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

	my @pid_array = split /,/,$pid;

	my @wins = ();

	my @all_wins = GetWindows_ext();
	foreach my $aw (@all_wins) {		
		for my $p1 (@pid_array) {
			if ($aw->{pid} == $p1) {
				push @wins, $aw;
				last;
			}
		}
	}
	return(@wins);
}

sub GetWindows_ext {
	my @wins = ();

	my @all_wins = GetChildWindows(GetRootWindow(0));
	foreach my $aw (@all_wins) {
		my $aw_pid = 0;

		if (IsWindowViewable($aw)) {
			$aw_pid = GetWindowPid($aw);
			if ($aw_pid > 0) {
#				my $pname=GetProcessName($aw_pid);
				my $wname = GetWindowName($aw) || "XX";
				my %win_info = (pid => $aw_pid, wid => $aw, wname => $wname);
#				my %win_info = (pid => $aw_pid, wid => $aw, wname => $wname, pname =>$pname);
				push @wins, \%win_info;
			}
		}
	}
	return(@wins);
}
sub MoveCursor
{
	my $cursor_tomove = shift;
	my @xny = split /,/ , $cursor_tomove;

	my $res_m = MoveMouseAbs($xny[0] , $xny[1]);
	sleep(1);
	my $res_b = PressMouseButton(M_LEFT);
	sleep(1);
	my $res_b1 = ReleaseMouseButton(M_LEFT);

	print "move to $xny[0] and $xny[1] and press left button result [$res_m,$res_b,$res_b1]\n";
}
sub GetCursor
{
	my ($x, $y, $scr_num) = GetMousePos();
	print "mouse $x, $y, $scr_num\n";
}
sub GetProcessName
{
	my $pid = shift;
	my $pname=`ps -o cmd= -p $pid`;
#	my $qname=`ps -o cmd= -p $pid`;
#	system("date >> /tmp/arnold_agent_check.log");
#	system("echo 'ps -o cmd= -p' $pid >> /tmp/arnold_agent_check.log");
#	system("echo $pname >> /tmp/arnold_agent_check.log");
	if ($pname =~ /^(\S+)[ \n]/) {
		$pname = $1;
	} else {
## When process is gone, the ps command get null string
		$pname  = "GONE" . $pname;
#	system("echo $pid >> /tmp/arnold_agent_check.log");
#	system("ps -ef | grep $pid >> /tmp/arnold_agent_check.log");
#	system("ps -O cmd= -p $pid >> /tmp/arnold_agent_check.log");
	}
	$pname = "" unless (defined $pname);
	return $pname;
}
sub loge
{
        my $msg = $_[0];

	my $log_fname = "/tmp/X11_agent.log";
        open(LOG , ">> $log_fname");
        my $time_mark = localtime;
        my $epoch_time = time();
        print LOG "$time_mark : ($epoch_time)\n$msg\n";
        close(LOG);
}

