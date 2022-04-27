package DeckInfo;
use strict;
use warnings;
use Proc::ProcessTable;
use File::Find::Rule;
use File::Basename;
use File::stat;
use Data::Dumper;
use POSIX ":sys_wait_h";
use RegUtil;
use Notify;
use ProcWin;
use POSIX qw(strftime);
use Date::Parse;

my $pwin;
our $_decks_aref = [];
my %class_var = (
		TestsPath => "/" ,
		target => "ls",
		skip => {},
		db_selected => [],
		one_sleep => 3,
		max_sleep => 3,
		pid => 0,
		dbWin => 0,
		pwin => $pwin
	);

my %product_winsize = (
	"deckbuild" => {
		dimension =>  
			{	"1280x800"	=> [860 , 528] ,
				"1920x1080"	=> [860 , 528] ,
				"1920x1200"	=> [860 , 528] },
		wname	=> 'DeckBuild' ,
		wclose	=> '%(f){pause 1500}x' },
	"tonyplot" => {
		dimension	=>
			{	"1280x800"	=> [612 , 573] ,
				"1920x1080"	=> [612 , 573] ,
				"1920x1200"	=> [612 , 573] },
		wname	=> 'Tonyplot' ,
		wclose	=> '%(f){pause 1500}x'},
	"tonyplot3d" => {
		dimension	=>
			{	"1280x800"	=> [600 , 600] ,
				"1920x1080"	=> [810 , 810] ,
				"1920x1200"	=> [900 , 900] },
		wname	=> 'TonyPlot3D' ,
		wclose	=> '%(f){pause 1500}x'}
	);
my %product_actual = (
	"deckbuild" => [],
	"tonyplot"  => [],
	"tonyplot3d" => []
	);
my $loop_notify;
my $stop_notify;
sub get_default_winsize
{
	my $keep_config = shift;
	my $screen_aspect = shift;
	my $not_matched = 0;
	for my $p (keys %product_winsize) {
		my $wsize_aref = $product_winsize{$p}->{dimension}->{$screen_aspect};
		if (! defined $wsize_aref) {
			warn "Not defined dimension for product $p and screen $screen_aspect";
		}
	}
	remove_config() unless ($keep_config);
	for my $p (keys %product_winsize) {
		my $pwin1 = new ProcWin($p);
		my $winfo = $pwin1->winfo();
		
		print "query product [$p]\n";
		my $wsize_aref = $product_winsize{$p}->{dimension}->{$screen_aspect};
		$wsize_aref = [0,0] unless (defined $wsize_aref);
		my $wname = $product_winsize{$p}->{wname};
		my $wclose = $product_winsize{$p}->{wclose};
		print "should be: $wsize_aref->[0] , $wsize_aref->[1] , $wname\n";
		print "actual   : $winfo->{width} , $winfo->{height} , $winfo->{wname}\n";
		$product_actual{$p} = $winfo;
##		sleep(2);
		if ((abs($winfo->{width}-$wsize_aref->[0])> 0.09*$wsize_aref->[0]) ||
		    (abs($winfo->{height}-$wsize_aref->[1])> 0.09*$wsize_aref->[1]) ||
		    (substr($winfo->{wname},0,length($wname)) ne $wname)) {
			print "!!!!! NOT DEFAULT WINDOW SIZE !!!!!\n";
			$not_matched++;
		} else {
			print "DEFAULT WINDOW SIZE Matched\n";
		}

		$pwin1->fin();
	}
	if ($not_matched and (not $keep_config) ) {
		print "\n\n";
		for my $p (sort keys %product_actual) {
			print "$p window size and Title are\n";
			my $winfo = $product_actual{$p};
			print $_, ": ",$winfo->{$_} , "\t" for (sort keys %$winfo);
			print "\n";
		}
		warn "Exit because of windows size not matched";
	}
	return 0;
}
sub remove_config
{
	 my @config = (
        '.config/Silvaco, Inc.',
        '.config/silvaco',
        '.silvaco'
        );
        my $home = $ENV{HOME};
	for my $cfg1 (@config) {
		my $path = $home . '/' . $cfg1;
		if ( -e $path ) {
			print "[" , $path , "] exists\n";
			my $res = `rm -r \"$path\"`;
			print "$res\n";
			print "after remove: ";
			if (-e $path) {
				print "[" , $path , "] exists\n";
			} else {
				print "it's gone\n";
			}
		}
	}
}
sub copy_cases
{
	my $original_case = shift;
	my $outpath = shift;

	my $work = $outpath . "/" . "cases";
	system("mkdir -p $work");
	if (ref($original_case) eq "ARRAY") {
		for my $o_case (@$original_case) {
			print "copy 1 from $o_case to $work\n";
			system("cp -r $o_case $work");
		}
	} else {
		print "copy 2 from $original_case to $work\n";
		system("cp -r $original_case $work");
	}
	$class_var{TestsPath} = $work;
	##$class_var{output_dir} = $outpath . "/" . "output";
}

sub skip
{
	my $skip_f = shift;
	my %skip_dir = ();
	unless (-f $skip_f) {
		$class_var{skip} = \%skip_dir;
		return;
	}
	open(SKIP , "<" , $skip_f);
	while (<SKIP>) {
		chomp;
		s/\s$//;
		#print "[$_]\n";
		$skip_dir{$_} = 1;
	}
	close(SKIP);
	$class_var{skip} = \%skip_dir;
}

sub init
{
	shift;
	my $original_case = shift;
	my $outputpath = shift;
##
# $original_case  >> /home/arnold/reg/tests/last/case0/4.2.12.R
# $outputpath     >> /home/arnold/reg/tests/last/output-mmdd-d
###
	$class_var{workpath} = dirname($outputpath);
	$class_var{target} = shift;
	my $keep_config = shift;

# Check the to-be-test target should not be default, should be "deckbuild"
	if ($class_var{target} eq 'ls') {
		print "No proper target\n";
		exit;
	}

	my $winfo = ProcWin::getinfo_from_wid(0);
	logx("DISPLAY : [". $ENV{DISPLAY} . "]");
	logx("width : $winfo->{width}, height : $winfo->{height}");
	my $screen_aspect = $winfo->{width} . "x" . $winfo->{height};
	
	$class_var{display_width} = $winfo->{width};
	$class_var{display_height} = $winfo->{height};
	#get_default_winsize($keep_config, $screen_aspect);
	system("echo \"[SimulatorSettings]\" >> $ENV{HOME}/.config/silvaco/DeckBuild.conf");
	if (exists $ENV{NrCPUs}) {
		system("echo \"NrCPUs=$ENV{NrCPUs}\" >> $ENV{HOME}/.config/silvaco/DeckBuild.conf");
	} else {
		system("echo \"NrCPUs=8\" >> $ENV{HOME}/.config/silvaco/DeckBuild.conf");
	}

=pod
	$pwin = new ProcWin($class_var{target});
	($class_var{pid} , $class_var{dbWin}) =
		($pwin->{pid} , $pwin->{wid});
	my $pname=`ps -o cmd= -p $class_var{pid}`;
	chomp($pname);
	my $pid_check .= "$class_var{pid} - [$pname]\n";


	logx("Start " . $pid_check);
=cut

	$class_var{TestsPath} = $original_case;
	$class_var{output_dir} = $outputpath;

#	$class_var{run_id} = logdb("test_run" , $class_var{TestsPath} , $pname , $class_var{pid});
#system("sleep 9909");

#	db_selected();
	$loop_notify = new Notify($class_var{pid});
	$stop_notify = new Notify($class_var{pid});
}

