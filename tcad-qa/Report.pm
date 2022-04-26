package Report;
use strict;
use warnings;

use DeckInfo;
use File::Basename;
use POSIX;
use Date::Parse;
use Scalar::Util qw(looks_like_number);
sub parse_deck
{
	my $dir = shift;
	return {} if (! -d $dir);
	my $deck_tag = basename($dir);

	my @capture_info = ();
	my @info = glob "$dir/capture_*.info";
	for my $info1 (@info) {
		my $icat = `cat $info1`;
		my $VAR1;
		eval $icat;
		push @capture_info , $VAR1;
	}

	my $deck_capture = {
		$deck_tag , []
	};
	push @{$deck_capture->{$deck_tag}}, $_->{_NET_WM_NAME} for (@capture_info);

	my @png = glob "$dir/capture_*.png";
	push @{$deck_capture->{$deck_tag}}, $_ for (@png);
	return $deck_capture;
	
}
sub parse_running
{
	my $running = shift;
	my $running_href = {};
	my @capture = glob "$running/*";
	
	$running_href->{dir} = \@capture;
	for my $cap1 (@capture) {
	}

	
	my $rpt_aref = [];
	for (@capture) {
		my $rpt_deck_href = parse_deck($_);
		push @$rpt_aref , $rpt_deck_href if (%$rpt_deck_href);
	}
	$running_href->{rpt} = $rpt_aref;

	my @monitor = glob "$running/*/monitor.log";
	my %cpuu;
	for my $mon1 (@monitor) {
		my $tag = substr($mon1 , length($running) + 1 , -1 * length("monitor.log")-1);
print "tag $tag\n";
		$cpuu{$tag} = analyze_monitor(parse_monitor($mon1));
	}
	open FN , ">cpuu.log";
	print FN Data::Dumper->Dump([\%cpuu]);
	close FN;
	return $running_href;
}
sub parse_running_str
{
	my $running = shift;
	my $str = "";
	my @capture = glob "$running/*";
	
	for my $cap1 (@capture) {
	}

	$str .= Data::Dumper->Dump(\@capture);
	$str =~ s/\n/<br>\n/g;
	
	my $rpt_aref = [];
	for (@capture) {
		my $rpt_deck_href = parse_deck($_);
		push @$rpt_aref , $rpt_deck_href if (%$rpt_deck_href);
	}

	my %all_kind_wm_name = ();
	for my $tag1 (@$rpt_aref) {
		for my $k (keys %$tag1) {
			$str .= $k . "==><br>\n";
			my %wm_name = ();
			for my $wm_name (@{$tag1->{$k}}) {
				if (defined $wm_name{$wm_name}) {
					$wm_name{$wm_name}++;
				} else {
					$wm_name{$wm_name} = 1;
				}
				my @seg_wm = split /\s+/ , $wm_name;
				my ($i , $short_wm) = (1, $seg_wm[0]);
				$short_wm .= " " . $seg_wm[$i++] while ($seg_wm[$i] and $i<4);
				if (defined $all_kind_wm_name{$short_wm}) {
					$all_kind_wm_name{$short_wm}++;
				} else {
					$all_kind_wm_name{$short_wm} = 1;
				}
			}
			for my $k_wm_name (keys %wm_name) {
				$str .= "&nbsp;" . $wm_name{$k_wm_name} . " for " . $k_wm_name . "<br>\n";
			}
		}
	}
	for my $k_wm_name (keys %all_kind_wm_name) {
				$str .= "&nbsp;" . $all_kind_wm_name{$k_wm_name} . " for " . $k_wm_name . "<br>\n";
			}
	return $str;
}
sub convert2jsvar
{
	my $all_res = shift;
	my $str = "";

	if (ref $all_res eq "HASH") {
		$str .= "{";
		for my $k (keys %$all_res) {
			my $kk = $k;
			if (not looks_like_number($k)) {
				$kk = "\"$k\"";
			}
			$str .= $kk . ":" . convert2jsvar($all_res->{$k}) . ",\n";
		}
		$str .= "}\n";
	} elsif (ref $all_res eq "ARRAY") {
		$str .= "[";
		for my $v (@$all_res) {
			$str .= convert2jsvar($v) . ",";
		}
		$str .= "]\n";
	} elsif (ref $all_res eq "") {
		if (looks_like_number($all_res)) {
				$str .= $all_res;
		} else {
				$str .= "\"$all_res\"";
		}
	}
	return $str;
}
sub convert2jsvar_result
{
	my $result_aref = shift;

	my $header = ["Result", "Deck tag", "From" , "To" , "status" , "Duration"];
	unshift @$result_aref , $header;
	my $str = "var result = [";
	my $first_row = 1;
	for my $run1_aref (@$result_aref) {
		if ($first_row == 0) {
				$str .= ",\n";
			} else {
				$str .= "\n";
				$first_row = 0;
			}
		$str .= "    [";
		my $first = 1;
		for my $g (@$run1_aref) {
			if ($first == 0) {
				$str .= ",";
			} else {
				$first = 0;
			}
			if (looks_like_number($g)) {
				$str .= $g;
			} else {
				$str .= "\"$g\"";
			}
		}
		
		$str .= "]";
	}

	$str .= "\n];\n";

	return $str;
}
sub output_allresult
{
	my $all_data = shift;
	my $opath = shift;
	my $str_jsvar = "var result=" . convert2jsvar($all_data) . ";\n";
	my $result_data_fh;
	open $result_data_fh , "> $opath/data.js" or die $!;
	print $result_data_fh $str_jsvar;
	close $result_data_fh;
}
sub review_report
{
	my $result_fn = shift;
	my $opath = dirname($result_fn);
	my $result_aref = DeckInfo::parse_result($result_fn);
	my $casetorun = DeckInfo::parse_casetorun("$opath/casetorun.txt");
	

	my $header = ["Result", "Deck tag", "From" , "To" , "status" , "Duration"];
	unshift @$result_aref , $header;
	my $all_data = {
		result => $result_aref,
		casetorun => $casetorun,
		output_path => $opath,
		running => parse_running("$opath/running"),
	};
	output_allresult($all_data , $opath);
}


