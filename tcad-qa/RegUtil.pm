package RegUtil;

use IO::Socket;
use POSIX;
use strict;
use warnings;
require Exporter;
our @ISA = qw(Exporter);
our @EXPORT = qw(logx logname logdb logdb2 logdb3 logdb4 open_client g_machine);
my $log_fname = 'nono';

my $port_init = 12365; # change this at will
my $port_max = 12367;
my $port = $port_init;
my $db_fname = "/build/qadb/db/b.db";
#my $db_fname = "/build/qadb/db/test_migration/b.db";
my %logdb_arguments = ('test_run' =>
	['test_run_v' ,
	'(
		test_date,
		tester,
		pid,
		case_root,
		deleted,
		prod,
		ver,
		install_path,
		platform,
		hostname,
		num_cpu,
		CPU_MHz,
		Architecture,
		memtotal_KB,
		swaptotal_KB) '] ,
	'test_1_deck' =>
	['test_v',
	'(
		run_id,
		prod,
		ver,
		platform,
		result,
		wait_status,
		ps_pid,
		ps_ppid,
		ps_start,
		ps_ctime,
		ps_time,
		ps_elapse,
		ps_size,
		ps_rss,
		state,
		fname,
		script_wait,
		limit_time,
		deck_name,
		case_path
	) '],
	'test_1_error' =>
	['test_error_v',
	'(
		test_id,
		type,
		message,
		message2
	) '],
	'test_1_outcome' =>
        ['test_outcome_v',
        '(
                test_id,
                type,
                name
        ) '],

	);


sub open_client
{
	my $port = shift || $port_init;
	my $server = "twlxws2810.silvaco.com";

	my $socket;

        until ($socket) {
		$socket = IO::Socket::INET->new(PeerAddr   => $server,
			PeerPort   => $port,
			Proto => "tcp",
			Type => SOCK_STREAM);
		logx("Can't create a socket [$server] [$port] $@\n") unless $socket;
                $port++;
                if ($port > $port_max) {
                        warn "Use out of port from $port_init to $port_max";
			return;
                }
        }

	return $socket;
}

sub g_platform
{
	my $p = `uname -sr`;
        chomp $p;
	return $p;
}
sub g_hostname
{
	my $h = `hostname -s`;
        chomp $h;
	return $h;
}
sub g_cpu
{
	my ($arch , $num_cpu, $CPU_MHz) = ('NA',0,0);
	my $lscpu = `lscpu`;
	my @lscpu = split "\n" , $lscpu;
	for (@lscpu) {
		if (/^Architecture:\s+(\S+)\s*/) {
			$arch = $1;
		} elsif (/^CPU\(s\):\s+(\S+)\s*/) {
			$num_cpu = $1;
		} elsif (/^CPU.+MHz:\s+(\S+)\s*/) {
			$CPU_MHz = $1;
		}
	}

	return ($arch,$num_cpu,$CPU_MHz);
}
sub g_hostmem
{
	my ($memTotal , $swapTotal ) = (0,0);
	my $m = `cat /proc/meminfo`;
	my @minfo = split "\n", $m;
	for (@minfo) {
		if (/^MemTotal:\s+(\S+)\s*/) {
			$memTotal = $1;
		} elsif (/^SwapTotal:\s+(\S+)\s*/) {
			$swapTotal = $1;
		}
	}

	return ($memTotal , $swapTotal);
}

sub g_machine
{
	my $platform = g_platform();
	my $hostname = g_hostname();
	my ($arch , $num_cpu, $CPU_MHz) = g_cpu();
	my ($memTotal , $swapTotal ) = g_hostmem();
	return ($platform, $hostname, $arch , $num_cpu, $CPU_MHz, $memTotal , $swapTotal );
}