sub complete
{
	shift;

	ProcWin::win_message($class_var{dbWin} , '{pause 500}^(q)');
	$loop_notify->notify_flush();
}

sub class_var
{
	shift;
	my $var = shift;
	return $class_var{$var};
}
sub db_selected
{
	my $socket = open_client();
	my $sql="select * from deck where not exists (select 1 from test_v where deck.deck_name == test_v.deck_name);";
	print $socket "-header /build/qadb/db/b.db \"$sql\"\n";

	my $line;
	my $row = 0;
	my @fields = ();
	my @db_result = ();
	while (defined ($line = <$socket>)) {
		chomp $line;
		next unless ($line);
		my @val = split(/\|/,$line);
		if ($row == 0) {
			for (@val) {
				push @fields , $_;
			}
		} else {
			my %row_data = ();
			for (my $i=0 ; $i <= $#fields ; $i++) {
				$row_data{$fields[$i]} = $val[$i];
			}
			push @db_result , \%row_data;
		}
##		print "[$line]\n" if ($line);
		$row++;
	}
	close($socket);
#	$class_var{db_selected} = \@db_result;
}
sub decks
{
	shift;
	my $case_tags = shift;

warn "before $class_var{TestsPath}";
    my @deck_arr = File::Find::Rule->file()
                                   ->name("*.in")
                                   ->extras({follow => 1 })
                                   ->in("$class_var{TestsPath}");

	my @filter_ext = ();
	for (@deck_arr) {
		push @filter_ext, $_ if (/ex\d+\.in$/);
	}

	if (@$case_tags) {
		for my $a (@filter_ext) {
			for my $b (@$case_tags) {
				if (index($a , $b) >0) {
					push @$_decks_aref ,  $a;
				}
			}
		}
	} else {
		push @$_decks_aref , @filter_ext;
	}

	return $_decks_aref;
}
sub new 
{
	my $class = shift;
	my $case_fullpath = shift;
	my $self = {pid =>0, deckfull => $case_fullpath}; #pid 0 means not started yet

	my $last_seg = basename($case_fullpath);
	my $case_head = dirname($case_fullpath);
	my $case_category = basename($case_head);


	if (exists $class_var{skip}->{$case_category}) {
		print "case [$case_head] is skipped\n";
		return undef;
	}
	$self->{case_path} = $case_head;
	$self->{notify_tag} = "$case_category/$last_seg";
	
	$self->{deck_all} = $last_seg;
	$last_seg =~ /(.*)\.in/;
	my $DeckName = $1 || 'dummy';

	if ( $DeckName eq 'dummy') {
		print "cannot get DeckName for $last_seg\n";
		return undef;
	}
	$self->{case_log} = $case_head . "/." . $DeckName . "/deckbuild/rto.xml"; 
	$self->{deck} = $DeckName;
	$self->{logf} = $DeckName . ".log";
	$self->{output_deck} = $class_var{output_dir} . "/" . $DeckName;
	$self->{running_deck_dir} = "$class_var{output_dir}/running/$self->{deck}";
## $self->{running_deck_dir} >> /home/arnold/reg/tests/last/output-0330-8/running/bjtex11
	system("mkdir -p $self->{output_deck}");
	system("mkdir -p $self->{running_deck_dir}") 
		if (not -d $self->{running_deck_dir});
	
	$self->{pid} = $class_var{pid};
	$self->{dbWin} = $class_var{dbWin};

#	$self->{work_path} = $class_var{TestsPath} . "/" . $case_category;
	$self->{work_path} = $case_head;
	$self->{work_case_h} = $self->{work_path} . "/" . $DeckName;
	$self->{work_case} = $self->{work_case_h} . ".in";
	$self->{done} = $self->{work_case_h} . ".done";
	$self->{start} = $self->{work_case_h} . ".new";
	$self->{WER} = "";
	$self->{DIALOG} = 0;
	$self->{windows} = "";
	$self->{nowindow} = 0;
	$self->{all_windows_href} = {};
	$self->{ptree} = {};
	$self->{ctime} = 0;
	$self->{waitpid} = 0;
	$self->{stat_count} = 0;
	$self->{capture_folder} = $self->{work_path} . "/";
	$self->{capture} = 0;
	$self->{cap_rec} = [];
	$self->{main_gone} = 0;
	$self->{result} = "NONE";

	#$pwin->set_deck($self, "$self->{running_deck_dir}");
	bless $self, $class;
}
sub open_n_run2
{
	my $self = shift;
	my $pwd = `pwd`;
	chomp $pwd;
	$self->{pwd} = $pwd;
#	chdir($self->{case_path});
	$self->back_n_decor();
	$self->{f_mtime} = time();
	sleep(1);
	system("rm $self->{done}") if (-f $self->{done});
	system("touch $self->{start}");

	my $deck_shortname = basename($self->{deckfull});
# $deck_shortname   >> bjtex11.in
	my $deck_outf = $deck_shortname;
	$deck_outf =~ s/\.in/\.outf/;
	#my $cmd = "deckbuild -run $deck_shortname -outfile $deck_outf";
	my $cmd = "deckbuild $deck_shortname -outfile $deck_outf";
print "$cmd\n";
	chdir($self->{case_path});
	$self->{windows_before} = get_wins_on_display();
	$self->{main_gone} = 0;
	$pwin = new ProcWin($cmd , $self->{running_deck_dir});

	#my $winfo = ProcWin::getinfo_from_wid($pwin->{wid});
	#print "width $winfo->{width} height $winfo->{height}\n";
	if ($pwin->{wid}) {
		system("xraise $pwin->{wid}; xsendkey -window $pwin->{wid} F9");
	}
	
	#system($cmd);

return;
	$pwin->find_children_n_windows("new");

	#start bare deckbuild
	#$self->{pid} = start($self->{deckfull}, $self->{logf});
	#($self->{pid} , $self->{dbWin}) = start($class_var{target});

	print "Before open\n";
	
### 20161110 1838
	

	$self->{f_mtime} = time();

## added 2022/2/22 one deckbuild for each deck
	$pwin = new ProcWin($class_var{target});
	($class_var{pid} , $class_var{dbWin}) =
		($pwin->{pid} , $pwin->{wid});
	my $pname=`ps -o cmd= -p $class_var{pid}`;
	chomp($pname);
	my $pid_check .= "$class_var{pid} - [$pname]\n";

=pod
	my $exp_title = "DeckBuild - 5.2.8.R - " . $self->{work_case};
	$exp_title =  "- " . $self->{work_case};
	my $last_de = rindex $exp_title, "/";
	if ($last_de >=0) {
		substr($exp_title , $last_de , 1) = " - ";
	}

	$self->file_open();
	while (index($pwin->{dbTitle} , $exp_title) < 0) {
print "$pwin->{dbTitle} \nvs.\n$exp_title \n";
		warn "Check Windows Title not matched";
		$stop_notify->notify("Check Windows Title not matched",1,1);
#		system("sleep 99");
		$self->file_open();
	}
	warn "Check WindowsTitleMatched";
	

#	sleep(1);
	## F9 is to call "Run" command in deckbuild
	ProcWin::win_message($self->{dbWin} , '{pause 500}{F9}');
=cut

	$self->{status} = "running";
}
sub open_n_run
{
	my $self = shift;
	my $pwd = `pwd`;
	chomp $pwd;
	$self->{pwd} = $pwd;
#	chdir($self->{case_path});
	$self->back_n_decor();
	system("rm $self->{done}") if (-f $self->{done});
	system("touch $self->{start}");
	$pwin->find_children_n_windows("new");

	#start bare deckbuild
	#$self->{pid} = start($self->{deckfull}, $self->{logf});
	#($self->{pid} , $self->{dbWin}) = start($class_var{target});

	print "Before open\n";
	
### 20161110 1838
	$self->{windows_before} = get_wins_on_display();

	$self->{f_mtime} = time();

	my $exp_title = "DeckBuild - 5.2.8.R - " . $self->{work_case};
	$exp_title =  "- " . $self->{work_case};
	my $last_de = rindex $exp_title, "/";
	if ($last_de >=0) {
		substr($exp_title , $last_de , 1) = " - ";
	}

	$self->file_open();
	while (index($pwin->{dbTitle} , $exp_title) < 0) {
print "$pwin->{dbTitle} \nvs.\n$exp_title \n";
		warn "Check Windows Title not matched";
		$stop_notify->notify("Check Windows Title not matched",1,1);
#		system("sleep 99");
		$self->file_open();
	}
	warn "Check WindowsTitleMatched";
	

#	sleep(1);
	## F9 is to call "Run" command in deckbuild
	ProcWin::win_message($self->{dbWin} , '{pause 500}{F9}');

	$self->{status} = "running";
}
{
my $keyin_filename = "";
my $keyin_delay = 0;
sub file_open
{
	my $self = shift;
	my $open_dialog;
	my $stop;
	my $win_now;
    print "[", (caller(0))[3], "] and at line ",__LINE__, "\n";
	system("xraise $self->{dbWin}");
	ttprt(" [$self->{dbWin}] send command for open dialog\n");
	ProcWin::win_message($self->{dbWin} , '{pause 500}^(o)');
	sleep(1);
	
	my $w_count = 0;
	while (1) {
		$pwin->find_children_n_windows();
		$win_now = $self->check_windows('Select a deck to open');
		last if ($open_dialog = $win_now->{wanted});

		if ($stop = $win_now->{stop}) {
			do {
			ProcWin::win_message($stop->{wid}, '{pause 1000}{ENT}');
			} while (!ProcWin::wait_window_gone($stop->{wid}) );
		}
			
		ProcWin::win_message($self->{dbWin} , '{pause 500}^(o)');
		$w_count++;
		if ($w_count >= 9) {
			warn "Cannot get open deck dialog";
			print "Error\n" x 3;
			$stop_notify->notify("Cannot get open deck dialog, sleep 9999987",1,1);
			system("sleep 9999987");
		}
		sleep(1);
	}

	if ( $keyin_filename ne $self->{work_case} ) {
		ttprt(" [$open_dialog->{wid}] send text for deck\n");
		ProcWin::win_message($open_dialog->{wid}, "$self->{work_case}");
		$keyin_filename = $self->{work_case};
		$keyin_delay = 0;
	} else {
		$keyin_delay += 50;
		ttprt(" [$open_dialog->{wid}] send text for deck with delay $keyin_delay\n");
		ProcWin::win_message($open_dialog->{wid}, "$self->{work_case}", $keyin_delay);
	}
	$self->capture($open_dialog);
	ttprt(" [$open_dialog->{wid}] open button\n");
	ProcWin::win_message($open_dialog->{wid}, '{ESC}{pause 500}%(o)');
	while (!ProcWin::wait_window_gone($open_dialog->{wid})) {
		system("sleep 1");
		ttprt(" wait 1 second for open dialog to be gone\n");
	}
	$pwin->find_children_n_windows();
}
}

