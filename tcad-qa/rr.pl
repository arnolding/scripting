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


config("config.guir");
my $reg_home = $ENV{REG_HOME} || "/home/arnold/reg";
my $case_path0 = "/home/arnold/reg/tests/last/cases"; ##"/home/arnold/scripting/tests/case_do";

if (! -e $case_path0) {
	die "$case_path0 not exist!";
}
my $OutPath = "$reg_home/tests/last";
$OutPath = create_outpath("$reg_home/tests") if (! -e $OutPath);
my $log_name = "$OutPath/r.log";
my $quick_deck = "/build/arnoldh/tcad-qa/shorter_than_1000.txt";
my $keep_config = 0;
my $help ="";
my $review_result = 0;
my $runmode = 'NA';
my $case_path_in = "";
my $runcount = 999999;
my $result_name = "$OutPath/result.log";
GetOptions(
		'review' => \$review_result,
        'path=s' => \$case_path_in,
        'output=s' => \$OutPath,
        'log=s' => \$log_name,
		'keep'	=> \$keep_config,
		'runmode=s' => \$runmode,
		'count:i' => \$runcount,
	'help' => \$help
        );

if ($help || ( ($runmode eq 'NA') and ($review_result == 0))) {
	my $b = basename($0);
	print "$b --review \n    or\n";
	print "$b --runmode [killed|sort|common] --path <case-path> --output <work-path> --log <log-file> --keep\n\n";
	print "example:\n";
	print "$b --runmode --path $case_path0 --output $OutPath --log $log_name --keep $keep_config\n";
	
	exit;
}
my $result_aref = [];
if (-f $result_name) {
	$result_aref = parse_result($result_name);
}
if ($review_result) {
	print "review $result_name" , "\n";
	review_report($result_aref);
	exit;
}
my $case_path = get_case_path($case_path_in, $case_path0);
die "$case_path_in does not exist" if ($case_path eq "");
print "$case_path $OutPath $log_name\n";
logname($log_name);
DeckInfo->init($case_path, $OutPath, "deckbuild", "skip.txt", $keep_config);

my $Temp;

my $run_ref = "$reg_home/tcad-qa-old/log_out.txt";
# Get start time





my %run_ref = ();
if (-f $run_ref) {
open(REF , "<" , $run_ref);
while (<REF>) {
	chomp;
#	print $_ , "\n";
	if (/^(.+\.in),.*,\t(\d+)$/) {
	my $casename = $1;
	my $case_run = $2;
	$run_ref{$casename} = $case_run;
	} else {
		print $_ , "\n";
	}
}
close(REF);
}
	
print "=======================================\n";
for my $key (keys %run_ref) {
##	print "$key	$run_ref{$key}\n";
}

#my $s_pid = startpv($exe);
#my ($cmd_name , $cmd_ver , $cmd_platform , $cmd_exe) = get_pv($s_pid);
#logx("$cmd_name , $cmd_ver , $cmd_platform , $cmd_exe");
#end_win($s_pid);


my @decks = DeckInfo->decks($quick_deck);
for my $c1 (@decks) {
	#print "$c1\n";
}

