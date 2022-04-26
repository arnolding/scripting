package ProcWin;
use strict;
use warnings;
use Proc::ProcessTable;
use File::Find::Rule;
use File::Basename;
use File::stat;
use Data::Dumper;
use Notify;
use POSIX ":sys_wait_h";
$Data::Dumper::Sortkeys = 1;
$Data::Dumper::Deepcopy = 1;
#See package description at tail
my @pid_in_group;
my $ntree_pre;
my @capture_history = ();
sub new 
{
	my $class = shift;
	my $pid_or_command = shift;
	my $running_deck_dir = shift;
	
	my $self;

	my $cap_dir = "/tmp/arnoldh1234";
	system("mkdir -p $cap_dir") if (! -d $cap_dir);

	if ($pid_or_command =~ /^\d+$/) {
		if (pid_exists($pid_or_command)) {
			$self = {pid =>$pid_or_command, wid => -1, 
				command => ""};
		}
	} else {
		my ($pid , $wid) = start($pid_or_command , $running_deck_dir);
		$self = {pid =>$pid, wid => $wid,
			command => $pid_or_command };
	}
	$self->{deck} = "";
	$self->{dbTitle} = "";
	$self->{ptree} = {};
	$self->{ntree} = {};
	$self->{windows} = [];
	$self->{pwindows} = {};
	$self->{ctime} = 0;
	$self->{rec_seq} = 1;
	$self->{log_path} = $cap_dir;
    $self->{child_progress} = {};
	$self->{running_deck_dir} = $running_deck_dir;
## $self->{running_deck_dir} >> /home/arnold/reg/tests/last/output-0330-8/running/bjtex11

	@capture_history = ();

	bless $self, $class;
}
sub is_children_closed
{
	my $self = shift;
	my $pid = $self->{pid};

	return (scalar @{$self->{ntree}->{$pid}->{local_child}} == 0);
}
sub is_children_just_tp
{
	my $self = shift;
	my $pid = $self->{pid};
	my $pwin_href = $self->{pwindows};

	my @windows = @{$self->{windows}};
	my %pid_in_windows;

	my $current = time();
	for my $w1 (@windows) {
		if ($w1->{wid} != 0) {
			if (index($w1->{_NET_WM_WINDOW_TYPE}, "SPLASH") >= 0) {
				$pid_in_windows{$w1->{pid}} = -1;
				if ((exists $pwin_href->{$w1->{wid}}) and
					(($current - $pwin_href->{$w1->{wid}}->{first_detected}) > 300)) {
					#kill -9, $w1->{pid};
					return $w1->{pid};
				}
			} else {
				$pid_in_windows{$w1->{pid}} = 1;
			}
		}
	}
	for my $cid (@{$self->{ntree}->{$pid}->{local_child}}) {
		my $cmnd_s = cmndline_short($self->{ntree}->{$cid}->{cmndline});
		return 0 if (($cmnd_s ne "tonyplot.exe") and
					($cmnd_s ne "tonyplot3d.exe"));
		return 0 if (not exists $pid_in_windows{$cid});

		##below means the windows is still in SPLASH stage
		return 0 if ($pid_in_windows{$cid} < 0);
	}
	return 1;
}
sub fin
{
	my $self = shift;
	my $wclose = '%(f){pause 1500}x';
	my $res = `agent.pl -w $self->{wid} -m '$wclose'`;
	my $kid;
	do {
            sleep 5;
            $kid = waitpid($self->{pid}, WNOHANG);
            warn "waiting [$self->{command}] to exit";
	} while $kid == 0;
	print "waitpid get $kid, pid = $self->{pid}\n";
}
sub fin_deck
{
	my $self = shift;

	$self->{deck} = "";
	$self->{dbTitle} = "";
	$self->{ptree} = {};
	$self->{ntree} = {};
	$self->{windows} = [];
	$self->{pwindows} = {};
}
sub set_deck
{
	my $self = shift;
	my $deck = shift;
	my $log_path = shift;
	
	$self->{deck} = $deck;
	$self->{log_path} = $log_path;
}
sub save_children_tmp
{
	my $self = shift;

	open NCHILD , "> /tmp/child_" . $self->{pid} . ".log" or die $!;

	print NCHILD Data::Dumper->Dump([$self->{child}]);

	close NCHILD;
}
sub kill_overlimit
{
	my $self = shift;
	my $limitcpu = $self->{limitcpu} || 600;
	$limitcpu = $limitcpu * 1000000;
	
	my $pid = $self->{pid};
	my @tobekilled = ();
	my @descendant = ();
	for my $ppp (keys %{$self->{ntree}}) {
		next if ($ppp == $pid);
		my $pt = $self->{ntree}->{$ppp};
		if ( $pt->{time} > $limitcpu ) {
			if (scalar @{$pt->{local_child}} == 0) {
				push @tobekilled , $ppp;
			} else {
				push @descendant, $self->get_descendant($ppp);
			}
		}
	}

	if ($#tobekilled >= 0) {
		print "tobekilled :";
		print "$_ " for (@tobekilled);
		print "\n";
		kill 9, @tobekilled;
		return 2;
	} else {
		for my $cid (@descendant) {
			my $pt = $self->{ntree}->{$cid};
			if ( (scalar @{$pt->{local_child}} == 0) and
				 ($pt->{time} > 60*1000000)) {
				push @tobekilled , $cid;
			}
		}
		if ($#tobekilled >= 0) {
		print "descendant :";
		print "$_ " for (@tobekilled);
		print "\n";
			kill 9, @tobekilled;
			return 1;
		}
	}
	return 0;
}
sub get_descendant
{
	my $self = shift;
	my $pid = shift;

	my @local = @{$self->{ntree}->{$pid}->{local_child}};
	for my $cid (@local) {
		push @local, $self->get_descendant($cid);
	}
	return @local;
}
sub find_children_n_windows
{
	my $self = shift;
	my $trace = shift || "notrace";

	my $ptree_h = $self->make_tree();
	my $mtree_retry = 10;
	while (($ptree_h == 0) and ($mtree_retry >0)) {
		$ptree_h = $self->make_tree();
		$mtree_retry--;
		if ($mtree_retry <= 5) {
			print "\n\n";
			die "find_children but retry failed several times";
		}
	}
	$self->{ntree} = $ptree_h;
	$self->update_ptree($ptree_h);
	$self->{acc_children} = $self->acc_ptree($self->{pid});

	my @windows = @{getwindow_from_pids(join(',' , @{$self->{child}}))};
	for my $w1 (@windows) {
## arnold 20201105
## There might be windows not completely created and displayed
##
#		if ($w1->{wid} == $self->{dbWin}) {
#			$self->{dbTitle} = $w1->{wname};
#		}
#		$self->all_windows_add($w1->{wname});
		if ($w1->{wid} == $self->{wid}) {
			$self->{dbTitle} = $w1->{wname};
		}
	}
	$self->{windows} = \@windows;
	$self->update_pwindows();
	$self->rec() if ($trace eq "trace");
}
sub rec
{
	my $self = shift;
	return unless ($self->{log_path});
	my $fn = "$self->{log_path}/pwin-" . $self->{rec_seq} . ".log";
	print "===[$fn]\n";
	my $fh;
	open($fh," > $fn") or die $!;
	print $fh "Now " . time() . "\n";
	my $track_children = $self->print_ptree($self->{pid}, "", 1);
	print $fh $track_children;
	print $fh "\n";
	print $fh $self->print_windows_str();
	close($fh);
	$self->{rec_seq}++;
}
sub is_children_sleep
{
    my $self = shift;
    my $pt = $self->{ptree}->{$self->{pid}};
    my $acc_all = $self->{acc_children};

    my $result = 0;
    my $progress = {
        since => time(),
        ctime   => $acc_all->{ctime} - $pt->{ctime},
        time    => $acc_all->{time} - $pt->{time}
    };
    if ($self->{child_progress}) {
        if (($progress->{ctime} == $self->{child_progress}->{ctime}) and
            ($progress->{time} == $self->{child_progress}->{time})) {
            if (($progress->{since} - $self->{child_progress}->{since}) > 60) {
                $result = 1;
            }
        } else {
            $self->{child_progress} = $progress;
        }
    } else {
        $self->{child_progress} = $progress;
    }
    return $result;
}

