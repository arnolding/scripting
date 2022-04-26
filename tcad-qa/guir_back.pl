#!/home/arnoldh/perl520/bin/perl
use strict;
use warnings;
use 5.010;

use IO::Socket;
use POSIX ":sys_wait_h";
use X11::GUITest qw/:ALL/;
use Cwd 'abs_path';
use File::Path;
use ProductInfo;
use ProductX11;
use RegUtil;
use DeckInfo;

my $log_name;
my $case_path = "/build/arnoldh/tcad2016/examples";
my $exe = "deckbuild";
DeckInfo->root($case_path);
DeckInfo->target($exe);
my $port_init = 12345; # change this at will
my $port_max = 12347;
my $port = $port_init;
my $QADB_UPLOAD = 0;
my $Temp;
my $OutPath = "/build/arnoldh/tests";
# Get start time

my ($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime(time);
my $StartTime = sprintf("%s %s %02d %02d:%02d:%02d %4d", 
                    ("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat")[$wday],
                    ("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec")[$mon], $mday, $hour, $min, $sec, 1900+$year);

create_outpath();
$log_name = "$OutPath/r.log";

logname($log_name);
my $s_pid = startpv($exe);
my ($cmd_name , $cmd_ver , $cmd_platform , $cmd_exe) = get_pv($s_pid);
logx("$cmd_name , $cmd_ver , $cmd_platform , $cmd_exe");
my $sql_idx = add_idx($cmd_name , $cmd_ver , $cmd_platform);
end_win($s_pid);


DeckInfo->skip("skip.txt");

chdir($case_path);
my @decks = glob "*/*.in";
my $dbMainWin = 0;
my $dbWin_pid = 0;
my $dbWin = 0;

for my $case (@decks) {
	print $case , "\n";
	
	my @path_sep = split /\// , $case;
	my $case_f = $path_sep[0];
	if ( exists $skip_dir{$case_f} ) { next; }
	my $case_n = $path_sep[1];
	$case_n =~ /(.*)\.in/;
	my $DeckName = $1;
	next if ($DeckName eq "dummy");
	my $deck = new DeckInfo($case);
	my $outfile = $DeckName . ".log";
	my $done_file = $DeckName . ".done";
	my $newer_file = $DeckName . "new";
	my $case_m = "dummy.in";
	my $quit = 0;
	chdir($case_f);
	open(DECK , "< $case_n");
	open(MDECK , "> $case_m");
	binmode MDECK;
	while (<DECK>) {
		if ( /^quit/i ) {
			$quit = 1;
			print MDECK "system touch $done_file\n";
		}
		print MDECK;
	}
	if ($quit == 0) {
		print MDECK "system touch $done_file\n";
		print MDECK "quit\n";
	}
	close(MDECK);
	close(DECK);
	system("rm $done_file");
	system("touch $newer_file");

	$dbWin_pid = start($case_m, $outfile);
	my $deck = new DeckInfo($dbWin_pid, $case);

	logx("$exe $case_m -outfile $outfile");
	my $finished;
	my $log0 = "";
	my $count = 0;
	my $one_sleep = 10;
	my $lengthy_kill =0;
	my $wait_st;
	my $wait_t0 = time();
	my $wait_t1 = 0;
	while (1) {
		if (-f $done_file) {
			my $st = end_win($dbWin_pid);
			print "******  endW status [$st]\n\n\n";
		}
		$finished = waitpid($dbWin_pid , WNOHANG);
		$wait_st = $?;
	
		if ($finished == 0) {
			sleep($one_sleep);

			my $loop_check = $deck->wait_n_check();
			my $log_size = `ls -l $outfile`;
			if ($log_size eq $log0) {
				$count += $one_sleep;
				print "count ======== $count\n";
				if ($count > 60000) {
					logx("$log_size");
					#kill 9,$dbWin_pid;
					system("endW_pid.pl $dbWin_pid");
					logx("kill $dbWin_pid");
					$lengthy_kill = 1;
				}
			} else {
				$log0 = $log_size;
				$count = 0;
			}

			chomp($log_size);
			print "$outfile status $log_size\n";
		} else {
			last;
		}
	}
	$wait_t1 = time();
	
	say "$dbWin_pid is done with $finished\n";
	logx("status is " . ($wait_st >> 8) . "\n");
	
	my $log_prod;
	my $log_ver;
	my ($user_t , $system_t , $elapsed_t, $CPU_u ) = (0,0,0,0);
	if ( -f $outfile ) {
		open(LOG , "<" , $outfile);
		while (<LOG>) {
			chomp;
			if (/Version: (\S*) (\d+\.\d+\.\d+\.[ABCR])/) {
			$log_prod = $1;
			$log_ver = $2;
			}

			if (/([\d:\.]+)user ([\d:\.]+)system ([\d:\.]+)elapsed (\d+)%CPU/) {
			$user_t = convert_minute($1);
			$system_t = convert_minute($2);
			$elapsed_t = convert_minute($3);
			$CPU_u = $4;
			}
		}
		close(LOG);

	}

	my @newf = `find . -newer $newer_file`;
	my $result_dir = "$OutPath/$case_f";
	system("mkdir -p $result_dir") unless ( -e $result_dir );
	for (@newf) {
		chomp;
		next if ($_ eq '.');
		next if ($_ eq '..');
		s/^\.\///;
		system("cp $_ $result_dir");
	}
	add_test($sql_idx , $case_n , $case_f , $lengthy_kill? 'K' : 'D' ,
	 $wait_st , 0 , $user_t , $system_t + $user_t, $elapsed_t,
	 $wait_t1 - $wait_t0, 0);
	chdir("..");
}
sub create_outpath
{
  my ($date, $i);
  if (!(-e $OutPath)) { File::Path::mkpath($OutPath, 0, 0755) or die "\nError: Unable to create output path\n[$OutPath]\n\n"; }
  $OutPath = abs_path($OutPath);

  $date = sprintf("%02d%02d", $mon+1, $mday);
  $OutPath = "$OutPath/tcad-$date";
  for ($i = 0; ; $i++) { last unless (-d $OutPath . "-$i" ); }
  $OutPath .= "-$i";

  # Create outpath along with dir for temp files
  $Temp = "$OutPath/.temp";
  File::Path::mkpath("$Temp", 0, 0755) or die "\nError: Unable to create output path\n[$Temp]\n\n";
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
##
## add_idx( $product , $version , $platform
##

sub add_idx
{
	return 0 unless ($QADB_UPLOAD);
my ($prod, $ver ,$plat) = @_;
# create the socket, connect to the port
my $socket = open_client($port);
until ($socket) {
$port++;
if ($port > $port_max) {
die "Use out of port from $port_init to $port_max\n";
}
$socket = open_client($port);
}
##print "open socket $socket\n";
my $line;
my $datetime = localtime;
my $machine = `hostname`;
chomp $machine;
my $sql = "QADBINDX insert into test_idx ";
$sql .= "(test_date, prod , ver , platform , machine) values ('";
$sql .= $datetime . "','";
$sql .= $prod   . "','";
$sql .= $ver  . "','";
$sql .= $plat   . "','";
$sql .= $machine. "')\n";
print $socket $sql;
if (defined ($line = <$socket>)) {
chomp $line;
print "** From socket **";
print "$line\n";
}
close($socket);
return $line;
}
sub add_test
{
my ($idx, $casename ,$casepath, $res, $wait_st, $mem, $usertime, $cputime, $elapsetime , $scripttime , $limittime) = @_;
	return 0 unless ($QADB_UPLOAD);
# create the socket, connect to the port
my $socket = open_client($port);
until ($socket) {
$port++;
if ($port > $port_max) {
die "Use out of port from $port_init to $port_max\n";
}
$socket = open_client($port);
}
##print "open socket $socket\n";
my $line;
my $sql = "QADBTEST insert into test ";
$sql .= "(test_id, casename, case_path, result, wait_status, ";
$sql .= "memory , user_time , cpu_time , elapse_time, ";
	$sql .= "script_wait , limit_time) values (";
$sql .= $idx  . ",'";
$sql .= $casename . "','";
$sql .= $casepath . "','";
$sql .= $res  . "',";
$sql .= $wait_st	. ",";
$sql .= $mem  . ",";
$sql .= $usertime . ",";
$sql .= $cputime. ",";
$sql .= $elapsetime   . ",";
$sql .= $scripttime   . ",";
$sql .= $limittime. ")\n";
##print $sql;
print $socket $sql;
if (defined ($line = <$socket>)) {
chomp $line;
print "** From socket **";
print "$line\n";
}
close($socket);
return $line;
}

sub open_client
{
my $port = shift;
my $server = "twlxws2810.silvaco.com";

my $socket = IO::Socket::INET->new(PeerAddr   => $server,
   PeerPort   => $port,
   Proto=> "tcp",
   Type => SOCK_STREAM);
print "Can't create a socket [$server] [$port] $@\n" unless $socket;

return $socket;
}