sub parse_duration {
  my $seconds = shift;
  my $hours = int( $seconds / (60*60) );
  my $mins = ( $seconds / 60 ) % 60;
  my $secs = $seconds % 60;
  return sprintf("%02d:%02d:%02d", $hours,$mins,$secs);
}
sub running_stat
{
	my $self = shift;
	my $stat = shift;
	my $fh;

	if ($stat eq "start") {
		open($fh," > $self->{running_deck_dir}/stat-start.log");
		print $fh "start at [" . localtime($self->{wait_t0}) . "] or $self->{wait_t0}\n";
		close($fh);
	}
	if ($stat eq "running") {
		my $fn = "$self->{running_deck_dir}/stat-" . $self->{stat_count} . ".log";
		open($fh," > $fn");
		my $e_str = parse_duration(time() - $self->{wait_t0});

		print $fh "Already elapse $e_str\n";
		my $track_children = $pwin->print_ptree($self->{pid}, "", 1);
		print $fh $track_children;
		print $fh "\n";
		print $fh $pwin->print_windows_str();
		close($fh);
		$self->{stat_count}++;
	}

	if ($stat eq "done") {
		my $done_str = "done";
		$done_str = "undid" if (not -f $self->{done});
		my $fn = "$self->{running_deck_dir}/stat-done.log";
		open($fh," > $fn");
		my $e_str = parse_duration(time() - $self->{wait_t0});

		print $fh "Finished with $e_str\n";
		print $fh "waitpid [$self->{waitpid}]\n";
		print $fh "status [$self->{status}]\n";
		print $fh "error dialog [$self->{WER}]\n";
		print $fh "deck is [$done_str]\n";
		close($fh);
	}
}
sub run_over_time
{
	my $self = shift;
	my $tt = time();

	if (($tt - $self->{wait_t0} ) > 
		$ENV{REG_MAX_SEC_PER_DECK} ) {
		return 1;
	} else {
		return 0;
	}
}

