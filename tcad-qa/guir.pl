#!/usr/bin/perl
use strict;
use warnings;
##use 5.010;

use IO::Socket;
use POSIX ":sys_wait_h";
use Cwd 'abs_path';
use File::Basename;
use File::Path;
use File::Find;
use ProductInfo;
#use ProductX11;
use RegUtil;
use DeckInfo;
use Getopt::Long;
use Data::Dumper;

config("config.guir");
my $reg_home = $ENV{REG_HOME} || "/home/arnold/reg";
my $OutputPath;
my $WorkPathLabel = "last";
my $ref_fn = "runmode_common_host_altos_result.log";
my $result_aref;

my $keep_config = 0;
my $help ="";
## The following 5 options, (report, resume, killed, sort, common)
## are mutual exclusive
my ($report, $resume, $killed , $sort , $common)  = ('tbd', 'tbd', 0, 0, 1);
my @opt_case_tags = ();
my $runcount = 999999;


sub help
{
	my $b = basename($0);
	print "$b --report \n    or\n";
	print "$b --[killed|sort|common|resume] --tag bjt,power --keep\n\n";
	print "example:\n";
	print "$b --[killed|sort|common|resume] --tag bjt,power -keep $keep_config\n";
}
my $get_opt = GetOptions(
		'report:s' => \$report,
        'tag=s@' => \@opt_case_tags,
		'keep'	=> \$keep_config,
		'common' => \$common,
		'sort' => \$sort,
		'kill' => \$killed,
		'resume:s' => \$resume,
		'count:i' => \$runcount,
		'help' => \$help
        );

if ($help or !$get_opt) {
	help();
	exit 0;
}

if ($report ne 'tbd') {
	$OutputPath = decide_outputpath("$reg_home/tests", $WorkPathLabel , 'recent');
	if (! -f "$OutputPath/result.log" ) {
		warn "result.log does not exist in $OutputPath/result.log";
		exit 1;
	}
	review_report("$OutputPath/result.log");
	exit 0;
}

my $runmode;
my $ref_aref = DeckInfo::parse_result(dirname($0) . "/" . $ref_fn);
if ($resume ne 'tbd') {
	$resume = 'recent' if ($resume eq '');
	$runmode = "resume";
} elsif ($sort) {
	$runmode = "sort";
} elsif ($killed) {
	$runmode = "killed";
} elsif ($common) {
	$runmode = "common";
} else {
	print "runmode [report|killed|sort|common|resume] is not specified\n";
	help();
	exit 1;
}

if ($runmode eq "resume") {
	$OutputPath = decide_outputpath("$reg_home/tests", $WorkPathLabel, $resume);
} else {
	$OutputPath = decide_outputpath("$reg_home/tests", $WorkPathLabel);
}
my $WorkPath = dirname($OutputPath);
my $CasePath = "$WorkPath/cases";
if (! -e $CasePath) {
	die "$CasePath not exist!";
}
my $log_name = "$WorkPath/r.log";

my @case_tags = ();
for (@opt_case_tags) {
	my @mul = split /,/ , $_;
	push @case_tags ,  @mul;
}

## $case_ver specify the version under $CasePath

my $case_ver = '';
my $case_path_to_ver = get_case_path('', $CasePath);

###
# $case_path_to_ver  >> /home/arnold/reg/tests/last/cases/4.2.12.R
# $WorkPath          >> /home/arnold/reg/tests/last
# $OutputPath        >> $WorkPath/output-mmdd-d
###
print "$case_path_to_ver $WorkPath $log_name\n";

logname($log_name);
DeckInfo->init($case_path_to_ver, $OutputPath, "deckbuild", $keep_config);

# Get start time


DeckInfo->decks(\@case_tags);

print "Total " . (1 + scalar @$DeckInfo::_decks_aref) . " decks\n";
print "END OF cases listing\n\n";

DeckInfo->run($ref_aref, $runmode, $runcount);
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
	