#print Data::Dumper->Dump($DeckInfo::decks_aref);
print "Total " . (1 + $#decks) . " decks\n";
print "END OF cases listing\n\n";

DeckInfo->run($result_name, $result_aref, $runmode, $runcount);
my @dir_recursive;
sub get_case_path
{
	my $case_in = shift;
	my $case_default = shift;
	my @tt = ();

	if ($case_in eq "") {
		my @ex_versions =
				sort {
					$b->[0] <=> $a->[0] or $b->[1] <=> $a->[1] or
					$b->[2] <=> $a->[2] or $b->[3] cmp $a->[3] }
				grep {
					$_->[0] =~ /^\d+$/ and $_->[1] =~ /^\d+$/
					and $_->[2] =~ /^\d+$/ and $_->[3] =~ /[ABCR]/ }
				grep { scalar @$_ == 4 }
				map { [split /\./,$_ ]}
				map {substr($_ , length($case_default) + 1);} 
					glob "$case_default/*";
		my $latest_ver = join("." , @{$ex_versions[0]});
		return "$case_default/$latest_ver";
	}

	if ((-e $case_in) and (-d $case_in)) {
		return $case_in;
	}

	my @case_a = split /,/ , $case_in;
	my @case_d = ();
	@dir_recursive = ();
	find(\&find_directory , ($case_default));
	for my $d1 (@dir_recursive) {
		for my $c1 (@case_a) {
			if ((length($d1) > length($c1)) and 
				(substr($d1 ,length($d1) - length($c1)) eq $c1)) {
#			warn "[" . substr($d1 ,length($d1) - length($c1)) . "]";
				push @case_d , $d1;
			}
		}
	}
	if ($#case_d > 0) {
		return \@case_d;
	} elsif ($#case_d == 0) {
		return $case_d[0];
	} else {
		return "";
	}
}

sub find_directory
{
	my $item = $File::Find::name;
	if (-d $item) {
		push @dir_recursive , $item;
	}
}
	
sub create_outpath
{
    my $outpath_head = shift;
    my $OutPath;
  my ($date, $i);
  if (!(-e $outpath_head)) { File::Path::mkpath($outpath_head, 0, 0755) or die "\nError: Unable to create output path\n[$outpath_head]\n\n"; }
  $outpath_head = abs_path($outpath_head);
	my ($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime(time);
  $date = sprintf("%02d%02d", $mon+1, $mday);
  $OutPath = "$outpath_head/tcad-$date";
  for ($i = 0; ; $i++) { last unless (-d $OutPath . "-$i" ); }
  $OutPath .= "-$i";

  # Create outpath along with dir for temp files
  $Temp = "$OutPath/.temp";
  File::Path::mkpath("$Temp", 0, 0755) or die "\nError: Unable to create output path\n[$Temp]\n\n";

  my $recent = $outpath_head . "/last";
  if (-l $recent) {
		unlink "$recent" or 
		die "Failed to remove $recent $!\n";
  }
  system("ln -s $OutPath $recent");
    return $OutPath;
}

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
	my $result_name = shift;
	open my $fh , "<$result_name" or die "Cannot open $result_name $?";
	my $run1_aref = [];
	my $allrun_aref = [];
	my $run_aref = [];
	my $current = "";
	while (<$fh>) {
		chomp $_;
		my @s = split /,\s*/ , $_;
		if ($#s == 3) {
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
# and put in [4] of array element
	for my $run1_aref (@$run_aref) {
		$run1_aref->[4] = 0;
		my $start_t = str2time($run1_aref->[2]);
		my $end_t = str2time($run1_aref->[3]);
		if ($start_t && $end_t) {
			$run1_aref->[4] = $end_t - $start_t;
			$run1_aref->[4] += 24*60*60 if ($run1_aref->[4] < 0);
		}
	}

	#print Data::Dumper->Dump($allrun_aref);
	return $run_aref;
}

sub review_report
{
	my $result_aref = shift;
	my $killed = 0;
	my $done = 0;
	my $done_total_time = 0;
	my $killed_cases = {};
	my $done_cases = {};


## create the hash by case tag, and separate to killed and done hash
## $killed_cases and $done_cases
	for my $run1_aref (@$result_aref) {
		$killed_cases->{$run1_aref->[1]} ++ if ($run1_aref->[0] eq "KILL");

		if ($run1_aref->[0] eq "DONE") {
			$done_cases->{$run1_aref->[1]}++;
			$done++;
			$done_total_time += $run1_aref->[4];
		} elsif ($run1_aref->[0] eq "KILL") {
			$killed_cases->{$run1_aref->[1]}++;
			$killed++
		}
	}

## For the cases both killed but done, set it as done, so remove the killed entry.
	for my $kill_case ( keys %$killed_cases ) {
		if ($done_cases->{$kill_case}) {
			delete $killed_cases->{$kill_case};
		}
	}


## For the cases done, sort by elapse time
	my @sort_run_aref = sort { $a->[4] <=> $b->[4] or $a->[1] cmp $b->[1] } 
						grep { $_->[0] eq "DONE" } @$result_aref;

	print "$_->[4] : $_->[1] : $_->[2] : $_->[3]\n" for (@sort_run_aref); 
	print "Listed      : " , scalar @sort_run_aref , "\n";
	print "DONE number : " , scalar keys %$done_cases , "\n";
	print "KILLED      : " , scalar keys %$killed_cases , "\n";

	my $ss = $done_total_time % 60;
	my $mm = (int $done_total_time / 60) % 60;
	my $hh = (int $done_total_time / 3600);

	print "Total Time  : $hh:$mm:$ss\n";

	for my $done_case ( keys %$done_cases) {
		if ($done_cases->{$done_case} > 1) {
			print "DONE twice or more $done_case , with times $done_cases->{$done_case}" , "\n";
		}
	}
}