sub log_before_kill
{
	my $ph = shift;
	my $running_deck = shift;
	
	my $cc = 1;
	my $pre = "$running_deck/pwin-";
	my $fn = $pre . $cc. ".log";
	while (-e $fn) {
		$cc += 1;
		$fn = $pre . $cc. ".log";
	}
	my $fh;
	open($fh," > $fn") or die $!;
	print $fh "Now " . time() . "\n";
	print $fh Data::Dumper->Dump([$ph,$pwin]);
	print $fh "\n";
	close($fh);
}
sub capture_before_kill
{
	my $self= shift;
	my $wait_splash = 30;
	my $splash = 1;

	
	$pwin->find_children_n_windows();
	my $windows = $pwin->{windows};
	$self->running_stat("start");
	while ($splash) {
		$splash = 0;
	  for my $w1 (@$windows) {
		if (index($w1->{_NET_WM_WINDOW_TYPE} , "SPLASH") >=0) {
			$splash = 1;
			last;
		}
	  }
	  sleep(1);
	  $pwin->find_children_n_windows();
	  $windows = $pwin->{windows};
	  $self->running_stat("running");
	  $wait_splash--;
	  last if ($wait_splash <=0);
	}

	$self->running_stat("done");
	for my $w1 (@$windows) {
		$self->capture($w1);
	}
}

	
sub clean
{
	my $self = shift;
	$pwin->find_children_n_windows();
	my $ptree_h = $pwin->{ntree};

	log_before_kill($ptree_h, $self->{running_deck_dir});
	

	my @all_kill = (@{$pwin->{child}} , $pwin->{pid});
	$self->capture_before_kill(\@all_kill);
	log_before_kill(\@all_kill, $self->{running_deck_dir});

	
	my $winfo = ProcWin::getwindow_from_pids(join(',' , @{$pwin->{child}}));
	for my $w1 (@$winfo) {
		print Data::Dumper->Dump([$w1]);
		if ($w1->{_NET_WM_NAME} eq "Tonyplot") {
			my $wid = $w1->{wid};
			print "process wid $wid\n";
			system("xraise $wid; xsendkey -window $wid Alt+f; xsendkey -window $wid x");
			system("sleep 3");
		}
	}

	$winfo = ProcWin::getwindow_from_pids($pwin->{pid});
	for my $w1 (@$winfo) {
			print Data::Dumper->Dump([$w1]);
			my $wid = $w1->{wid};
			print "process wid $wid\n";
			system("xraise $wid; xsendkey -window $wid Control+q");
			sleep(1);
			system("xraise $wid; xsendkey -window $wid 0xff0d");
	}
	#print "sleep 100\n";
	#system("sleep 100");
	#print "width $winfo->{width} height $winfo->{height}\n";
	kill 9, @{$pwin->{child}};
	kill 9, $pwin->{pid};
}
sub win_gen
{
	my $self = shift;
	my $str = "";
	my $count = 0;
	my $windows_gen_aref = get_wins_on_display();

	for my $w1 (@$windows_gen_aref) {
		my $new_win = 1;
		for my $w2 (@{$self->{windows_before}}) {
			if ( ($w1->{pid} == $w2->{pid}) &&
				 ($w1->{wid} == $w2->{wid}) ) {
				$new_win = 0;
				last;
			}
		}
		if ($new_win) {
			if (($w1->{pid} == $pwin->{pid}) &&
				(index($w1->{wname}, "DeckBuild") != 0)) {
				$self->{main_gone} += 1;
			}  
			$str .= Data::Dumper->Dump([$w1]);
			$count++;
		}
	}
	$str .= "    win gen $count\n";
	if ($count == 0) {
		$self->{main_gone} += 1;
	}
	return $str;

}
sub waitloop
{
	my $self = shift;
	$self->{status} = "waiting";
	$self->{logsize} = 0;
	$self->{wait_count} = 0;
	$self->{wait_t0} = time();

	my $count = 0;
	my $lengthy_kill =0;
	my $str0 = "";

	while (1) {
		
#		warn "........sleep for [$class_var{one_sleep}] seconds\n";
		suspend();
		system("sleep $class_var{one_sleep}");

		$self->{WER} = "";
		$self->{DIALOG} = 0;
		my $str = $self->win_gen();
		if ($str ne $str0) {
			print $str;
			print "WWWWwaitloop again\n";
			$str0 = $str;
		} else {
			print ".";
		}


		if ((-f $self->{done}) ||
			($self->{main_gone} >3)) {
			$self->{result} = "DONE";
			last if ($pwin->finish() == 0);
			#$self->clean() if ($pwin->{pid});
		}
		if ($self->run_over_time()) {
			$self->{result} = "KILL";
			$self->clean() if ($pwin->{pid});
			last;
		}
	}
	$self->{wait_t1} = time();

	my $term_count = 0;
	my $term_status;
	my $terminated_pid = waitpid($pwin->{pid}, WNOHANG);
	$term_status = $?;
	while ($terminated_pid == 0) {
		$term_count++;
		print "Waiting $pwin->{pid} to be done, count $term_count\n";
		sleep(1);
		
		$terminated_pid = waitpid($pwin->{pid}, WNOHANG);
		$term_status = $?;
	}
	return $term_status;

}
sub waitloop2
{
	my $self = shift;
	$self->{status} = "waiting";
	$self->{logsize} = 0;
	$self->{wait_count} = 0;
	$self->{wait_t0} = time();

	my $count = 0;
	my $lengthy_kill =0;
	my $loop_check;

	$self->running_stat("start");

	while (1) {
		print "W"x10, "waitloop again\n";
#		warn "........sleep for [$class_var{one_sleep}] seconds\n";
		suspend();
		system("sleep $class_var{one_sleep}");

		$self->{WER} = "";
		$self->{DIALOG} = 0;
		$loop_check = $self->wait_n_check(); ## LENGTHY or WINERR
		$self->running_stat("running");
		last if (($loop_check eq "DONE") ||
				 ($loop_check eq "WINERR"));
	}
	$self->{wait_t1} = time();
	sleep(1);
	print "$loop_check\n\n\n";
	$self->running_stat("done");
}
sub resume_spot
{
	my ($mx, $my, $mscr) = GetMousePos();
#	print "Current mouse at $mx, $my\n";
	if (($class_var{display_width} - $mx < 10) && ($class_var{'display_y'} - $my < 20)) {
		return 1;
	}
	return 0;
}
sub get_wins_on_display
{
	my @wins = ();
	my $display_wins = `track_wins.pl`;

	my @w_split = split /\n/ , $display_wins;

	for my $one (@w_split) {
#print "<<$one>>\n";
		if ($one =~ /^(\d+), (\d+), [\d,]+\[(.*)\], \[(.*)\]/) {
			my %w_one = (pid => $1, wid => $2 , wname => $3 , pname =>$4);
			push @wins, \%w_one if ($w_one{pid} > 0);
		}
	}
	return \@wins;
}
sub all_windows_str
{
	my $self = shift;
	my $all_win_href = $self->{all_windows_href};
	my $win_str = "";

	for my $all_w (sort keys %$all_win_href) {
		$win_str .= " [" . $all_w . "]";
	}
	return $win_str;
	
}
sub all_windows_add
{
	my $self = shift;
	my $wname = shift;
	my $all_win_href = $self->{all_windows_href};
	if (exists $all_win_href->{$wname} ) {
		$all_win_href->{$wname}++;
	} else {
		$all_win_href->{$wname} = 1;
	}
}
sub fin_tp
{
	my $self = shift;
	my $save_fname = shift;

	my $fh;
	open($fh," > $save_fname/ProcWin.log");
	print $fh Data::Dumper->Dump([$pwin]);
	close($fh);

	my $subw;
	my $stop;
	while (1) {
	  for my $w1 (@{$pwin->{windows}}) {
		next if ($w1->{wid} == $pwin->{wid});
		close_window($w1);

	  }

	  $pwin->find_children_n_windows();
	  if (scalar @{$pwin->{windows}} > 1) {
		print "\n\n\n          Take a look at ProcWin2.log\n\n";
		$stop_notify->notify("Non-cleaned windows, sleep 10304050",1,1);
		open($fh," > $save_fname/ProcWin2.log");
		print $fh Data::Dumper->Dump([$pwin]);
		close($fh);
#		system("sleep 10304050");
	  }
	  $subw = $self->check_windows();
		if ($stop = $subw->{MODAL}) {
			do {
			ProcWin::win_message($stop->{wid}, '{pause 1000}%({F4})');
			} while (!ProcWin::wait_window_gone($stop->{wid}) );
		}
	  
	  last if ($pwin->is_children_closed());
	}
}
sub finish
{
	my $self = shift;
	my $orig = $self->{deckfull};

	my $subw;
	my $count = 0;
	my $check_tp;

	#print "I'll wait for 30 seconds ...\n";
#	sleep(30);
	while (1) {
		$pwin->find_children_n_windows();
		$subw = $self->check_windows();
system("date");
print "wait for all children closed, count $count and acc---------   >>> \n";
print $pwin->print_ptree($self->{pid}, "", 1) , "\n";
		
		suspend();
		$check_tp = $pwin->is_children_just_tp();
		last if ($check_tp == 1);
		if ($check_tp > 0) {
			$stop_notify->notify("kill $check_tp for tonyplot SPLASH persists",1,1);
			system("kill -9 $check_tp");
		}
		$count += 1;
		sleep(1);
	}

# Now close tonyplot

	$self->fin_tp($self->{running_deck_dir});
	$pwin->fin_deck();
	$self->{windows_after} = get_wins_on_display();
	$self->close_added_windows();	
	$pwin->find_children_n_windows("collect to database");
	$self->proc_win_record();
	my $rto_str = $self->collect_generate_files();
	open(CAPLOG , "> $self->{output_deck}/capture_record.log");
	print CAPLOG Data::Dumper->Dump([$self->{cap_rec}]);
	close(CAPLOG);
##	$self->upload_db_result();

	$loop_notify->notify($self->{notify_tag});

#	system("sleep 10123");
	return $rto_str;
}
sub collect_generate_files
{
	my $self = shift;

	my @files_in_folder = glob( $self->{case_path} . "/*");
	my @outcome_files = ();
	for (@files_in_folder) {
		if (stat($_)->mtime > $self->{f_mtime}) {
			push @outcome_files , $_;
			system("mv $_ $self->{output_deck}");
		}
	}
	system("cp $self->{deckfull} $self->{output_deck}");
	$self->{outcome} = \@outcome_files;
	my $logf = $self->{case_log};
	my $rto_str = "0 usertime 0 systime 0 elapsed 0%CPU";
	if (-f $logf) {
		$rto_str = parse_rto($logf);
		system("mv $logf $self->{output_deck}");
	}
	return $rto_str;
}
sub upload_db_result
{
	my $self = shift;
	my $ptree = $self->{ptree};
	my $test_id = -1;
	for my $pid (keys %$ptree) {
		my $short_path = $self->{case_path};
		$short_path =~ s/$class_var{TestsPath}//;
		$short_path =~ s/^\///;
		my $res = logdb2('test_1_deck',
				$class_var{run_id},
				$short_path,
				$self->{deck},
				$ptree->{$pid});

		if ($test_id == -1) {
			$test_id = $res;
		}
	}
	logdb3('test_1_error', $test_id, 'GUI', $self->{WER}) if ($self->{WER});

	my $all_win_href = $self->{all_windows_href};
	for my $w1 (sort keys %$all_win_href) {
		logdb4('test_1_outcome', $test_id, 'GUI', $w1) if ($w1);
	}
	my $file_aref = $self->{outcome};
	for my $ff1 (@$file_aref) {
		my $f1 = basename($ff1);
		next if ( ($f1 =~ /\.done$/) || ($f1 eq "ptree.log") );
		logdb4('test_1_outcome', $test_id, 'file', $f1) if ($f1);
	}
}
sub parse_rto
{
	my $fname = shift;
	open DATA, "<"  , $fname;

	my $rto = "";
	while (<DATA>) {
		$rto .= $_;
	}
	close DATA;

	my ($user_time , $sys_time, $elapse_time, $cpu_util) = (0,0,"0",0);

	my $outtext = $rto;
    if ($outtext =~ /([\d\.]+)u ([\d\.]+)s ([\d\.:]+) ([\d\.]+)%/) {
        $user_time = $1;
        $sys_time = $2;
        $elapse_time = $3;
        $cpu_util = $4;
    }

    return "$user_time usertime $sys_time systime $elapse_time elapsed $cpu_util%CPU";
}
sub close_added_windows
{
	my $self = shift;
	my @before = @{$self->{windows_before}};
	my @after = @{$self->{windows_after}};

	for my $a1 (@after) {
		my $match = 0;
		for my $b1 (@before) {
			if (($a1->{pid} == $b1->{pid}) && ($a1->{wid} == $b1->{wid})) {
				$match = 1;
				last;
			}
		}
		if ($match == 0) {
			close_window($a1);
		}
	}
}
sub close_window
{
	my $winfo_href = shift;

	my $closed = 0;
	if (($winfo_href->{wname} eq "Tonyplot") || ($winfo_href->{wname} eq "TonyPlot3D")) {
		my $result = `xprop -pid $winfo_href->{pid}`;
		print $result;
		ProcWin::win_message($winfo_href->{wid} , "%({F4})");
		sleep(1);
		if (!ProcWin::wait_window_gone($winfo_href->{wid})) {
			$stop_notify->notify("kill $winfo_href->{pid} for tonyplot Exit but not Gone",1,1);
			system("kill -9 $winfo_href->{pid}");
		}
		$closed = 1;
	} elsif ($winfo_href->{wname} eq "SFLM Login Failed"){
		sleep(1);
		ProcWin::win_message($winfo_href->{wid} , "{TAB}{pause 500}{ENT}");
		if (!ProcWin::wait_window_gone($winfo_href->{wid})) {
			$stop_notify->notify("kill $winfo_href->{pid} for SFLM Login Failed",1,1);
			system("kill -9 $winfo_href->{pid}");
		}
		$closed = 1;
	}
	return $closed;
}
sub now
{
	my $self = shift;
	if ($self->{status} eq 'running') {
		return "$class_var{target} $self->{deckfull} -outfile $self->{logf}";
	}
}
sub back_n_decor
{
	my $self = shift;
	my $orig = $self->{work_case};
	my $back = $orig . ".bak";
	my $quit = 0;
	my $touched = 0;
	my $lastline = "";
	my @all_lines = ();
	my @b_lines = ();
	
	open(DECK , "< $orig");
	
	while (<DECK>) {
		push @all_lines , $_;
	}
	close(DECK);

	for (my $i = 0; $i <= $#all_lines ; $i++) {
		my $chomped = $all_lines[$i];
		chomp($chomped);
		if ($chomped eq "system touch $self->{done}") {
			next if ($touched>0);
			$touched++;
		}
		if ($all_lines[$i] =~ /^quit/) {
			if ($touched == 0) {
				push @b_lines , "system touch $self->{done}\n";
				$touched++;
			}
			$quit++
		}
		push @b_lines , $all_lines[$i];
	}
	if ($touched == 0) {
		push @b_lines , "system touch $self->{done}\n";
	}
	if ($quit == 0) {
		push @b_lines , "quit\n";
	}
	system("mv $orig $back");
	print ">>> mv $orig $back\n";				
		

	open(MDECK , "> $orig");
	binmode MDECK;
	print MDECK for (@b_lines);
	close(MDECK);
}
sub pid
{
	my $self = shift;
	return $self->{pid};
}