sub logdb2
{
	my $type = shift;
	my $run_id = shift;
	my $case_path = shift;
	my $deckname = shift;
	my $pt = shift;
	return 0 unless ($logdb_arguments{$type});
	return 0 if ($run_id <= 0);

	my $cmndline = $pt->{cmndline};
	$cmndline =~ s/^\s+|\s+$//g;
	my @cmnds = split ' ', $cmndline;
	my ($install, $prod, $ver, $prod_platform, $prod_exe) =
                ('NA','NA','0.0.0.0','NA','NA');
	if ($cmnds[0] =~ /(\S+)\/lib\/(\S+?)\/(\S+?)\/(\S+?)\/(\S+)$/) {
		($install, $prod, $ver, $prod_platform, $prod_exe) =
                ($1, $2, $3, $4, $5);
        } else {
		$prod = $cmnds[0];
		$ver = '0.0';
	}
	
	my $result = "TBD";
	my $wait_status = "TBD";
	my $script_wait = 0;
	my $limit_time = 0;

	my $now_epoch =time();
	my $platform = g_platform();
	my $hostname = g_hostname();
	my ($arch , $num_cpu, $CPU_MHz) = g_cpu();
	my ($memTotal , $swapTotal ) = g_hostmem();

	my $sql = "INSERT INTO $logdb_arguments{$type}->[0] ";
	$sql .= $logdb_arguments{$type}->[1];
	$sql .= "VALUES (";
	$sql .= $run_id .",";
	$sql .= "'" . $prod ."',";
	$sql .= "'" . $ver ."',";
	$sql .= "'" . $prod_platform ."',";
	$sql .= "'" . $result . "',";
	$sql .= "'" . $wait_status . "',";
	$sql .= $pt->{pid} . ",";
	$sql .= $pt->{ppid} . ",";
	$sql .= $pt->{start} . ",";
	$sql .= $pt->{ctime}/1000000.0 . ",";
	$sql .= $pt->{time}/1000000.0 . ",";
	$sql .= $now_epoch - $pt->{start} . ",";
	$sql .= $pt->{size}/1024 . ",";
	$sql .= $pt->{rss}/1024 . ",";
	$sql .= "'" . $pt->{state} . "',";
	$sql .= "'" . $pt->{fname} . "',";
	$sql .= $script_wait . ",";
	$sql .= $limit_time . ",";
	$sql .= "'" . $deckname . "',";
	$sql .= "'" . $case_path . "'";
	$sql .= ");";

	$sql =~ s/\n/ /g;
	my $line;
	my $socket = open_client();
	##print $socket "b.db \"$sql\"\n";
	print $socket "$db_fname \"$sql\"\n";
print "logdb2: [$sql]\n";
logx( "logdb2: [$sql]");
	while (defined ($line = <$socket>)) {
		chomp $line;
		print "[$line]\n";
	}
	close($socket);
	if ( $line) {
		return 0;
	}

	$socket = open_client();

	$sql = "SELECT test_id from $logdb_arguments{$type}->[0] WHERE ps_start=$pt->{start} AND ps_pid=$pt->{pid} order by test_id desc limit 1;";
	print $socket "$db_fname \"$sql\"\n";
	my $test_id = 0;
	while (defined ($line = <$socket>)) {
		chomp $line;
		if ($test_id == 0) {
			if ($line =~ /(\d+)/) {
				$test_id = $1;
			}
		}
	}
	close($socket);
	return $test_id;
}

sub logdb3
{
	my $type = shift;
	my $test_id = shift;
	my $err_type = shift;
	my $err_msg = shift;
	return 0 unless ($logdb_arguments{$type});
	return 0 if ($test_id <= 0);

	my $sql = "INSERT INTO $logdb_arguments{$type}->[0] ";
	$sql .= $logdb_arguments{$type}->[1];
	$sql .= "VALUES (";
	$sql .= $test_id .",";
	$sql .= "'" . $err_type ."',";
	$sql .= "'" . $err_msg ."',";
	$sql .= "'NA'";
	$sql .= ");";

	$sql =~ s/\n/ /g;
	my $line;
	my $socket = open_client();
	##print $socket "b.db \"$sql\"\n";
	print $socket "$db_fname \"$sql\"\n";
print "logdb3: [$sql]\n";
logx( "logdb3: [$sql]");
	while (defined ($line = <$socket>)) {
		chomp $line;
		print "[$line]\n";
	}
	close($socket);
	if ( $line ) {
		return 0;
	}

}
sub logdb4
{
	my $type = shift;
	my $test_id = shift;
	my $outcome_type = shift;
	my $outcome_name = shift;
	return 0 unless ($logdb_arguments{$type});
	return 0 if ($test_id <= 0);

	my $sql = "INSERT INTO $logdb_arguments{$type}->[0] ";
	$sql .= $logdb_arguments{$type}->[1];
	$sql .= "VALUES (";
	$sql .= $test_id .",";
	$sql .= "'" . $outcome_type ."',";
	$sql .= "'" . $outcome_name ."'";
	$sql .= ");";

	$sql =~ s/\n/ /g;
	my $line;
	my $socket = open_client();
	##print $socket "b.db \"$sql\"\n";
	print $socket "$db_fname \"$sql\"\n";
print "logdb4: [$sql]\n";
logx( "logdb4: [$sql]");
	while (defined ($line = <$socket>)) {
		chomp $line;
		print "[$line]\n";
	}
	close($socket);
	if ( $line ) {
		return 0;
	}

}



sub logx
{
	my $msg = $_[0];
	if ($log_fname eq 'nono') {
		my $now_string = strftime "%b_%e_%H_%M", localtime;
		$log_fname = "/tmp/" . $now_string . ".log";
	}

	open(LOG , ">> $log_fname");
	my $time_mark = localtime;
	print LOG "$time_mark : $msg\n";
	close(LOG);
}

sub logname
{
	my $fname = $_[0];
	if (open(LOG , ">> $fname") ) {
		$log_fname = $fname;
		close(LOG);
	} else {
		my $now_string = strftime "%b_%e_%H_%M", localtime;
		$log_fname = "/tmp/" . $now_string . ".log";
	}
	logx("Started");
}

1;