###############################################################################################
=pod
492634  deckbuild.exe
│       ctime [40000] time [1350000]
└─492847  csh -cf victorya
  │       ctime [0] time [0]
  └─492849  csh -f victorya
    │       ctime [40000] time [10000]
    └─492889  time victorya.exe
      │       ctime [0] time [0]
      └─492890  victorya.exe
                ctime [0] time [10000]


130252  deckbuild.exe
│       ctime [68010000] time [2500000]
└─151940  csh -cf victorymesh
  │       ctime [0] time [0]
  └─151942  csh -f victorymesh
    │       ctime [40000] time [0]
    └─151985  time victorymesh.exe
      │       ctime [0] time [0]
      └─151986  victorymesh.exe
        │       ctime [0] time [130000]
        ├─152011  vmio.exe
        │ │       ctime [0] time [0]
        │ └─152026  vmio_vpglue.exe
        │           ctime [0] time [3660000]
        └─151995  vmparse.exe
                  ctime [0] time [30000]
=cut
# parse_ptree
my $pptree_err;
my $pptree_line;
my $pptree_code;

sub parse_ptree
{
	my $pt_lines = shift;

#print "number of lines " , scalar @$pt_lines , "\n";

	my ($line0, $line1 , $ctime , $time) = ("x","y",0,0);
	my $start_pid;
	my $lev_space;
	my $pid;
	my $ppid;
	my $process_name;
	my $match_pos0;
	my @ptree = ();

	for (my $i = 0; $i < scalar @$pt_lines ; $i+=2) {
		$line0 = $pt_lines->[$i];

		if ($line0 =~ /^(\d+)  (\S*)$/)  { 
			$start_pid = $pid = $1;
			$process_name = $2;
			$match_pos0 = -1;
		} elsif ( $line0 =~ /─(\d+)  (.*)$/ ) {
#print "x",$line0, "\n";
			$pid = $1;
			$process_name = $2;			
			$match_pos0 = $-[0];
			my $pre = substr($line0 , 0 , $match_pos0);
			$pre =~ s/ //g;
#print "p, $match_pos0 , [$pre]\n";
			$match_pos0 -= length($pre)/3*2;
		} else {
			$pptree_err = "Should be child process";
			$pptree_line = $i;
			return 0;
		}
		$line1 = $pt_lines->[$i + 1];
#print "y",$line1, "\n";
		if ($line1 =~ /^((│|\s)+)\s+ctime \[(\d+)\] time \[(\d+)\]$/)  { 
			my $pass_bar = $1;
			$ctime = $3;
			$time = $4;
#print "y [$pass_bar] [$ctime] [$time]\n";
			if ((index($pass_bar, "│") <0) and
				($i != (scalar @$pt_lines) -2 )) {
				$pptree_err = "Should be last line but i is $i";
				$pptree_line = $i;
				return 0;
			}
		} else {
			$pptree_err = "Should be time info of $process_name";
			$pptree_line = $i;
			return 0;
		}
		$ppid = 0;
		for (my $j = $#ptree ; $j >= 0 ; $j--) {
			if ($ptree[$j]->{anchor} == $match_pos0 - 1) {
				$ppid = $ptree[$j]->{pid};
				last;
			}
		}
		if ($ppid == 0 and $pid ne $start_pid) {
			$pptree_err = "Error no ppid of $process_name";
			$pptree_line = $i;
			return 0;
		}
		my $pt_href = {
			anchor => $match_pos0 + 1,
			pid => $pid,
			ctime => $ctime,
			'time' => $time,
			pname => $process_name,
			ppid => $ppid
		};
		push @ptree , $pt_href;
	}

	return \@ptree;
}
sub parse_ptree0
{
	my $pt_lines = shift;

	print "number of lines " , scalar @$pt_lines , "\n";

	my ($line0, $line1 , $ctime , $time) = ("x","y",0,0);
	my $start_pid;
	my $lev_space;
	my $pid,
	my $process_name;

	$line0 = $pt_lines->[0];
	if ($line0 =~ /^(\d+)  (\S*)$/)  { 
		$start_pid = $1;
		my $deckbuild = $2;
		if ($deckbuild eq "") {
			$pptree_err = " deckbuild.exe is gone pre-mature";
			$pptree_line = 0;
			#return 0;
		}
	} else {
		$pptree_err = "Should be deckbuild.exe as leading line0 [$line0]";
		$pptree_line = 0;
		return 0;
	}
	$line1 = $pt_lines->[1];
	if ($line1 =~ /^((│)?)\s+ctime \[(\d+)\] time \[(\d+)\]$/)  { 
		$ctime = $3;
		$time = $4;
	} else {
		$pptree_err = "Should be time info of leading";
		$pptree_line = 1;
		return 0;
	}


	for (my $i = 2; $i < scalar @$pt_lines ; $i+=2) {
		$line0 = $pt_lines->[$i];
print "x",$line0, "\n";
		if ($line0 =~ /^((│)?)(\s*)(└─|├─)(\d+)\s\s(\S+.*)$/) {
			my $pass_bar = $1;
			$lev_space = $3;
			my $x2 = $4;
			$pid = $5;
			$process_name = $6;
print "x [$pass_bar] [$lev_space] [$x2] length " , length($x2) , " [$pid] [$process_name]\n";
		} else {
			$pptree_err = "Should be child process";
			$pptree_line = $i;
			return 0;
		}
		$line1 = $pt_lines->[$i + 1];
print "y",$line1, "\n";
		if ($line1 =~ /^((│)?)(\s*)((│)?)\s+ctime \[(\d+)\] time \[(\d+)\]$/)  { 
			my $pass_bar = $1;
			$lev_space = $3;
			my $x2 = $4;
			$ctime = $6;
			$time = $7;
print "y [$pass_bar] [$lev_space] [$x2] length " , length($x2) , " [$ctime] [$time]\n";
			if (($x2 eq "") and ($pass_bar eq "") and
				($i != (scalar @$pt_lines) -2 )) {
				$pptree_err = "Should be last line but i is $i";
				$pptree_line = $i;
				return 0;
			}
		} else {
			$pptree_err = "Should be time info of $process_name";
			$pptree_line = $i;
			return 0;
		}
	}
	return 1;

=pod			
						($line =~ /^\s*(│\s+)+└─\d+\s+/) or
						($line =~ /^\s*(│\s+)+├─\d+\s+/))  {
					if ($line =~ /^\d+, \d+, \[.+\]/)  {
=cut
}

#top - 01:15:07 up  7:16,  1 user,  load average: 10.42, 9.80, 5.52
# $top_time = 01:15:07
# $load_average_3 = "10.42, 9.80, 5.52"
# $current_top is ARRAY ref of list of top process, $current_top->[0] is header
# $current_top_href is the hash of above

sub parse_monitor
{
	my $r_fname = shift;
	open FN , "< $r_fname" or die "open r file $r_fname failed";

print "monitor $r_fname\n";
	my $r_data = [];
	my $monitor_on_pid;
	my $monitor_by_pid;
	my $line_count = 1;
	my $group_begin_main = "";
	my $group_begin = 0;
	my $enter_count = 0;
	my $single_dt;
	my $top_time;
	my $ptree_time;
	my $load_average_3;
	my $current_top;
	my $current_top_href;
	my @ptree_lines = ();
	my $current_ptree;
	while (<FN>) {
		#print "= = = = [$group_begin_main] [$group_begin] line [$line_count] \n";
	
		chomp;
		my $line = $_;
		if ($line =~ /^start from (\d+)/) {
			$monitor_on_pid = $1;
		} elsif ($line =~ /^monitor is (\d+)/) {
			$monitor_by_pid = $1;
		} elsif ($line =~ /^top - (\d\d:\d\d:\d\d).+load average: ([\.\d]+, [\.\d]+, [\.\d]+)$/) {
			$top_time = $1;
			$load_average_3 = $2;
			$group_begin_main = "top";
			$group_begin = 0;
		} elsif ($group_begin_main eq "top") {
			if ($line eq '') {
				$group_begin++;
				if ($group_begin ==3) {
					my %top = (
						top_time => $top_time,
						load_average => $load_average_3,
						top_list => $current_top
					);
					$current_top_href = \%top;
				}
				if ($group_begin == 4) {
					$group_begin_main = '';
				}
			} elsif ($group_begin == 1) {
				if ($line =~ /PID\s+USER\s+PR\s+NI\s+VIRT\s+RES\s+SHR\s+S\s+%CPU\s+%MEM\s+TIME\+\s+COMMAND?/) {
					$line =~ s/^\s+//;
					my @top_header = split /\s+/ , $line;
					my @top_begin = (\@top_header);
					$current_top = \@top_begin;
					$group_begin = 2;
				} else {
					die "expect PID USER ...COMMAND as top list header";
				}
			} elsif ($group_begin == 2) {
				$line =~ s/^\s+//;
				my @seg = split /\s+/ , $line;
				push @$current_top , \@seg;
			}
		} elsif (($line =~ /^\S{3}\s/) and (defined ($single_dt = str2time($line)))) {
#			print "single datetime [$single_dt]\n";
			$group_begin_main = 'ptree';
			$ptree_time = $line;
			$group_begin = 0;
		} elsif ($group_begin_main eq 'ptree') {
			if ($group_begin == 0) {
				if ($line eq '') {
					$group_begin++;
					$current_ptree = parse_ptree(\@ptree_lines);
					$current_top_href->{ptree} = $current_ptree;
					$current_top_href->{ptree_time} = $ptree_time;
					push @$r_data , $current_top_href;
					@ptree_lines = ();
					if ($current_ptree == 0) {
						my $msg_str = "at line " . $pptree_line . " $pptree_err for $r_fname" ;
						die $msg_str;
					}
				} else {
					push @ptree_lines , $line;
				}
			} elsif ( ($group_begin == 1) and
				 ($line eq '**') ) {
				$group_begin_main = '';
			} else {
				die "something wrong at line $line_count in $r_fname";
			}
		} else {

			warn "Unrecognized [$line] at line $line_count in $r_fname";
		}
		$line_count++;
	}

	close FN;

	return $r_data;
}

sub calculate_one_shot
{
	my $shot = shift;
	my $ps_aref = $shot->{ptree};
	my $top_aref = $shot->{top_list};
	my $top_time = $shot->{top_time};
	my $ps_time = $shot->{ptree_time};

	my ($ss,$mm,$hh,$day,$month,$year,$zone) = strptime($ps_time);
	if ($top_time =~ /^(\d\d):(\d\d):(\d\d)$/) {
		my ($thh , $tmm, $tss ) = ($1, $2, $3);

		if ($hh*3600+$mm*60+$ss - ($thh*3600+$tmm*60+$tss) > 1) {
			warn "Not match $ps_time and $top_time\n";
		}
	} else {
		die "Error on $top_time, expected hh:mm:ss";
	}

	my @pstree = ();
	for (@$ps_aref) {
		push @pstree , $_->{pid};
	}

	my $cpuu = 0;
	for (my $j = 1; $j < scalar @$top_aref ; $j++) {
		my $pid = $top_aref->[$j][0];
		my $cpu_pct = $top_aref->[$j][8];
		for my $pid1 (@pstree) {
			if ($pid == $pid1) {
				$cpuu += $cpu_pct;
				last;
			}
		}
	}
	return $cpuu;
	
}
sub analyze_monitor
{
	my $mon_data = shift;

	my @cpuu = ();
	for my $h1 (@$mon_data) {
		push @cpuu , calculate_one_shot($h1);
	}
	return \@cpuu;
}


1;