sub debug
{
	my $self = shift;
	for my $key (keys %$self) {
		print "$key --> [$self->{$key}]\n";
	}

	print "class variable:\n";
	for my $key (keys %class_var) {
		print "$key : [$class_var{$key}]\n";
	}
}

sub wait_n_check
{
	my $self = shift;
	my $result = "STILL";
	my $done = 0;
	my $wait_st;
	

## check if error windows
	$pwin->find_children_n_windows();
	$self->proc_win_record();
	if ( $pwin->is_children_sleep()) {
	        ProcWin::win_message($self->{dbWin} , '{pause 500}{F12}');
	        logx("KillSimulator");
	        $self->{status} = "killed";
	}
	if ($self->{WER} eq "SFLM Login Failed") {
		warn "SFLM Error";
		ProcWin::win_message($self->{WER_wid} , "{TAB}{pause 500}{ENT}");
		system("sleep 300");
		return "SFLM";
	}
	if ($self->{WER}) {
		if ($self->{WER_pid} == $self->{pid}) {
			warn "loop check WINERR";
			return "WINERR";
		}
	}
	if ($self->{DIALOG}) {
print "DDDDDDDDDDDIAAAAAAAAAAAAAAAAAAAALLLLLLLLLLLLLLLLLOOOOOOOOG\n";
		##win_message($self->{DIALOG} , "{ENT}");
		if (!ProcWin::wait_window_gone($self->{DIALOG})) {
			warn "dialog cannot be closed by entered";
			$stop_notify->notify("dialog cannot be closed by enter, sleep 9945699",1,1);
			system("sleep 9945699");
		} else {
			$self->{DIALOG} = 0;
		}
		return "DIALOG";
	}

	if (((-f $self->{done}) || ($self->{status} eq "killed")) &&
		($self->{nowindow} == 0)) {
		$done = 1;
	}
	$self->{waitpid} = waitpid($self->{pid} , WNOHANG);
	$wait_st = $?;
	print "finished [$self->{waitpid}] and done [$done]\n";


	if (($self->{waitpid} >0) || $done) {
		return "DONE";
	}


## check log file increase or not, $self->{case_log}
	my $log_size = 0;
	$log_size = -s "$self->{case_log}" if (-e "$self->{case_log}");
	if (length $log_size > 0) {
	    if ($log_size eq $self->{logsize}) {  ## No change on log filesize since last sleep
			$self->{wait_count} += $class_var{one_sleep};
			print "count ======== $self->{wait_count}\n";
			if ($self->{wait_count} > $class_var{max_sleep}) {
				print "loop check LENGTHY\n";
				return "LENGTHY";
			}
	    } else {
			$self->{logsize} = $log_size;
			$self->{wait_count} = 0;
	    }
	    chomp($log_size);
	    print "$self->{logf} status $log_size\n";
	}


	return $result;
}
sub find_children_old_20170805
{
	my $self = shift;
	my $pid_check;
	
	my @child = ($self->{pid});
	my $i;
	my $t = new Proc::ProcessTable;
	my %t_hash = ();
	for my $p ( @{$t->table}) {
		$t_hash{$p->{pid}} = $p;
	}
	
	for ($i = 0 ; $i <= $#child ; $i++) {
		my $ppid = $child[$i]; ## as a parent pid
		my $pname = $t_hash{$ppid}->{'cmndline'};
		$pid_check .= "$ppid - [$pname]\n";

print ">"x10, "children of $ppid\n";
		for my $cid (keys %t_hash) {
			if ($t_hash{$cid}->{ppid} == $ppid) {
print "$cid	$t_hash{$cid}->{cmndline}\n";
				push(@child , $cid);
			}
		}
print ">"x80 , "\n";
	}
	my $debug_ps = `ps -ef | grep -i tony`;
	print "\n" , "+"x30 , " debug tony ", "+"x30, "\n",$debug_ps,"\n" , "+"x80 , "\n";

	logx("find_children\n" . $pid_check);
	$self->{child} = \@child;
}