sub make_tree
{
	my $self = shift;
	my @child = ($self->{pid});
	my %ptree = ();
	my $i;
	my $t = new Proc::ProcessTable;
	my %t_hash = ();
	for my $p ( @{$t->table}) {
		$t_hash{$p->{pid}} = $p;
	}

	for ($i = 0 ; $i <= $#child ; $i++) {
		my $ppid = $child[$i]; ## as a parent pid
		$ptree{$ppid} = $t_hash{$ppid};
		#my $prod = get_prod($ptree{$ppid});
		my @local_child = ();

		for my $cid (keys %t_hash) {
			if ($t_hash{$cid}->{ppid} == $ppid) {
				push(@child , $cid);
				push(@local_child , $cid);
			}
		}

#		if ((($prod->{prod} eq "/bin/csh") or
#			 ($prod->{prod} eq "/usr/bin/time")) and
#		    (scalar @local_child == 0)) {
#			warn "encountered csh or time";
#		}
		$ptree{$ppid}->{local_child} = \@local_child;
	}

	$self->{child} = \@child;
	return \%ptree;
}
sub get_prod
{
    my $pt = shift;
	my %prod_info;

    my $cmndline = $pt->{cmndline};
	if ($cmndline) {
    	$cmndline =~ s/^\s+|\s+$//g;
		my @cmnds = split ' ', $cmndline;
##debug for cmnds[0] uninitialized
		if ($#cmnds < 0) {
			for my $k (keys %$pt) {
				print "$k \t $pt->{$k}\n";
			}
			die "cmndline [$cmndline]";
		}
		if ($cmnds[0] =~ /(\S+)\/lib\/(\S+)\/(\S+)\/(\S+)\/(\S+)$/) {
			my ($install, $prod, $ver, $prod_platform, $prod_exe) =
                ($1, $2, $3, $4, $5);
			$prod_info{install} = $install;
			$prod_info{prod} = $prod;
			$prod_info{ver} = $ver;
			$prod_info{platform} = $prod_platform;
			$prod_info{exe} = $prod_exe;
        } else {
			$prod_info{prod} = $cmnds[0];
    	}
	} else {
		$prod_info{prod} = $pt->{fname};
	}

	return \%prod_info;
}
sub getwindow_from_pids
{
	my $pids = shift;
	my $i = 0;
	#while ( (my @call_details = (caller($i++))) ){
    #	print STDERR $call_details[1].":".$call_details[2]." in function ".$call_details[3]."\n";
	#}

	return [] unless ($pids);
	my $xprop_cnt = 0;
	my $result = `xprop -pid $pids`;
	$xprop_cnt++;
	my $st = $?;
	while ($st != 0) {
		$result = `xprop -pid $pids`;
		$st = $?;
		$xprop_cnt++;
		die "xprop error for 100 times" if ($xprop_cnt > 100);
	}

	my $VAR1;
	eval $result;
	for my $w1 (@$VAR1) {
		if (exists $w1->{_NET_WM_NAME}) {
			$w1->{wname} = $w1->{_NET_WM_NAME};
		} else {
			$w1->{wname} = "XX";
		}
	}

	return $VAR1;
}
sub acc_ptree
{
	my $self = shift;
	my $pid = shift;
	my $out_acc = {ctime	=> 0, time	=> 0};
	my $pt = $self->{ptree}->{$pid};
	my @local_children = @{$pt->{local_child}};
	
	$out_acc->{ctime} += $pt->{ctime};
	$out_acc->{time} += $pt->{time};

	for my $cid (@local_children) {
		my $cid_acc = $self->acc_ptree($cid );

		$out_acc->{ctime} += $cid_acc->{ctime};
		$out_acc->{time} += $cid_acc->{time};
	}
	return $out_acc;
}
sub update_ptree
{
	my $self = shift;
	my $ntree = shift;
	my $kind = shift;
	my $ptree = $self->{ptree};
	$self->{ctime} = $ntree->{$self->{pid}}->{ctime};
	for my $pid (keys %$ntree) {
		$ptree->{$pid} = $ntree->{$pid};
	}
}
sub update_pwindows
{
	my $self = shift;
	my $wins_aref = $self->{windows};
	my $pwin_href = $self->{pwindows};

	for my $w1 (@$wins_aref) {
		if (exists $pwin_href->{$w1->{wid}}) {
		} else {
			$w1->{first_detected} = time();
			$pwin_href->{$w1->{wid}} = $w1;
		}
	}
}
sub print_windows
{
	my $self = shift;
	my @windows = @{$self->{windows}};
	my $w_str = "";
	my $w0_str = "";

	for my $w1 (@windows) {
		if ($w1->{wid} != 0) {
			$w_str .= $w1->{pid} . "  " . $w1->{wid} . "  " . $w1->{wname} . "\n";
		} else {
			$w0_str .= $w1->{pid} . "  " . $w1->{wid} . "  " . $w1->{wname} . "\n";
		}
	}
	return $w_str . "\n" . $w0_str;
}
sub print_windows_str
{
	my $self = shift;
	my @windows = @{$self->{windows}};
	my $w_str = "";
	my $w0_str = "";

	for my $w1 (@windows) {
		if ($w1->{wid} != 0) {
			$w_str .= Data::Dumper->Dump([$w1]);
		}
	}
	return $w_str;
}
sub print_pshort
{
	my $self = shift;
	my $ptree_h = $self->{ntree};

	my $tmark0 = localtime;
	my $epoch = time();
	my $pout = "="x4 . $tmark0 . " = " . $epoch . "\n";
	for my $ppp (keys %$ptree_h) {
		$pout .= ">" x 40 . "\n";
		my $ppp_item = $ptree_h->{$ppp};
		$pout .= $ppp_item->{pid} . " " . ${get_prod($ppp_item)}->{exe} . "\n";
		$pout .= "<" x 40 . "\n";
	}
	$ntree_pre = $pout;
	return $pout;
}
sub print_ptable
{
	my $self = shift;
	my $ptree_h = $self->{ntree};

	my $tmark0 = localtime;
	my $epoch = time();
	my $pout = "="x4 . $tmark0 . " = " . $epoch . "\n";
	for my $ppp (keys %$ptree_h) {
		$pout .= ">" x 40 . "\n";
		my $ppp_item = $ptree_h->{$ppp};
		for my $qq ( sort keys %$ppp_item) {
			$pout .= "$qq	$ppp_item->{$qq}\n";
		}
		$pout .= "<" x 40 . "\n";
	}
	return $pout;
}
# \033[1;31m bold face and red \033[0m
sub print_ptree_l
{
	my $self = shift;
	my $pid = shift;
	my $padding = shift;
	my $last_one = shift || 0;
	
	my $limitcpu = $self->{limitcpu} || 36000;
	my $level = length $padding;
	my $out_buffer = "";
	my $pt = $self->{ntree}->{$pid};
#	warn "pid [$pid] [$padding]";
	my @local_children = @{$pt->{local_child}};


	my ($color_h , $color_t) = ("","");
#	if ($#local_children < 0 and $pt->{time} > $limitcpu*1000000) {
#	}
	if ($pt->{time} > $limitcpu*1000000) {
		$color_h = "\033[1;31m";
		$color_t = "\033[0m";
	}
	
	my $cmndline = $pt->{cmndline};
	my $max_len = 80 - 2*$level - 10;
	if (length($cmndline) > $max_len) {
###print "[$cmndline]\n";
		my @cmnds = split ' ', $cmndline;
		my @cmnd1st = split '/' , $cmnds[0];
		shift @cmnds;
		$cmndline = "..." . $cmnd1st[$#cmnd1st] . ' ' . join(' ' , @cmnds);
	}
	if (length($cmndline) > $max_len) {
		$cmndline = substr($cmndline , 0 , $max_len) . "...";
	}


	if ($level == 0 ) {
		my $dd = `date`;
##		print "\n\n\n" , $dd;
		$out_buffer = "\n\n" . $dd;
	}

	my $prefix = "";
	for (my $ix = 0 ; $ix < length($padding) - 1 ; $ix++) {
		my $cont = substr $padding , $ix , 1;
		if (($cont eq "1") ) {
			$prefix .= "│ ";
		} else {
			$prefix .= "  ";
		}
	}

	my $heading = $prefix;
	if ($level) {
		if ($last_one) {
			$heading .= "└─";
		} else {
			$heading .= "├─";
		}
	}
#	print $heading , $pid , "  " , $cmndline , "\n";
	$out_buffer .= $heading . $pid . "  " . $cmndline . "\n";

	$heading = $prefix;
	
	if (($#local_children<0) && $last_one) {
	  if ($level) {
		$heading .= "   ";
	  }
	} else {
	  if ($level) {
		if (not $last_one) {
			$heading .= "│ ";
		} else {
			$heading .= "  ";
		}
	  }
	  if ($#local_children<0) {
		$heading .= " ";
	  } else {
		$heading .= "│";
	  }
	}
	
#	print $heading, " " x (1+length($pid)) , "ctime [$pt->{ctime}] time [$pt->{time}] \n";
	$out_buffer .= $color_h . $heading. " " x (1+length($pid)). "ctime [$pt->{ctime}] time [$pt->{time}]" . $color_t . "\n";

	for my $cid (@local_children) {
		if ($cid == $local_children[$#local_children]) {
			$out_buffer .= $self->print_ptree($cid , $padding."0" , 1);
		} else {
			$out_buffer .= $self->print_ptree($cid , $padding."1" , 0);
		}
	}
	return $out_buffer;
}
sub cmndline_short
{
	my $full_line = shift;
	return "" if (! $full_line);
	my @cmnds = split ' ', $full_line;
	my @cmnds_short;
	for (@cmnds) {
		my @cmnd1st = split '/' , $_;
		push @cmnds_short , $cmnd1st[-1];
	}
	my $cmndline = $cmnds_short[0];
	if (($cmndline eq "csh") or
		($cmndline eq "sh") or
		($cmndline eq "time")) {
		if (defined $cmnds_short[1] and
			(substr($cmnds_short[1],0,1) eq '-') and
			(defined $cmnds_short[2])) {
			$cmndline .= " " . $cmnds_short[1] . " " . $cmnds_short[2];
		}
		if (defined $cmnds_short[1] and
			(substr($cmnds_short[1],0,1) ne '-') ) {
			$cmndline .= " " . $cmnds_short[1];
		}
	}

	return $cmndline;
}
sub print_ptree
{
	my $self = shift;
	my $pid = shift;
	my $padding = shift;
	my $last_one = shift || 0;
	
	return "" if ($pid == 0); 
	my $limitcpu = $self->{limitcpu} || 36000;
	my $level = length $padding;
	my $out_buffer = "";
	my $pt = $self->{ntree}->{$pid};
	warn "pid [$pid] [$padding]";
	my @local_children = @{$pt->{local_child}};


	my ($color_h , $color_t) = ("","");
#	if ($#local_children < 0 and $pt->{time} > $limitcpu*1000000) {
#	}
	if ($pt->{time} > $limitcpu*1000000) {
		$color_h = "\033[1;31m";
		$color_t = "\033[0m";
	}

	if ($level == 0 ) {
		my $dd = `date`;
##		print "\n\n\n" , $dd;
		$out_buffer = "\n\n" . $dd;
	}

	my $prefix = "";
	for (my $ix = 0 ; $ix < length($padding) - 1 ; $ix++) {
		my $cont = substr $padding , $ix , 1;
		if (($cont eq "1") ) {
			$prefix .= "│ ";
		} else {
			$prefix .= "  ";
		}
	}

	my $heading = $prefix;
	if ($level) {
		if ($last_one) {
			$heading .= "└─";
		} else {
			$heading .= "├─";
		}
	}
	my $cmndline_s = cmndline_short($pt->{cmndline});
	$out_buffer .= $heading . $pid . "  " . $cmndline_s . "\n";

	$heading = $prefix;
	
	if (($#local_children<0) && $last_one) {
	  if ($level) {
		$heading .= "   ";
	  }
	} else {
	  if ($level) {
		if (not $last_one) {
			$heading .= "│ ";
		} else {
			$heading .= "  ";
		}
	  }
	  if ($#local_children<0) {
		$heading .= " ";
	  } else {
		$heading .= "│";
	  }
	}
	
#	print $heading, " " x (1+length($pid)) , "ctime [$pt->{ctime}] time [$pt->{time}] \n";
	$out_buffer .= $color_h . $heading. " " x (1+length($pid)). "ctime [$pt->{ctime}] time [$pt->{time}]" . $color_t . "\n";

	for my $cid (@local_children) {
		if ($cid == $local_children[$#local_children]) {
			$out_buffer .= $self->print_ptree($cid , $padding."0" , 1);
		} else {
			$out_buffer .= $self->print_ptree($cid , $padding."1" , 0);
		}
	}
	return $out_buffer;
}
sub winfo
{
	my $self = shift;
	return getinfo_from_wid($self->{wid});
}
sub getinfo_from_wid
{
    my $wid = shift;
    my $result = `agent.pl -s -w $wid`;
    my $VAR1;
    eval $result;
    return $VAR1;
}
sub pid_exists
{
	my $pid = shift;
	my $t = new Proc::ProcessTable;
	for my $p ( @{$t->table}) {
		return 1 if ($p->{pid} == $pid);
	}
	return 0;
}
sub StartApp 
{
	my @cmd = @_;
	my $pid = fork;
        if ($pid) {
                use POSIX qw(WNOHANG);
                sleep 1;
                waitpid($pid, WNOHANG) != $pid
                        and kill(0, $pid) == 1
                        and return $pid;
        } elsif (defined $pid) {
                use POSIX qw(_exit);
                exec @cmd or _exit(1);
        }
        return;
}
my $str_license = "There was a problem obtaining a license for DeckBuild";
my $str_hidden = "The hidden folder (containing history";
my $str_simerror = "a Simulator has unexpectedly exited";
my $str_tool_abnormal = "The tool exited abnormally";
my $str_exception_WM = 'Exception occurred inside simulator';
my $str_tonyploterr_WM = 'Tonyplot Error';
my $str_closing_WM = 'Closing';
my $str_deckerror_WM = 'Deck Error';
sub finish
{
	my $self = shift;
	my $wins_aref = [];
	my $str_re = "";
	my $str_re0 = "";
	my $str_same = 0;
print "                           finish\n";
	while ($str_same <10) {
		$self->find_children_n_windows("trace");
		$wins_aref = $self->{windows};
        
		$str_re = Data::Dumper->Dump($wins_aref);
		if ($str_re0 ne $str_re) {
			print $str_re;
			$str_re0 = $str_re;
			$str_same = 0;
		} else {
			print ".";
			$str_same++;
		}
		if (@$wins_aref) {
		  for my $win_ref (@$wins_aref) {
			
			if (($win_ref->{wid} ==0) or 
				($win_ref->{wname} eq "XX") or
				(index($win_ref->{_NET_WM_WINDOW_TYPE}, "SPLASH") >= 0)  ) {
				next;
			}
			if (index($win_ref->{_NET_WM_WINDOW_TYPE}, "DIALOG") >= 0) {
				my $w_dialog = capture($win_ref, $self->{running_deck_dir});
				if (($win_ref->{_NET_WM_NAME} eq $str_exception_WM) or
						($win_ref->{_NET_WM_NAME} eq $str_tonyploterr_WM) or
						($win_ref->{_NET_WM_NAME} eq $str_deckerror_WM)     ) {
						xcommand($win_ref->{wid} , "0xff0d" , $self->{running_deck_dir});
				} elsif ($win_ref->{_NET_WM_NAME} eq $str_closing_WM)  {
						xcommand($win_ref->{wid} , ["0xff09","0xff0d"], $self->{running_deck_dir});
						sleep(10);
						$str_same = 99;
				} elsif ($w_dialog) {
						my $ocr_txt = `cat $w_dialog->{ocr}`;
						if ((index($ocr_txt ,$str_license) >= 0) or 
							(index($ocr_txt ,$str_simerror) >= 0) or 
							(index($ocr_txt ,$str_tool_abnormal) >= 0) ) {
							xcommand($win_ref->{wid} , "0xff0d" , $self->{running_deck_dir});
						} elsif (index($ocr_txt ,$str_hidden) >= 0) {
							xcommand($win_ref->{wid} , "0xff0d" , $self->{running_deck_dir});
						} else {
							Notify::Notify("Unrecognized dialog $win_ref->{_NET_WM_NAME}");
							xcommand($win_ref->{wid} , "0xff0d" , $self->{running_deck_dir});
						}
				} else {
					Notify::Notify("Unrecognized dialog outer $win_ref->{_NET_WM_NAME}");
				}
			} elsif ($win_ref->{_NET_WM_WINDOW_TYPE} eq "_NET_WM_WINDOW_TYPE_NORMAL") {
				capture($win_ref, $self->{running_deck_dir});
				if (($win_ref->{_NET_WM_NAME} eq "Tonyplot") or 
					($win_ref->{_NET_WM_NAME} eq "TonyPlot3D")) {
					my $wid = $win_ref->{wid};
					print "process wid $wid\n";
					xcommand($wid , ["Alt+f","x"] , $self->{running_deck_dir});
				}
				if ((index($win_ref->{_NET_WM_NAME} , "DeckBuild") == 0) and
					(scalar @$wins_aref == 1)) {
					my $wid = $win_ref->{wid};
					print "process wid $wid\n";
					xcommand($wid , "Control+q" , $self->{running_deck_dir});
				}
			}
		  }
		} else {
			last;
		}
		sleep(1);
	}
	return scalar @$wins_aref;
}
sub start
{
    my $exe = shift;
	my $running_deck_dir = shift;
    my $s_pid = StartApp($exe);
	return (0,0) unless ($s_pid);
    my $start_pid;
    my $dbWin;
	my $start_count = 0;
	

    while ( ! defined $dbWin) {
        $start_pid = find_real_pid($s_pid);
		return (0,0) unless ($start_pid);


        my $wins_aref = getwindow_from_pids($start_pid);
        
		my $main_win = 0;
        for my $win_ref (@$wins_aref) {
			my $str_license = "There was a problem obtaining a license for DeckBuild";
            if (($win_ref->{wid} ==0) or 
				($win_ref->{wname} eq "XX") or
				(index($win_ref->{_NET_WM_WINDOW_TYPE}, "SPLASH") >= 0)  ) {
				next;
			} elsif (index($win_ref->{_NET_WM_WINDOW_TYPE}, "DIALOG") >= 0) {
				my $w_dialog = capture($win_ref, $running_deck_dir);
				if ($w_dialog) {
					my $ocr_txt = `cat $w_dialog->{ocr}`;
					if (index($ocr_txt ,$str_license) >= 0) {
						xcommand($w_dialog->{wid} , "0xff0d" , $running_deck_dir);
					}
				}
			} elsif ($win_ref->{_NET_WM_WINDOW_TYPE} eq "_NET_WM_WINDOW_TYPE_NORMAL") {
				capture($win_ref, $running_deck_dir);
				$main_win = $win_ref;
			}
            
            #if ((substr $win_ref->{wname} , -4 , 4 ) ne '.exe') {
            #    $dbWin = $win_ref->{wid};
            #}
        }
		if ($main_win and (scalar @$wins_aref == 1)) {
			$dbWin = $main_win->{wid};
			last;
		}
		
        sleep 1;
		ttprt("starting $exe and waiting main window\n") if ($start_count == 0);
		$start_count++;
		my $hours_count = int($start_count / 3600);
		my $mod = $start_count % 3600;
		if ($start_count == 30) {
			my $ping_out = `ping -c 1 8.8.8.8`;
			my $status = $?;
			if ($status != 0) {
				die;
			}

			print "checking SFLM_SERVERS " ,$ENV{SFLM_SERVERS} , "\n";
			$ping_out = `ping -c 1 $ENV{SFLM_SERVERS}`;
			$status = $?;
			if ($status != 0) {
				Notify::Notify("cannot connect license server $ENV{SFLM_SERVERS}");
			}
		}

		
		Notify::Notify("over 1 minutes cannot start deckbuild") if ($start_count == 60);
		Notify::Notify("over 10 minutes no deckbuild") if ($start_count == 600);
		Notify::Notify("over $hours_count hour(s) no deckbuild") if ($mod == 0);
	}
    ttprt("return of StartApp is $start_pid\n");

    return ($start_pid,$dbWin);
}
sub find_real_pid
{
    my $start_pid = shift;
    my @child = ($start_pid);
    my $i;
    my $t = new Proc::ProcessTable;
    my %t_hash = ();
    for my $p ( @{$t->table}) {
        $t_hash{$p->{pid}} = $p;
    }

    for ($i = 0 ; $i <= $#child ; $i++) {
        my $pid = $child[$i]; ## as a parent pid
        my $prod = get_prod($t_hash{$pid});
        if (defined $prod->{ver}) {
            return $pid;
        }

        for my $cid (keys %t_hash) {
            if ($t_hash{$cid}->{ppid} == $pid) {
                push(@child , $cid);
            }
        }
    }
    return undef;
}
## timetag print
sub ttprt
{   
    my $str = shift;
    my $now_string = localtime;
    print "$now_string : $str";
}   


sub wait_window_gone
{
	my $wid = shift;
	my $timeout = shift || 60; 
	#wait for 6 second
	
	my $gone = 0;
	my $waiting0 = "";
	my $ret0 = -1;
warn "wait_window_gone on [$wid] at line " , __LINE__;
ttprt( "command = [xprop -id $wid _NET_WM_PID]\n");

	while (!$gone and $timeout) {
		my $waiting = `xprop -id $wid _NET_WM_PID`;
		my $ret = $?;
		if (($waiting0 ne $waiting) or ($ret0 != $ret)) {
			ttprt( "waiting = [$waiting] and ret [$ret]\n");
			$waiting0 = $waiting;
			$ret0 = $ret;
		} else {
			print ".";
		}
		$gone = -1 if ($ret);
		sleep(1) if ($timeout--);
	}
print "\n";

	return $gone;
}
sub win_message
{
	my $win = shift;
	my $msg = shift;
	my $delay = shift;
	if (defined $delay) {
		system("agent.pl -w $win -d $delay -m \"$msg\"");
	} else {
		system("agent.pl -w $win -m \"$msg\"");
	}
}

# Following routines are static and not in class
sub print_ptree_by_pid
{
	my $ptree = shift;
	my $pid = shift;
	my $padding = shift;
	my $last_one = shift || 0;

	return "" if (($ptree ==0) or ($pid == 0)); 

	my $limitcpu = 36000;
	my $level = length $padding;
	my $out_buffer = "";
	my $pt = $ptree->{$pid};
	
	my @local_children = ();
	@local_children = @{$pt->{direct_child}} if ($pt->{direct_child});


	my ($color_h , $color_t) = ("","");

	if ($pt->{time} > $limitcpu*1000000) {
		$color_h = "\033[1;31m";
		$color_t = "\033[0m";
	}

	if ($level == 0 ) {
		my $dd = `date`;
##		print "\n\n\n" , $dd;
		$out_buffer = "\n\n" . $dd;
	}

	my $prefix = "";
	for (my $ix = 0 ; $ix < length($padding) - 1 ; $ix++) {
		my $cont = substr $padding , $ix , 1;
		if (($cont eq "1") ) {
			$prefix .= "│ ";
		} else {
			$prefix .= "  ";
		}
	}

	my $heading = $prefix;
	if ($level) {
		if ($last_one) {
			$heading .= "└─";
		} else {
			$heading .= "├─";
		}
	}
	my $cmndline_s = cmndline_short($pt->{cmndline});
	$out_buffer .= $heading . $pid . "  " . $cmndline_s . "\n";

	$heading = $prefix;
	
	if (($#local_children<0) && $last_one) {
	  if ($level) {
		$heading .= "   ";
	  }
	} else {
	  if ($level) {
		if (not $last_one) {
			$heading .= "│ ";
		} else {
			$heading .= "  ";
		}
	  }
	  if ($#local_children<0) {
		$heading .= " ";
	  } else {
		$heading .= "│";
	  }
	}
	
#	print $heading, " " x (1+length($pid)) , "ctime [$pt->{ctime}] time [$pt->{time}] \n";
	$out_buffer .= $color_h . $heading. " " x (1+length($pid)). "ctime [$pt->{ctime}] time [$pt->{time}]" . $color_t . "\n";

	for my $cid (@local_children) {
		if ($cid == $local_children[$#local_children]) {
			$out_buffer .= print_ptree_by_pid($ptree, $cid , $padding."0" , 1);
		} else {
			$out_buffer .= print_ptree_by_pid($ptree, $cid , $padding."1" , 0);
		}
	}
	return $out_buffer;
}

## input : pid
## source data from Proc::ProcessTable
## output : return ptree hash
##          the hash contains a top pid which is the input,
##          and all ProcessTable of the pid and its children process
##          add {direct_child} for each entry and add {child} for the top pid
sub make_tree_from_pid
{
	my $pid = shift;
	my @child = ($pid);
	my %ptree = ();
	my $i;
	my $t = new Proc::ProcessTable;
	my %t_hash = ();
	for my $p ( @{$t->table}) {
		$t_hash{$p->{pid}} = $p;
	}
	if (exists $t_hash{$pid}) {
	  for ($i = 0 ; $i <= $#child ; $i++) {
		my $ppid = $child[$i]; ## as a parent pid
		
		$ptree{$ppid} = $t_hash{$ppid};
		my @direct_child = ();

		for my $cid (keys %t_hash) {
			if ($t_hash{$cid}->{ppid} == $ppid) {
				push(@child , $cid);
				push(@direct_child , $cid);
			}
		}

		$ptree{$ppid}->{direct_child} = \@direct_child;
	  }

	  $ptree{$pid}->{child} = \@child;
	  $ptree{top} = $pid;
	}
	return \%ptree;
}

sub is_captured
{
	my $win = shift;
	for my $w_cap (@capture_history) {
		if (($win->{pid} == $w_cap->{pid}) and
			(($win->{wid} == $w_cap->{wid}) or ($win->{_NET_WM_NAME} eq 'Closing')) and
			($win->{_NET_WM_NAME} eq $w_cap->{_NET_WM_NAME}) ) {
			return 1;
		}
	}
	return 0;
}
sub add_captured
{
	my $win = shift;
	push @capture_history , $win;
}
# capture with win hash reference and save png to running_deck_dir
# also using tesseract to ocr
sub capture
{
	my $win = shift;
	my $running_deck_dir = shift;

	return 0 if (is_captured($win));
	my $wid = $win->{wid};
	my $appendix = $win->{wname};
	my $wid_name = "x" . $wid . ".xwd";

	if ((index($appendix, '/') >= 0) or
		(index($appendix, '(') >= 0)) {
		$appendix = $wid;
	} else {
		$appendix =~ s/ /_/g;
	}
	
	my $seq = 1;
	my $cap_name = $running_deck_dir . "/" . "capture_" . $seq . ".png";
	while ( -f $cap_name ) {
		$seq++;
		$cap_name = $running_deck_dir . "/" . "capture_" . $seq . ".png";
	}
	my $info_name = $running_deck_dir . "/" . "capture_" . $seq . ".info";
	my $tmp_name = $running_deck_dir . "/" . "capture_" . $seq . "x2.png";
	my $ocr_name = $running_deck_dir . "/" . "capture_" . $seq . "x2.txt";
	

	system("xraise $wid;sleep 1;xwd -frame -id $wid -out $wid_name");

	system("convert $wid_name $cap_name");
	


	my $res = getinfo_from_wid($wid);
    my $dbl_size = $res->{width}*2 . 'x' . $res->{height}*2;
	system("convert $cap_name -resize $dbl_size $tmp_name");
	system("tesseract $tmp_name " . substr($ocr_name , 0 , -4));
	
	$win->{ocr} = $ocr_name;
	open FH , ">$info_name";
	print FH Data::Dumper->Dump([$win]);
	print FH "\n";
	close FH;

	system("rm $wid_name $tmp_name");
	
	add_captured($win);
	return $win;
}

sub xcommand
{
	my $wid = shift;
	my $key_aref = shift;
	my $running_deck_dir = shift;
	
	$key_aref = [$key_aref] if ( ref $key_aref ne "ARRAY");

	return if (scalar @$key_aref == 0);
	my $cmd_logf = $running_deck_dir . "/xcommand.log";
	my $cmd = "xraise $wid;sleep 1; xsendkey -window $wid " . $key_aref->[0];
	my $k = 1;
	while ($key_aref->[$k]) {
		$cmd .= "; sleep 1; xsendkey -window $wid " . $key_aref->[$k++];
	}
	
	system("$cmd");
	system("echo \"$cmd\" >> $cmd_logf");
}
1;
# This package is to start (or attach to) a process , then monitor it.
# The {ntree} contains current processes started from pid
# {ptree} contains previous processes (finished ones) and current.
# {windows} contains current windows of the {ntree} and is array ref
# {pwindows} contains previous ones and current ones but hash ref.