sub decide_outputpath
{
	my $wpath_head = shift;
	my $label = shift;
	my $output_pre = shift;
	my $WorkPath = "$wpath_head/$label";
	my ($date, $i);
	if (!(-e $wpath_head)) {
		File::Path::mkpath($wpath_head, 0, 0755) or 
			die "\nError: Unable to create work path head\n[$wpath_head]\n\n";
	}
	$wpath_head = abs_path($wpath_head);
	my ($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime(time);
	$date = sprintf("%02d%02d", $mon+1, $mday);

	if ( ! -e $WorkPath) {
		$WorkPath = "$wpath_head/tcad-$date";
		for ($i = 0; ; $i++) { last unless (-d $WorkPath . "-$i" ); }
		$WorkPath .= "-$i";

# Create work path
		File::Path::mkpath($WorkPath, 0, 0755) or 
			die "\nError: Unable to create work path\n[$WorkPath]\n\n";

		my $recent = $wpath_head . "/last";
		if (-l $recent) {
			unlink "$recent" or 
			die "Failed to remove $recent $!\n";
		}
		system("ln -s $WorkPath $recent");
	}

	my $OutputPath = "$WorkPath/output-$date";
	if (defined $output_pre) {
		if ($output_pre eq "recent") {
			my @outp = sort {$b cmp $a} grep { -d $_ } glob("$WorkPath/output-*");
			$OutputPath = $outp[0];
		} else {
			$OutputPath = "$WorkPath/$output_pre";
		}
	} else {
		for ($i = 0; ; $i++) { last unless (-d $OutputPath . "-$i" ); }
		$OutputPath .= "-$i";
		File::Path::mkpath($OutputPath, 0, 0755) or 
			die "\nError: Unable to create output path\n[$OutputPath]\n\n";
	}
    return $OutputPath;
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







sub review_report
{
	my $result_fn = shift;
	my $result_aref = DeckInfo::parse_result($result_fn);
	
	my $opath = dirname($result_fn);
	my $casetorun = DeckInfo::parse_casetorun("$opath/casetorun.txt");
	my $rpt_fh;
	open $rpt_fh , "> $opath/rpt.html" or die $!;

	
	my $killed = 0;
	my $done = 0;
	my $done_total_time = 0;
	my $killed_cases = {};
	my $done_cases = {};

	my $result_str = "";


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
	my @sort_run_aref = sort { $a->[5] <=> $b->[5] or $a->[1] cmp $b->[1] } @$result_aref;

	$result_str .= "casetorun   : " . (scalar @$casetorun) . "<br>\n";
	$result_str .= "Listed      : " . (scalar @sort_run_aref) . "<br>\n";
	$result_str .= "DONE number : " . (scalar keys %$done_cases) . "<br>\n";
	$result_str .= "KILLED      : " . (scalar keys %$killed_cases) . "<br>\n";

	my $ss = $done_total_time % 60;
	my $mm = (int $done_total_time / 60) % 60;
	my $hh = (int $done_total_time / 3600);

	$result_str .= "Total Time  : $hh:$mm:$ss<br>\n";
	$result_str .= "<hr>\n";
	$result_str .= "<table><tr>\n";
	$result_str .= "<th>elapse time</th>";
	$result_str .= "<th>exit status</th>";
	$result_str .= "<th>case tag</th>";
	$result_str .= "<th>start</th>";
	$result_str .= "<th>end</th>";
	$result_str .= "</tr>\n";
	for (@sort_run_aref) {
		$result_str .= "<tr><td>$_->[5]</td><td>$_->[0], $_->[4]</td>";
		$result_str .= "<td>$_->[1]</td><td>$_->[2]</td><td>$_->[3]</td></tr>\n";
	}
	$result_str .= "</table>"; 

	for my $done_case ( keys %$done_cases) {
		if ($done_cases->{$done_case} > 1) {
			$result_str .= "DONE twice or more $done_case , with times $done_cases->{$done_case}" . "<br>\n";
		}
	}
	my $html_head = <<END_OF_BLOCK;
<!DOCTYPE html>
<html>
<head>
<style>
table, th, td {
  border: 1px solid black;
  border-collapse: collapse;
}
</style>
</head>
<body>
END_OF_BLOCK
	print $rpt_fh $html_head;
	print $rpt_fh "<h3>Regression Report</h3>\n";
	print $rpt_fh "<p>$opath</p>\n";
	print $rpt_fh $result_str;
	print $rpt_fh "</body>\n</html>\n"; 
	close $rpt_fh;

	print "report file : $opath/rpt.html\n";
	return $result_str;
}