# refer logdb2 of RegUtil.pm that extract data from process table structure
# will insert into database


sub capture
{
	my $self = shift;
	my $win = shift;
	my $wid = $win->{wid};
	my $appendix = $win->{wname};
	my $wid_name = "x" . $wid . ".xwd";

	if ((index($appendix, '/') >= 0) or
		(index($appendix, '(') >= 0)) {
		$appendix = $wid;
	} else {
		$appendix =~ s/ /_/g;
	}

	if ($wid == 0) {
		warn "cannot capture window id 0";
		# to get root window
		my $winfo = ProcWin::getinfo_from_wid($wid);
		$wid = $winfo->{wid};
	#	return;
	}
#	print "capture for $wid\n";
	
	my $cap_name = $self->{work_case_h} . $self->{capture} . "__" . $appendix . ".png";
#print "xraise [$wid] then import -w [$wid] [$cap_name]\n";
	$self->{capture}++;
#	system("xraise $wid");
#	sleep(1);
#	warn "import for [$wid]";
#	print "import -frame -w $wid $cap_name" , "\n";
#	system("import -frame -w $wid $cap_name");
	system("xraise $wid;sleep 1");
	return if ($? != 0);
#	print "xwd -frame -id $wid -out $wid_name" , "\n";
	system("xwd -frame -id $wid -out $wid_name");
	return if ($? != 0);
#	print "convert $wid_name $cap_name", "\n";
	system("convert $wid_name $cap_name");
	system("rm $wid_name") if ($? == 0);
	my $now_string = localtime;
	push @{$self->{cap_rec}} , [$now_string, $wid, $cap_name, $win->{wname}];
	
	sleep(1);
	return $cap_name;
#print "leave capture\n";
}


sub clear_other_windows
{
	my $self = shift;
	my @windows = @{$self->{windows}};
	my $action = 0;
	for my $w1 (@windows) {
		if ($w1->{wid} == $self->{dbWin}) {next;}
		if ($w1->{pid} == $self->{pid}) {
			#press ESC
			print "TAB windows $w1->{pid} $w1->{wid} $w1->{wname}\n";
			ProcWin::win_message($w1->{wid} , "{TAB}{pause 500}{ENT}");

			$action++;
		} else {
			#tonyplot
			print "close windows $w1->{pid} $w1->{wid} $w1->{wname}\n";
			ProcWin::win_message($w1->{wid} , "%({F4})");
			$action++;
		}
		sleep(3);
	}
	#sleep(1000) if ($action);
}



