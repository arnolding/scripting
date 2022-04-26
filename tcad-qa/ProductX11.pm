package ProductX11;
use strict;
use warnings;
use POSIX ":sys_wait_h";
use X11::GUITest qw/:ALL/;
use RegUtil;
require Exporter;
our @ISA = qw(Exporter);
our @EXPORT = qw(start startpv GetWindowsFromPid_ext win_message );

my $dbWin;
sub startpv
{
	my $exe = $_[0];
	my $start_pid = StartApp($exe);
	die "Cannot exec $exe" unless ($start_pid);
	$dbWin = 0;
    my @wins = ();
    my $wait_count = 0;
    while ($dbWin == 0) {
		@wins = GetWindowsFromPid_ext($start_pid);
		if ( ! @wins) {
			sleep(3);
			print "wait windows for $start_pid\n";
			return $start_pid if ($wait_count > 10);
			$wait_count++;
			next;
		}

		for my $win_ref (@wins) {
			print "wid from start_pid $start_pid is [$win_ref->{wid}]\n";
			if ((substr $win_ref->{wname} , -4 , 4 ) ne '.exe') {
				$dbWin = $win_ref->{wid};
				last;
			}
		}
		sleep(1) if ($dbWin == 0);
	}
	return $start_pid;
}

sub find_real_pid
{
        my $pid = shift;
        my $real_pid = 0;
print "find_real_pid $pid\n";
        my $ps_str = `ps -o cmd= -p $pid`;
        chomp $ps_str;
print "self -> [$ps_str]\n";

#       if (( $ps_str =~ m/catchsegv/ ) ||
#               ( $ps_str =~ m/\/bin\/csh/ ) ||
#               ( $ps_str =~ m/usr\/bin\/time/)) {
#       }
        if ( $ps_str =~ /^\S+\/lib\/\S+\/(\d+\.\d+\.\d+\.[ABCR])\/x86_64-linux\/(\S+)$/ ) {
                my $ver = $1;
                my $exe = $2;
                print "ver [$ver] exe [$exe]\n";
                return $pid;
        } else {
                my $ps_str2;

                while ($real_pid <= 0) {
                        $ps_str2 = `ps --ppid $pid`;
                        chomp $ps_str2;
print "find child $ps_str2\n";
                        if ( $ps_str2 =~ m/^\s*(\d+)/m) {
                                return find_real_pid($1);
                        } else {
                                return 0 if ($real_pid == -2);
                                $real_pid--;
                                sleep(1);
                        }
                }
        }
        return 0;
}

sub start
{
    my $exe = shift;
#    my $deck = shift;
#    my $ofile = shift;
#    print "deckbuild $deck -outfile $ofile\n";
#    logx("deckbuild $deck -outfile $ofile");
#    my $start_pid = StartApp("deckbuild $deck -outfile $ofile");
    my $s_pid = StartApp($exe);
	die "Cannot exec $exe" unless ($s_pid);
    my $start_pid = find_real_pid($s_pid);

    print "return of StartApp is $start_pid\n";
    logx("return of StartApp is $start_pid");

    while ($start_pid == 0) {
	print "---------------------------------------\n";
	$start_pid = find_real_pid($s_pid);
    }

# Wait for it to appear within 120 seconds. RegEx: .* = zero or more of any character.
#    ( ($dbMainWin) = WaitWindowViewable('^DeckBuild', undef, 20) ) or die('Unable to find deckbuild window!');

    $dbWin = 0;
    my $win_title;
    my @wins = ();
    my $wait_count = 0;
    while ($dbWin == 0 or length($win_title) == 0) {
		@wins = GetWindowsFromPid_ext($start_pid);
		if ( ! @wins) {
			sleep(1);
			print "wait windows for $start_pid\n";
			exit if ($wait_count > 20);
			$wait_count++;
			next;
		}

		


		my $number = 0;
		for my $win_ref (@wins) {
			print "wid $number from start_pid $start_pid is [$win_ref->{wid}] and wname [$win_ref->{wname}]\n";
			$win_title = $win_ref->{wname};
			if ((substr $win_ref->{wname} , -4 , 4 ) ne '.exe') {
				$dbWin = $win_ref->{wid};
			}
			$number++;
		}
		sleep(1);
    }

    #my $res = SetInputFocus($dbWin);
    #my $res2 = SendKeys('{pause 500}{F9}');
    #logx("SetInputFocus res [$res] [$res2]");

    return ($start_pid,$dbWin);
}


sub win_message {
	
	my $win = shift;
	my $msg = shift;
	my $delay = shift;
	if (defined $delay) {
		system("agent.pl -w $win -d $delay -m \"$msg\"");
	} else {
		system("agent.pl -w $win -m \"$msg\"");
	}
}
sub GetWindowsFromPid_ext {
	my $pid = shift;
	my @wins = ();

	if ($pid <= 0) {
		return(undef);
	}
	
	my @children = find_children($pid);

print "check 2 $pid\n";
system("ps -p $pid");
	my @all_wins = GetChildWindows(GetRootWindow(0));
	my $arnold=0;
	foreach my $aw (@all_wins) {
		my $viewable = IsWindowViewable($aw);
##print "check 3 $aw , $arnold\n";
		my $aw_pid = 0;
		if ($viewable) {
		 	$aw_pid = GetWindowPid($aw);
			for my $child_pid (@children) {
				if ($aw_pid == $child_pid) {
					my $wname = GetWindowName($aw);
					my %win_info = (pid => $aw_pid, wid => $aw , wname => $wname);
					push @wins, \%win_info;
					last;
				}
			}
##print "check 35 $aw , viewable pid = $aw_pid , $arnold\n";
		}
#print "check 4 $aw_pid\n";
		
		$arnold++;
	}
	#sleep(10000);
	return(@wins);
}

sub find_children
{
	my $pid = shift;
	
	my @child = $pid;
	my $i;
	
	for ($i = 0 ; $i <= $#child ; $i++) {
		my $ppid = $child[$i]; ## as a parent pid

		my $ps_output = `ps --ppid $ppid`;
		my @ppid_lines = split /\n/ , $ps_output;
		for my $oneline (@ppid_lines) {
			if ($oneline =~ /^ *(\d+) /) {
				my $parsed_pid = $1;
				push(@child , $parsed_pid);
			}
		}
	}

	return @child;
}

1;

