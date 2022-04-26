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
use ProductInfo;
use ProductX11;
use RegUtil;
use tpDeckInfo;
use Getopt::Long;


my $reg_home = $ENV{REG_HOME} || "/home/qa";
#my $case_path = "/build/arnoldh/tcad2016/examples";
#my $case_path = "$reg_home/tests/case_master";
my $case_path = "$reg_home/tests/case_do";
my $OutPath = "$reg_home/tests/last/cases";
my $log_name = "$OutPath/r.log";
my $keep_config = 0;
my $help ="";
GetOptions(
        'output=s' => \$OutPath,
	'help' => \$help
        );

if ($help) {
	print "$0 --output <work-path> \n";
	print "example:\n";
	print "$0 --output $OutPath\n";
	exit;
}

print "$case_path $OutPath $log_name\n";
logname($log_name);
tpDeckInfo->init($case_path, $OutPath, "tonyplot", "skip.txt", $keep_config);

my $result_name = "$OutPath/tp_result.log";

my $Temp;


#my $s_pid = startpv($exe);
#my ($cmd_name , $cmd_ver , $cmd_platform , $cmd_exe) = get_pv($s_pid);
#logx("$cmd_name , $cmd_ver , $cmd_platform , $cmd_exe");
#end_win($s_pid);


my @decks = tpDeckInfo->decks();
for my $c1 (@decks) {
	print "$c1\n";
}
print "END OF cases listing\n\n";

my $dbMainWin = 0;
my $dbWin_pid = 0;
my $dbWin = 0;

for my $case (@decks) {

	print $case , "\n";
	my $case_base = basename($case);
	my $case_cat = basename(dirname($case));
	my $case_tag = "$case_cat/$case_base";

	my $not_tested = 0;
	my $db_aref = tpDeckInfo->class_var("db_selected");
	for my $deck1 (@$db_aref) {
		my $case_in = $deck1->{deck_name} . ".in";
		if ($case_in eq $case_base) {
			$not_tested = 1;
			last;
        	}
	}

#	next unless ($not_tested);



	print "ready to open next case";
	

	my $deck = new tpDeckInfo($case) || next;
	
	my $start_time = strftime "%H:%M:%S", localtime;
	logx("before " . $case);
	$deck->run();

	$dbWin_pid = $deck->pid();
	logx($deck->now());
	
	$deck->waitloop();
	
	my $log_prod;
	my $log_ver;
	my ($user_t , $system_t , $elapsed_t, $CPU_u ) = (0,0,0,0);

print "\n\n             END OF a CASE, waiting ....\n";

	my $performance_str = $deck->finish();
	my $windows_str = "WER: $deck->{WER}";
	my $all_windows = "allWIN:" . $deck->all_windows_str();

	my $end_time = strftime "%H:%M:%S", localtime;
	
	system("echo $case_tag, $start_time, $end_time, $performance_str, $windows_str, $all_windows >> $result_name");

	system("date");
	sleep(3);
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