## timetag print
sub ttprt
{
	my $str = shift;
	my $now_string = localtime;
	print "$now_string : $str";
}
sub suspend
{
	my $printed_already=0;
	while ( -f "$class_var{output_dir}/suspended.tag" ) {
		if ($printed_already) {
			print ".";
		} else {
			system("date");
			print "suspended file exists, sleep for 60 seconds for every dot\n";
			print "$class_var{output_dir}/suspended.tag" , "\n";
			$printed_already = 1;
		}
		sleep(60);
	}
}
sub classify_window
{
    my $self = shift;
    my $w_href = shift;
    my $wid = $w_href->{wid};
    print "[", (caller(0))[3], "] and at line ",__LINE__, "\n";

	if ((index($w_href->{_NET_WM_STATE} , "MODAL") <0) and
		(index($w_href->{_NET_WM_WINDOW_TYPE} , "DIALOG") <0)) {
		if (index($w_href->{_NET_WM_WINDOW_TYPE} , "SPLASH") >=0) {
			return "SPLASH";
		} else {
			return "$w_href->{wname}";
		}
	}
    my $res = ProcWin::getinfo_from_wid($wid);

    my $double_size = $res->{width}*2 . 'x' . $res->{height}*2;
    my $png_fname = $self->capture($w_href);
    return png2txt($png_fname , $double_size,
        $self->{capture_folder}, $self->{deck});
#	my $cap_name = $self->{work_case_h} . $self->{capture} . ".png";
}

sub check_windows
{
	my $self = shift;
	my $wanted = shift || "";
	my @windows = @{$pwin->{windows}};
	my $ret = {
		MODAL	=> 0,
		DIALOG	=> [],
		ditmp	=> [],
		others	=> [],
		stop	=> 0,
		WER		=> [],
		wanted	=> 0
		};
    print "[", (caller(0))[3], "] and at line ",__LINE__, "\n";

	my $marked = 0;
	for my $w1 (@windows) {
#print Data::Dumper->Dump([$w1]);
		$marked = 0;
		next if (($w1->{wid} == $self->{dbWin}) ||
				 ($w1->{wid} == 0) );

		if (index($w1->{_NET_WM_STATE} , "MODAL") >=0) {
    print "[", (caller(0))[3], "] and at line ",__LINE__, "\n";
			$ret->{MODAL} = $w1;
			$marked = 1;
		}
		if (index($w1->{_NET_WM_WINDOW_TYPE} , "_NET_WM_WINDOW_TYPE_DIALOG" ) >= 0) {
    print "[", (caller(0))[3], "] and at line ",__LINE__, "\n";
			push @{$ret->{DIALOG}} , $w1;
			$marked = 1;
		}
		if ((length($wanted) > 0) and ($w1->{wname} eq $wanted)) {
    print "[", (caller(0))[3], "] and at line ",__LINE__, "\n";
			$ret->{wanted} = $w1;
			$marked = 1;
			last;
		}
		if (($w1->{wname} eq "Deck Error") or
			($w1->{wname} eq "Tonyplot Error") or
			($w1->{wname} eq "Can Not Read File") or
			($w1->{wname} eq "Internal Error")) {
			push @{$ret->{WER}} , $w1;
			$marked = 1;
			next;
		}

		if ($w1->{wname} =~ /^DeckBuild .+ ditmp/){
    print "[", (caller(0))[3], "] and at line ",__LINE__, "\n";
			push @{$ret->{ditmp}} , $w1;
			$marked = 1;
		}
		my $stop_win = $self->classify_window($w1);
		print ">>>>>>>>>>>>>>>>$stop_win<<<<<<<<<<<<<<\n";
		my $stop_str = "EXITABNORMAL,RTOLOG,DECKCONV,DECKCHANGE,OVERWRITE";
		if (index($stop_str ,$stop_win) >=0) {
			$ret->{stop} = $w1;
			$marked = 1;
		} elsif ($stop_win eq "UNKNOWN") {
			print "\n\n";
			print Data::Dumper->Dump([$w1]);
			print "\n\n";
#			die;
		}
		if (!$marked) {
			push @{$ret->{others}} , $w1;
		}
	}
	return $ret;
}
my %CLASSIFY_WINDOWS = (
	"EXITABNORMAL" => 
		"The tool exited abnormally: tonyplot exiting.",
	"RTOLOG" => 
		"h.dden folder",
	"DECKCONV" =>
		"convert them",
	"DECKCHANGE" =>
		"has changed",
	"OVERWRITE" =>
		"already existing"
	);
sub png2txt
{
	my $png = shift;
	my $dbl_size = shift;
	my $convert_dir = shift;
	my $tag = shift;
	
	my $tick = time();
	my $tmp_name = $convert_dir . "/resize_". $tag . $tick.".png";
	my $t2_name = $convert_dir . "/" . "ocr_".$tag . $tick;
	my $t3_name = $t2_name.".txt";
	my $result = "UNKNOWN";

    print "[", (caller(0))[3], "] and at line ",__LINE__, "\n";
	if (-f $png) {
		system("convert $png -resize $dbl_size $tmp_name");
		#system("mv $png $dbl_size $tmp_name");
		system("tesseract $tmp_name $t2_name");
		my $ocr = `cat $t3_name`;
		for my $class1 (keys %CLASSIFY_WINDOWS) {
			my $mat = $CLASSIFY_WINDOWS{$class1};
			if ($ocr =~ /$mat/m) {
				$result = $class1;
				last;
			}
		}
	}
	return $result;
}

#aaaa
sub proc_win_record
{
	my $self = shift;
#	my $ptree_h = $self->{ntree};

	open(PTREELOG , ">> $self->{work_path}/ptree.log");
	print PTREELOG $pwin->print_ptable();
	close(PTREELOG);


#	if ( $pwin->is_children_sleep()) {
#	        win_message($self->{dbWin} , '{pause 500}{F12}');
#	        logx("KillSimulator");
#	        $self->{status} = "killed";
#	}
#	if (! $ptree_h) {
#		warn "find children failed";
#		system("sleep 999999");
#		$stop_notify->notify("find children failed, sleep 9999999",1,1);
#		return;
#	}
}
sub dd
{
	my $routine_name = (caller(0))[3];
    print "[$routine_name] and at line ", __LINE__, "\n";
}
sub prepare_to_run
{
	my $result_aref = shift;
	my $runmode = shift;

	my @cases_torun = ();
	my @cases_ok = ();

	if ($runmode eq "resume") {
		my $casetorun_aref = parse_casetorun("$class_var{output_dir}/casetorun.txt");
		my $result_pre = parse_result("$class_var{output_dir}/result.log");

		print "number of case to run : " , scalar @$casetorun_aref , "\n";
		print "number of result      : " , scalar @$result_pre , "\n";

		my $i;
		for ($i = 0 ; $i < scalar @$result_pre ; $i++) {
			my $case_tag2 = $result_pre->[$i]->[1];
			my $case = $casetorun_aref->[$i];
			my $case_base = basename($case);
			my $case_cat = basename(dirname($case));
			my $case_tag = "$case_cat/$case_base";
			if ($case_tag2 ne $case_tag) {
				die "resume run got mismatch $case_tag2 from $case in $class_var{output_dir}";
			}
		}
		while ( $i < scalar @$casetorun_aref) {
			push @cases_ok , $casetorun_aref->[$i];
			$i++
		}
		return \@cases_ok;
	}

	my @sort_run = sort { $a->[5] <=> $b->[5] or $a->[1] cmp $b->[1] } 
						grep { $_->[0] eq "DONE" } @$result_aref;
	my @killed_run = grep {$_->[0] eq "KILL"} @$result_aref;

print "result_aref number: " , scalar @$result_aref , "\n";
print "killed_run: " , scalar @killed_run , "\n";


	if ($runmode eq 'common') {
		push @cases_torun , @$_decks_aref;
	} elsif ($runmode eq 'sort') {
		for my $case_t (@sort_run) {
			my $case_tag2 = $case_t->[1];

			for my $case (@$_decks_aref) {
				my $case_base = basename($case);
				my $case_cat = basename(dirname($case));
				my $case_tag = "$case_cat/$case_base";
				if ($case_tag2 eq $case_tag) {
					push @cases_ok , $case;
					last;
				}
			}
		}
		@cases_torun = uniq(@cases_ok);
	} elsif ($runmode eq 'killed') {
		for my $case_t (@killed_run) {
			my $case_tag2 = $case_t->[1];

			for my $case (@$_decks_aref) {
				my $case_base = basename($case);
				my $case_cat = basename(dirname($case));
				my $case_tag = "$case_cat/$case_base";
				if ($case_tag2 eq $case_tag) {
					push @cases_ok , $case;
					last;
				}
			}
		}
		@cases_torun = uniq(@cases_ok);
	} else {
		die "runmode should be one of common or killed or sort";
	}

	open my $ctr , ">$class_var{output_dir}/casetorun.txt" or die $!;
	print $ctr  $_,"\n" for (@cases_torun);
	close $ctr;

	return \@cases_torun;
}
sub run
{
	shift;
	#my $result_name = shift;
	my $result_aref = shift;
	my $runmode = shift;
	my $runcount = shift || 999999;

## runmode : killed, sort, common
	my @cases_torun = @{prepare_to_run($result_aref , $runmode)};

	my $result_log2 = $class_var{output_dir} . "/result.log";
	my $runcount_now = 0;

	for my $case (@cases_torun) {
# $case >> /home/arnold/reg/tests/last/case0/4.2.12.R/bjt/bjtex11/bjtex11.in
		my $case_base = basename($case);
		my $case_cat = basename(dirname($case));
		my $case_tag = "$case_cat/$case_base";
# $case_tag >> bjtex11/bjtex11.in
		
		print "---->{deck} :\n$case\n";
		print "---->case_base :\n$case_base\n";
		print "ready to open next case";
#	print ">>>>    $run_ref{$case_tag}\n";
#	next if ($run_ref{$case_tag} > 700);
	

		my $deck = new DeckInfo($case) || next;
		
		my $start_time = strftime "%a %b %e %H:%M:%S %Z %Y", localtime;
		my ($platform, $hostname, $arch , $num_cpu, $CPU_MHz, $memTotal , $swapTotal ) =
			g_machine();
		sleep(1);
		logx("before " . $case);
		my $proc_status = -1;
		$deck->open_n_run2();
		if ($pwin->{wid}) {
			my $monitor_pid = fork();
			if ($monitor_pid ==0) {
	## This is monitor perl
				monitor($deck->{running_deck_dir} , $pwin->{pid});
			}
			$proc_status = $deck->waitloop();
		}
		my $log_prod;
		my $log_ver;
		my ($user_t , $system_t , $elapsed_t, $CPU_u ) = (0,0,0,0);

print "\n\n             END OF a CASE, waiting ....\n";


		#my $performance_str = $deck->finish();
		#my $windows_str = "WER: $deck->{WER}";
		#my $all_windows = "allWIN:" . $deck->all_windows_str();

		my $end_time = strftime "%a %b %e %H:%M:%S %Z %Y", localtime;
		if ($runcount_now ==0) {
			system("echo '# $start_time runmode $runmode' >> $result_log2");
			system("echo '# hostname=$hostname, $arch , num_cpu=$num_cpu, CPU_MHz=$CPU_MHz, memTotal=$memTotal , swapTotal=$swapTotal' >> $result_log2");
		}
		system("echo '$deck->{result}, $case_tag, $start_time, $end_time, $proc_status' >> $result_log2");
		
		system("date");
		$deck->collect_generate_files();
		
		sleep(3);
		$runcount_now++;
		last if ($runcount_now >= $runcount);
	}
	
	system("echo 'END of $runcount_now decks' >> $result_log2");
	DeckInfo->complete();
}

sub parse_casetorun
{
	my $fn = shift;
	my @casetorun = ();
	if (! -f $fn) {
		return \@casetorun;
	}

	open CASEF , "< $fn";
	while (<CASEF>) {
		chomp;
		push @casetorun , $_;
	}
	close CASEF;
	return \@casetorun;
}
# $allrun_aref = [
#          {
#            'Tue Mar  1 15:58:24 CST 2022' => [
#                  [
#                    'DONE',
#                    'quantumex02/quantumex02.in',
#                    '22:07:36',
#                    '22:08:16'
#                  ],
#                  [
#                   'DONE',
#                   'quantumex10/quantumex10.in',
#                   '22:08:19',
#                   '22:08:49'
#                  ], ....

# $run_aref = [
#          [
#            'DONE',
#            'quantumex02/quantumex02.in',
#            '22:07:36',
#            '22:08:16'
#          ],
#          [
#            'DONE',
#            'quantumex10/quantumex10.in',
#            '22:08:19',
#            '22:08:49'
#          ], ....
sub parse_result
{
	my $result_f = shift;

	open my $fh , "< $result_f" or die "Cannot open $result_f $?";
	my $run1_aref = [];
	my $allrun_aref = [];
	my $run_aref = [];
	my $current = "";
print "parse result log [$result_f]\n";
	while (<$fh>) {
		chomp $_;
		my @s = split /,\s*/ , $_;
		if ($#s >=3 ) {
			$s[4] = 1 if ($#s == 3);
			push @$run1_aref , \@s;
			push @$run_aref , \@s;
		} elsif (my $d_str = str2time($_)) {
			if (scalar @$run1_aref) {
				if (length $current ==0) {
					$current = `date`;
					chomp $current;
				}
				my $run1_href = {};
				$run1_href->{$current} = $run1_aref;
				$run1_aref = [];
				push @$allrun_aref , $run1_href;
			}
			$current = $_;
		}
	}
	if (scalar @$run1_aref) {
				if (length $current == 0) {
					$current = `date`;
					chomp $current;
				}
				my $run1_href = {};
				$run1_href->{$current} = $run1_aref;
				$run1_aref = [];
				push @$allrun_aref , $run1_href;
	}
			
	close $fh;

## For all deck run, calculate the elapse time in seconds from starting to ending
# and put in [5] of array element
	for my $run1_aref (@$run_aref) {
		$run1_aref->[5] = 0;
		my $start_t = str2time($run1_aref->[2]);
		my $end_t = str2time($run1_aref->[3]);
		if ($start_t && $end_t) {
			$run1_aref->[5] = $end_t - $start_t;
			$run1_aref->[5] += 24*60*60 if ($run1_aref->[5] < 0);
		}
	}

	#print Data::Dumper->Dump($allrun_aref);
	return $run_aref;
}
sub monitor
{
	my $output_dir = shift;
	my $ppid = shift;
	exec "monitor.pl $output_dir $ppid";
}
sub uniq {
  my %seen;
  return grep { !$seen{$_}++ } @_;
}
1;
