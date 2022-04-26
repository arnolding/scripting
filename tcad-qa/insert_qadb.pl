#!/build/arnoldh/perl520/bin/perl
use strict;
use warnings;
use Date::Parse;
use POSIX 'strftime';
use DBI;
use Data::Dumper; 
use RegUtil;
use File::Find;
use Getopt::Long;
$Data::Dumper::Sortkeys = 1;
$Data::Dumper::Deepcopy = 1;

my $dbh;
my $sth;
my $parameters_done = 0;
my %db_parameters = (
	max_allowed_packet => ""
	);
my @tools;
my @non_tools;

# $msql is for multiple rows insertion makeup
my $msql;
my $msql_cols;
my $tobe_run = 0;
my $rcd = "/build/arnoldh/reg/tests";

my $test_list = "";
if ($ARGV[0]) {
	$test_list = $ARGV[0];
} else {
	die "Usage: $0 tag\n\tto match name in $rcd]";
}
system("date");
my $test_dir_href = get_test_result($rcd , $test_list);

open TT , ">tools.txt";
for my $t (@tools) {
	print TT $t , "\n";
}
close TT;
open TT , ">non_tools.txt";
for my $t (@non_tools) {
	print TT $t , "\n";
}
close TT;
#system("date");
#upload_database($test_dir_href);
#system("date");

my @fname_diff = ();
my $ptree_aref = [];
my %ps_shot_duration;
sub read_ptree
{
	my $run_tag = shift;
	my $output_path = "$run_tag/output";
	if (! -d $output_path) {
		warn "$output_path does not exist" if (! -d $output_path);
		return undef;
	}

	my @ptree_folder_path = ($output_path);
#/build/arnoldh/reg/tests/tcad-0929-0/cases/4.2.9.R/*/*/.*/deckbuild/rto.xml";
	find(\&find_file_callback, @ptree_folder_path);
	my $ps_set = extract_ptree($run_tag, $ptree_aref);


	open P , "> $output_path/reg_ptree.set";
	print P Data::Dumper->Dump($ps_set);
	close P;
#	open P , "> $output_path/count_blank_fields.txt";
#	print P Data::Dumper->Dump([$count_result]);
#	close P;
	open P , "> $output_path/fname_diff.log";
	print P Data::Dumper->Dump([\@fname_diff]);
	close P;

	my $reg_summary = get_ps_summary($ps_set);
	
	open P , "> $output_path/reg_summary.log";
	print P Data::Dumper->Dump([$reg_summary]);
	close P;
	my $sum_table = summary_in_table($reg_summary);
	open P, "> $output_path/reg_ptable.summary";
	print P Data::Dumper->Dump([$sum_table]);
	close P;

	return { reg_summary => $reg_summary , 
			 summary_table => $sum_table};
}

sub get_test_result
{
	my $test_root = shift;
	my $list_wildcard = shift;
	my @all_d = glob "$test_root/*";
	my @sel_d;
	if ($list_wildcard) {
		for my $d1 (@all_d) {
			if (index($d1 , $list_wildcard) > 0) {
				push @sel_d, $d1;
			}
		}
	}
	die "No test result in folder [$test_root] match to [$list_wildcard]" unless (@sel_d);

	my $count = 36;

	my $test_dir;
	for my $d1 (@sel_d) {
		if ( not -l $d1 and -f "$d1/r.log" and -f "$d1/result.log" and -e "$d1/output") {
			system("date");
			my $reg_ptree = read_ptree($d1);
			next unless $reg_ptree;
			print "$d1/r.log" , "\n" ;
			my $r_href = parse_r_file("$d1/r.log");
			$test_dir = {
				test_idx => [{
					path => $d1,
					r_log => $r_href,
					ptree => $reg_ptree
					}]
				};
#			add_deck(\%test_dir , "test_idx" ,
#				{path => $d1,
#				 r_log => $r_href,
#				 ptree => $reg_ptree
#				});
			system("date");
			upload_database($test_dir);
#			last unless ($count);
#			$count--;
		}
	}

	return $test_dir;
}
sub add_deck
{
	my $r_data = shift;
	my $major_label = shift;
	my $ref = shift;
	if (exists $r_data->{$major_label}) {
		push @{$r_data->{$major_label}} , $ref;
	} else {
		$r_data->{$major_label} = [ $ref ];
	}
}
sub parse_r_file
{
	my $r_fname = shift;
	open FN , "< $r_fname" or die "open r file $r_fname failed";

	my $r_data = {};
	my $line_count = 1;
	my $group_begin = 0;
	while (<FN>) {
		chomp;
		my $line = $_;
		my $col_pos = index($line, " :");
		if ($col_pos > 0) {
			my $dt_str = substr($line , 0 , $col_pos);
			my $action = substr($line , $col_pos + 2);
			$action =~ s/\s+//;
			my $dt = str2time($dt_str,"CST");
#			print "dt = [$dt] action = [$action]\n";

			if ($action eq "Started") {
				$r_data->{test_idx_start} = $dt;
			}
			if ($action =~ /Start (\d+).+\[(.+)\]/) {
				$r_data->{program_pid} = $1;
				$r_data->{program_fullpath} = $2;
			}
			if ($action =~ /before (\S+)/) {
				add_deck($r_data , "before_deck" , 
						{dt => $dt,
						 deck_fullpath => $1
						});
			}
			if ($action =~ /deckbuild (\S+)/) {
				add_deck($r_data , "start_deck" , 
						{dt => $dt,
						 deck_fullpath => $1
						});
			}

			$group_begin = 2 if ($action eq "find_windows");
		} else {
			my $single_dt;
			if ($line eq "") {
				$group_begin = 0;
			} elsif (defined ($single_dt = str2time($line))) {
#				print "single datetime [$single_dt]\n";
				$group_begin = 1;
			} else {
				if ($group_begin == 1) {
					if ($line =~ /^\d+\s+...\w+\.exe/)  {
						$group_begin = 11;
					} else {
						die "in group 1 [$line] at line $line_count in $r_fname";
					}
				} elsif ($group_begin == 11) {
					if ($line =~ /^[│\s]+ctime \[\d+\]/)  {
						$group_begin = 111;
					} else {
						die "in group 11 [$line] at line $line_count in $r_fname";
					}
				} elsif ($group_begin == 111) {
#					if ($line =~ /^\s*[└├]─\d+\s+\S+/)  {
					if (($line =~ /^\s*└─\d+\s+/) or
						($line =~ /^\s*├─\d+\s+/) or 
						($line =~ /^\s*(│\s+)+└─\d+\s+/) or
						($line =~ /^\s*(│\s+)+├─\d+\s+/))  {
						$group_begin = 11;
					} else {
						die "in group 111 [$line] at line $line_count in $r_fname";
					}
				} elsif ($group_begin == 2) {
					if ($line =~ /^\d+, \d+, \[.+\]/)  {
						$group_begin = 2;
					} else {
						die "in group 2 [$line] at line $line_count in $r_fname";
					}
				} else {
					die "Unrecognized [$line] at line $line_count in $r_fname";
				}
			}
		}
		$line_count++;
	}

	close FN;

	return $r_data;
}


sub upload_database
{
	my $test_dir_href = shift;

	my @test_idx = @{$test_dir_href->{test_idx}};

	my $mach_id = machine();
	my $test_id;
	for my $idx1 (@test_idx) {
		my $root_path = $idx1->{path};
		my $test_date = POSIX::strftime("%Y-%m-%d %T" ,
				localtime($idx1->{r_log}->{test_idx_start}));
		my $pgm = $idx1->{r_log}->{program_fullpath};
		my $pid = $idx1->{r_log}->{program_pid};
		my ($prod, $ver, $platform);
warn "pgm [$pgm] test_date [$test_date]" if (not defined $pgm);
		if ($pgm =~ /lib\/(\w+)\/(\d+\.\d+\.\d+\.[ABCR])\/(\w+)/) {
			$prod = $1;
			$ver = $2;
			$platform = $3;
		}
system("date");
		$test_id = insert_test_idx($test_date, $prod, $ver, $platform,
			$mach_id, $pid, $root_path);

#		my $testps_id = insert_test_ps_plain($test_id, $idx1->{ptree}->{summary_table});
		my $testps_id = insert_test_ps_normal($test_id, $idx1->{ptree}->{summary_table});
system("date");
		closedb();
	}
}

sub machine
{
	my $now_local = POSIX::strftime("%Y-%m-%d" , localtime());
	my ($platform, $hostname, $arch , $num_cpu, $CPU_MHz, $memTotal , $swapTotal ) =
			g_machine();

	my $sel_sql = "select machine_id from machine where hostname = \"".
			"$hostname\" and num_cpu = $num_cpu and memtotal_KB = $memTotal";

	run_sql($sel_sql);
	my ($data) = fetch();
	my $last_id;

	if ($data) {
		$last_id = $data;
	} else {
			my $sql = "insert into machine (hostname, platform, num_cpu, " .
				"CPU_MHz, Architecture, memtotal_KB, swaptotal_KB, ".
				"data_createdate) values (" .
				"\"$hostname\",\"$platform\",\"$num_cpu\",\"$CPU_MHz\",".
				"\"$arch\",\"$memTotal\",\"$swapTotal\" , \"$now_local\")";

		run_sql($sql);
		$last_id = last_insert_id();
	}

	return $last_id;
}

sub insert_test_idx
{
	my ($test_date, $prod, $ver, $platform, $mach_id, $pid, $root_path) = @_;

	my $sel = "select test_id from test_idx where test_date = \"$test_date\" and " .
			"machine_id = $mach_id and pid = $pid";
	my $s;
	my $last_id;
	$s = run_sql($sel);
	my ($data) = fetch();


	print "test_id $data mach_id $mach_id\n";

	if ($data) {
		$last_id = $data;
	} else {
			my $sql = "insert into test_idx (test_date, prod, ver, platform,
				machine_id, pid, root_path) values 
				(\"$test_date\" , \"$prod\", \"$ver\", \"$platform\",
				$mach_id, $pid, \"$root_path\")";
		#print "[$sql]\n";
		$s = run_sql($sql);
		$last_id = last_insert_id();
	}

	return $last_id;
}
sub insert_test_ps_plain
{
	my $test_id = shift;
	my $ptree_aref = shift;

	my $sth;
    for my $c1 (@$ptree_aref) {
    #push @table , [ $case, $ps_tree->{fname}, $ps_tree->{size} , $ps_tree->{time} ,
    #      $ps_tree->{ctime}, $ps_tree->{end} - $ps_tree->{start},
    #      $ps_tree->{pid}, $ps_tree->{ppid}];
		my $sql = "insert into test_ps2 (test_id, casename, fname, memory_MB, 
			time, ctime, start, elapse_time, pid, ppid, version, num_processor) values ";
		$sql .= "($test_id, \"$c1->[0]\", \"$c1->[1]\", $c1->[2], $c1->[3],
			$c1->[4], $c1->[5], $c1->[6], $c1->[7], $c1->[8], ";
		if ($c1->[9]) {
			$sql .= "\"$c1->[9]\", ";
		} else {
			$sql .= "NULL, ";
		}
		if ($c1->[10]) {
			$sql .= "$c1->[10])";
		} else {
			$sql .= "NULL)";
		}
		run_sql($sql);
    }
	commit();
}
sub insert_test_ps_normal
{
	my $test_id = shift;
	my $ptree_aref = shift;

	my $sth;
    for my $c1 (@$ptree_aref) {
		my $c9;
		if ($c1->[9]) {
			$c9 = $c1->[9];
		} else {
			$c9 = "0.0.0.0";
		}
		my $s1 = "insert ignore into deck (short_name) values (\"$c1->[0]\")";
		run_sql($s1);

		my $s2 = "insert ignore into tool (version, program_name ) values (";
		$s2 .= "\"$c9\" , \"$c1->[1]\" )";
		run_sql($s2);

		my $sql = "insert into test_ps (test_id, casename, tool_id, memory_MB, 
			time, ctime, start, elapse_time, pid, ppid, num_processor) values ";
		$sql .= "($test_id, ";
		$sql .= "(select deck_id from deck where short_name=\"$c1->[0]\"),";
		$sql .= "(select tool.tool_id from tool where version = \"$c9\" and program_name = \"$c1->[1]\"),";
		$sql .= "$c1->[2], $c1->[3],
			$c1->[4], $c1->[5], $c1->[6], $c1->[7], $c1->[8], ";
		if ($c1->[10]) {
			$sql .= "$c1->[10])";
		} else {
			$sql .= "NULL)";
		}
		run_sql($sql);
		commit();
    }
}
sub insert_test_ps
{
	my $test_id = shift;
	my $ptree_aref = shift;

	my $sth;
	my $sql = "insert into test_ps2 (test_id, casename, fname, memory_MB, 
			time, ctime, start, elapse_time, pid, ppid, version, num_processor) values ";
	make_multi_insert($sql , "");
    for my $c1 (@$ptree_aref) {
    #push @table , [ $case, $ps_tree->{fname}, $ps_tree->{size} , $ps_tree->{time} ,
    #      $ps_tree->{ctime}, $ps_tree->{end} - $ps_tree->{start},
    #      $ps_tree->{pid}, $ps_tree->{ppid}];
		$sql = "($test_id, \"$c1->[0]\", \"$c1->[1]\", $c1->[2], $c1->[3],
			$c1->[4], $c1->[5], $c1->[6], $c1->[7], $c1->[8], ";
		if ($c1->[9]) {
			$sql .= "\"$c1->[9]\", ";
		} else {
			$sql .= "NULL, ";
		}
		if ($c1->[10]) {
			$sql .= "$c1->[10])";
		} else {
			$sql .= "NULL)";
		}

		make_multi_insert("", $sql);
    }

	make_multi_insert_commit();
}
sub opendb_retry
{
	my $connecting_try = shift || 3;
	while ((!$dbh) and ($connecting_try--)) {
		opendb();
		last if (($dbh) or ($connecting_try == 0));
		warn "once";
		sleep(3);
	}
	if (!$dbh) {
		die "cannot establish connection";
	}
}
sub opendb
{
	my $dsn = "DBI:MariaDB:database=qadb;host=cadb01;port=3306";
	$dbh = DBI->connect($dsn, "arnoldho", "KDas72@!w2");
	$dbh->{RaiseError} = 0;
	$dbh->{AutoCommit} = 0;
	get_db_parameters();
	return $dbh;
}

sub get_db_parameters
{
	my $sql;

	return if ($parameters_done);
	for my $k (keys %db_parameters) {
		next if ($db_parameters{$k});
		$sql = "Show variables like '" . $k . "'";
		run_sql($sql);

		my ($col, $data) = fetch();
		$db_parameters{$col} = $data;
	}

	print Data::Dumper->Dump([\%db_parameters]);
	$parameters_done = 1;
}

sub make_multi_insert
{
	my $cols = shift;
	my $row = shift;

#print "ddd $db_parameters{max_allowed_packet}\n";
#die if (not defined $db_parameters{max_allowed_packet});
	if ($cols) {
		$msql = $cols;
		$msql_cols = $cols;
		$tobe_run = 0;
	} else {
		if ((length($msql) + length($row) + 1000 ) < $db_parameters{max_allowed_packet}) {
			$msql .= " " . $row . ", ";
			$tobe_run += 1;
		} else {
			my $rows_inserted = make_multi_insert_commit();
			make_multi_insert($cols, $row);
			return $rows_inserted;
		}
	}
}
sub make_multi_insert_commit
{
	return 0 if ($tobe_run == 0);
	my $rows_inserted = $tobe_run;
	substr($msql , -2, 2) = "; ";

	my $try = 2;
	while ($try > 0) {
		$try--;
		if (run_sql($msql)) {
				$msql = $msql_cols;
				$tobe_run = 0;
				$try = '0E0';
		}
	}

	die unless ($try);
	return $rows_inserted;
}
sub run_sql
{
	my $sql = shift;

again:
	opendb_retry();
	
	$sth = $dbh->prepare($sql);
	my $exe_res = $sth->execute();
	if (!$exe_res) {
		my $errstr = $dbh->errstr();
		print "tobe_run [$tobe_run] SQL [" . $sql . "]\n";
		warn "execute error: " . $errstr;
		if (index($errstr, "server has gone away") >= 0) {
			undef $dbh;
			goto again;
		}
	}
	#commit();
	return $exe_res;
}
sub commit
{
	if ($dbh) {
		$dbh->commit();
	}
}
sub closedb
{
	if ($dbh) {
		$dbh->disconnect();
		undef $dbh;
		undef $sth;
	}
}
sub fetch
{
	return $sth->fetchrow() if ($sth);
	return undef;
}
sub last_insert_id
{
	return $sth->last_insert_id();
}
sub insert
{
my $dsn = "DBI:MariaDB:database=qadb;host=cadb01;port=3306";
my $dbh = DBI->connect($dsn, "arnoldho", "KDas72@!w2");
 
my $sth0 = $dbh->prepare(
    'insert into tt values (100)'
) or die 'prepare statement failed: ' . $dbh->errstr();

$sth0->execute() or die 'execution failed: ' . $dbh->errstr();


my $sth = $dbh->prepare(
    'SELECT * from tt'
) or die 'prepare statement failed: ' . $dbh->errstr();
$sth->execute() or die 'execution failed: ' . $dbh->errstr();
print $sth->rows() . " rows found.\n";
while (my $ref = $sth->fetchrow_hashref()) {
	for my $k (keys %$ref) {
    print "Found a row: $k = $ref->{$k}\n";
	}
}
}


sub flat_ptree
{
    my $ps_tree = shift;
    my $case = shift;
	my $level = shift || 1;
    my @table = ();

	warn "start not defined" unless ($ps_tree->{start});
	unless ($ps_tree->{end}) {
		warn "end not defined for case $case level $level";
		print Data::Dumper->Dump([$ps_tree]);
#		die;
	}
	#"insert into test_ps (test_id, casename, fname, memory_MB, 
	#		time, ctime, start, elapse_time, pid, ppid) values ...
    push @table , [ $case, $ps_tree->{fname}, $ps_tree->{size} , $ps_tree->{time} , $ps_tree->{ctime}, $ps_tree->{start}, $ps_tree->{end} - $ps_tree->{start}, $ps_tree->{pid}, $ps_tree->{ppid}, $ps_tree->{version}, $ps_tree->{num_processor}];
    for my $p1 (@{$ps_tree->{child}}) {
        push @table , @{flat_ptree($p1 , $case , $level + 1)};
    }
    
    return \@table;
}
sub summary_in_table
{
    my $reg_sum = shift;
    my @ps_list;
    for my $c1 (@$reg_sum) {
        my $case = $c1->{case};
		my $case_name;
		if ($case =~ /output\/(.+)\//) {
			$case_name = $1;
		} else {
			die;
		}
        my @row_in_case1 = @{flat_ptree($c1->{ps_summary} , $case_name)};
        push @ps_list, @row_in_case1;
    }
    return \@ps_list;
}

sub get_ps_summary
{
    my $ps_set = shift;
    my @tmp_ps;
    for my $ps1 (@$ps_set) {
        my $href = get_ps1_summary($ps1);
        push @tmp_ps , $href;
    }
    
    my @reg_ps = sort {$a->{first_epoch} <=> $b->{first_epoch}} @tmp_ps;
    
    return \@reg_ps;
}
sub get_empty_fields
{
    my $ps_set = shift;
	my $fields_tocheck_aref = shift;
    my @tmp_ps;
	my %all_fname = ();
    for my $ps1 (@$ps_set) {
        #my $href = get_ps1_summary($ps1);
        my $href = get_ps1_fields($ps1 , $fields_tocheck_aref);
        if (scalar (keys %$href) > 1) {
        	push @tmp_ps , $href;
			for my $k (keys %$href) {
				if ((substr($k , 0 , 2) eq "z_") or
					(substr($k , 0 , 2) eq "zs")) {
					if (exists $all_fname{$k}) {
						$all_fname{$k} += $href->{$k};
					} else {
						$all_fname{$k} = $href->{$k};
					}
				}
			}
		}
    }

	$all_fname{grand_sum} = "Count-all-fname";
	push @tmp_ps , \%all_fname;
    
    return \@tmp_ps;
}
sub get_ps1_fields
{
	my $ps1 =shift;
	my $fields_tocheck_aref = shift;
    my $href = get_ps1_fields_pass1($ps1 , $fields_tocheck_aref);

    my @ps_shot = @{$ps1->{ps_tree_array}};
	my @pid_n_start = ();
	if ( exists $href->{pid_n_start} ) {
		@pid_n_start = @{$href->{pid_n_start}};
	}
    for my $ps_shot1 (@ps_shot) {
        my $shot_epoch = $ps_shot1->{shot_epoch};
		my @tA = @{$ps_shot1->{ps_tree}};
		for my $ptree (@tA) {
			for my $inst1 (@pid_n_start) {
				if (($ptree->{pid} == $inst1->[0]) and
					($ptree->{start} == $inst1->[1])) {
					$inst1->[2]++;
					if ($shot_epoch > $inst1->[3]) {
						print "----[$shot_epoch] [$inst1->[0]] [$inst1->[1]]","\n";
					}
					if ($shot_epoch == $inst1->[3]) {
						print "====[$shot_epoch] [$inst1->[0]] [$inst1->[1]] $inst1->[2]" , "\n";
					}
				}
			}
		}
	}
	if ((scalar keys %$href) > 1) {
		print "fname : $href->{fname}\n";
		for my $k1 (sort keys %$href) {
			print "$k1 gets blank count : $href->{$k1}" , "\n";
		}
		for my $k1 (@{$href->{pid_n_start}}) {
			print "pid_n_start : $k1->[0],\t$k1->[1],\t$k1->[2]\n";
		}
		for my $k1 (@pid_n_start) {
			print "pid_n_start : $k1->[0],\t$k1->[1],\t$k1->[2]\n";
		}
	}

	return $href;
}

sub get_ps1_fields_pass1
{
	my $ps1 =shift;
	my $fields_tocheck_aref = shift;

	#print Data::Dumper->Dump([$ps1]);
	#print Data::Dumper->Dump([$fields_tocheck_aref]);

	my %count_blank = ();
	$count_blank{fname} = $ps1->{fname};
    my @ps_shot = @{$ps1->{ps_tree_array}};
    for my $ps_shot1 (@ps_shot) {
        my $shot_epoch = $ps_shot1->{shot_epoch};
		my @tA = @{$ps_shot1->{ps_tree}};

		for my $ptree (@tA) {
			my $found = 0;
			for my $field1 (@$fields_tocheck_aref) {
				if ((not defined $ptree->{$field1}) or
					($ptree->{$field1} eq "NA")) {
					$count_blank{$field1}++;
					$found++;
				}
			}
			if ($found > 0) {
				$count_blank{"z_" . $ptree->{fname}}++;
				$count_blank{"zs_" . $ptree->{state}}++;
				if (exists $count_blank{pid_n_start}) {
					push @{$count_blank{pid_n_start}} ,
						[$ptree->{pid} , $ptree->{start} , 0, $shot_epoch];
				} else {
					$count_blank{pid_n_start} = [[$ptree->{pid} , $ptree->{start} , 0 , $shot_epoch]];
				}
			}
		}
	}
	return \%count_blank;
}

sub fname_diff_in
{
    my $from_name = shift;
    my $to_name = shift;

    my $same = 0;
    for my $fdiff (@fname_diff) {
        if (($fdiff->[0] eq $from_name) and
            ($fdiff->[1] eq $to_name) ) {
            $fdiff->[2]++;
            $same = 1;
            last;
        }
    }
    
    if ($same == 0) {
        push @fname_diff , [$from_name , $to_name , 1];
    }
}
sub tag_end
{
    my $ptree = shift;
    my $epoch = shift;
    
    $ptree->{end} = $epoch if (not defined $ptree->{end});
    my @child_new = @{$ptree->{child}};
    for my $c1 (@child_new) {
        tag_end($c1 , $epoch);
    }
}
sub ps_running
{
    my $ptree_pre = shift;
    my $ptree = shift;
    my $ps_epoch = shift;
    my $cover = 1;
    
    # should be exactly same
    my @same_f = ("pid" , "ppid" , "start");
    # large or equal
    my @le_f = ("ctime" ,  "time");
    # should be same (string)
    my @str_f = ("fname");
    for my $s (@same_f) {
        die "missing fields $s" if ((not exists $ptree_pre->{$s}) or 
                                    (not exists $ptree->{$s}));
        return "diff ps" if ($ptree_pre->{$s} != $ptree->{$s});
    }
    for my $s (@le_f) {
        die "missing fields $s" if ((not exists $ptree_pre->{$s}) or 
                                    (not exists $ptree->{$s}));
        return "reverse ps" if ($ptree_pre->{$s} > $ptree->{$s});
    }
    for my $s (@str_f) {
        die "missing fields $s" if ((not exists $ptree_pre->{$s}) or 
                                    (not exists $ptree->{$s}));
        if ($ptree_pre->{$s} ne $ptree->{$s}) {
            fname_diff_in($ptree_pre->{$s},$ptree->{$s});
        }
               
#        return "diff ps" if ($ptree_pre->{$s} ne $ptree->{$s});
    }
    
    my @child_pre = @{$ptree_pre->{child}};
    my @child_new = @{$ptree->{child}};
    
    my $found;
    for my $c_pre1 (@child_pre) {
        if ( defined $c_pre1->{end}) {
            unshift @{$ptree->{child}} , $c_pre1;
            next;
        }
        $found = 0;
        for my $c_new1 (@child_new) {
            if ( ( $c_new1->{pid} == $c_pre1->{pid}) and
                ($c_new1->{start} == $c_pre1->{start}) ) {
                die if (defined $c_pre1->{end});
                $found = 1;
                if (ps_running($c_pre1 , $c_new1, $ps_epoch) ne "OK") {
                    print Data::Dumper->Dump([$c_pre1 , $c_new1]);
                    die;
                }
                next;
            }
        }
        if ($found == 0) {
            tag_end($c_pre1 , $ps_epoch);
            unshift @{$ptree->{child}} , $c_pre1;
        }
    }
    return "OK";
}
sub get_ps1_summary
{
    my $ps1 = shift;
    my $ps_case = $ps1->{fname};
    my @ps_shot = @{$ps1->{ps_tree_array}};
    my $ps_one_file = {};
    my $aref = [];
    my $first_epoch = -1;
    my $last_epoch = -1;
    my $ps_lead_pre;
    my $ps_lead = {};
    for my $ps_shot1 (@ps_shot) {
        #warn "$ps_case";
        my $shot_epoch = $ps_shot1->{shot_epoch};
die if (not defined $shot_epoch);
        $first_epoch = $shot_epoch if (($first_epoch < 0) or ($first_epoch > $shot_epoch));
        $last_epoch = $shot_epoch;
        
        #warn "$ps_case  $shot_epoch";
        $ps_lead = make_tree($ps_shot1->{ps_tree});
        if ( defined $ps_lead_pre) {
            if (ps_running($ps_lead_pre , $ps_lead, $shot_epoch) ne "OK") {
                    print Data::Dumper->Dump([$ps_lead_pre , $ps_lead]);
                    die;
            }
        }
        $ps_lead->{end} = $shot_epoch;
        $ps_lead_pre = $ps_lead;
        
        
#        push @$aref , {
#            tag_epoch => $shot_epoch,
#            ps_lead => $ps_lead
#            };
#        update_summary($ps_summary, $ps_lead);
#		warn "ps_case $ps_case";
#		print Data::Dumper->Dump([$ps_shot1]);
    }

    tag_end($ps_lead , $last_epoch);
    $ps_one_file = {
        case => $ps_case,
        first_epoch => $first_epoch,
#        ps_all => $aref
        ps_summary =>   $ps_lead
        };
    return $ps_one_file;
}

sub get_ps
{
    my $target = shift;
    my $tree = shift;
    
    my $found;
    for my $c1 (@{$tree->{child}}) {
        $found = get_ps($target, $c1);
        if (defined $found ) {
            last;
        }
        if ( ($target->{pid} == $c1->{pid}) and
             ($target->{start} == $c1->{pid}) ) {
            $found = $c1;
            last;
        }
    }
    
    return $found;
}
sub update_summary
{
    my $summary = shift;
    my $plead = shift;
    
    update_1data($summary , $plead);
    if (exists $summary->{simulator}) {
        for my $s_child (@{$summary->{simulator}}) {
            my $s_update = get_ps($s_child, $plead);
            if (defined $s_update) {
                update_1data($s_child, $s_update);
            }
        }
    }
}
sub update_1data
{
    my $origin = shift;
    my $newdata = shift;
    
    if (defined $origin) {
        die "process lead, deckbuild goes mad" if (
                ($origin->{pid} != $newdata->{pid}) or
                ($origin->{ppid} != $newdata->{ppid}) or
                ($origin->{start} != $newdata->{start}) or
                ($origin->{fname} != $newdata->{fname})
            );
        die "process lead time and size should NOT be decreased" if (
                ($origin->{time} > $newdata->{time}) or
                ($origin->{size} > $newdata->{size}) or
                ($origin->{ctime} > $newdata->{ctime})
            );
            
        $origin->{time} = $newdata->{time};
        $origin->{size} = $newdata->{size};
        $origin->{ctime} = $newdata->{ctime};
    } else {
        $origin->{pid} = $newdata->{pid};
        $origin->{ppid} = $newdata->{ppid};
        $origin->{start} = $newdata->{start};
        $origin->{fname} = $newdata->{fname};
        $origin->{time} = $newdata->{time};
        $origin->{size} = $newdata->{size};
        $origin->{ctime} = $newdata->{ctime};
    }
}

## make_tree:
## input array ref of ps result in one shot sampling
## output one process as top, should be deckbuild, and with @child
## only the following elements are stored and return
##      pid,
##      ppid,
##      child - array of child processes,
##      utime       user mode time (1/100s of seconds) (Linux only)
##      stime       kernel mode time                   (Linux only)
##      time        user + system time                
##      cutime      child utime                        (Linux only)
##      cstime      child stime                        (Linux only)
##      ctime       child user + system time
##      size        virtual memory size (bytes)
##      fname
sub make_tree
{
    my $ps_tree_aref = shift;
    my @tA = @$ps_tree_aref;
    my @transfer_fields = ("pid", "ppid", "time",
                        "ctime", "size", "fname", "start",
                        "cmndline");
    my @pre_simplified_ps = ();
    for my $i (0 .. $#tA) {
        my $ps1 = $tA[$i];
        my $ps1x = {};
        if ($ps1->{ctime} != ($ps1->{cutime} + $ps1->{cstime})) {
            die "child CPU time not correct for pid $ps1->{pid}";
        }
        $ps1->{ctime} /= 1000000.0; 
        if ($ps1->{time} != ($ps1->{utime} + $ps1->{stime})) {
            die "self CPU time not correct for pid $ps1->{pid}";
        }
        $ps1->{time} /= 1000000.0;
        $ps1->{size} /= (1024*1024);
        for (@transfer_fields) {
            $ps1x->{$_} = $ps1->{$_};
        }
		$ps1x->{version} = undef;
		$ps1x->{num_processor} = undef;
		my $cmndl = $ps1->{cmndline};
		if ($cmndl =~ /^\S+\/lib\/\w+\/(\d+\.\d+\.\d+\.[ABCR])\/\S+\/(\S+) (-P \d+)?/) {
			my $ver = $1;
			my $pgm = $2;
			my $num_p = $3 || "";
			push @tools, "ver [$ver] pgm [$pgm] fname $ps1x->{fname} $num_p";
			$ps1x->{version} = $ver;
			if ($num_p) {
				$ps1x->{num_processor} = substr($num_p,3);
			}
			if ($pgm ne $ps1x->{fname}) {
				if (index($pgm, $ps1x->{fname}) == 0) {
					$ps1x->{fname} = $pgm;
				}
			}
		} else {
			push @non_tools, substr($cmndl , 0, 70);
		}
		
        $ps1x->{has_parent} = 0;
        $ps1x->{child} = [];
        push @pre_simplified_ps , $ps1x;
    }
    my @simplified_ps = sort {$a->{start} <=> $b->{start}} @pre_simplified_ps;
    
    my $ptree_lead;
    for my $i (0 .. $#simplified_ps) {  
        my $ps1 = $simplified_ps[$i];
        my @child;
        for my $j (0 .. $#simplified_ps) {
            my $ps2 = $simplified_ps[$j];
            next if ($ps2->{start} < $ps1->{start});
            next if ($ps2->{has_parent});
            if ($ps2->{ppid} == $ps1->{pid}) {
                push @child , $ps2;
                $ps2->{has_parent} = 1;
            }
        }
        $ps1->{child} = \@child;
    }
    $ptree_lead = $simplified_ps[0];
    for my $i (1 .. $#simplified_ps) {
        my $ps1 = $simplified_ps[$i];
        if ($ps1->{has_parent} ==0) {
            if ((index($ps1->{cmndline} , "/bin/csh") == 0) or
                (index($ps1->{cmndline} , "sh") ==0) ) {
                push @{$ptree_lead->{child}}, $ps1;
            } else {
                    die "floating process pid=$ps1->{pid}, no parent found";
            }
        }
    }
    for my $i (0 .. $#simplified_ps) {
        my $ps1 = $simplified_ps[$i];
        delete $ps1->{cmndline};
        delete $ps1->{has_parent};
    }
    
    
    return $ptree_lead;
}

sub extract_ptree
{
    my $ptree_head = shift;
    my $ptree_aref = shift;
    my ($total , $equal , $notequal, $ps_set) = (0,0,0, []);
    for my $ptree1 (@$ptree_aref) {
#        print $ptree1 , "\n";
        my ($t , $e , $ne , $ps_a) = extract1tree($ptree1);
        $total += $t;
        $equal += $e;
        $notequal += $ne;
        push @$ps_set , { fname => substr($ptree1 , length($ptree_head)),
                        ps_tree_array => $ps_a};
    }
    
#    print "t: $total  e: $equal n: $notequal\n";
    return $ps_set;
}

sub extract1tree
{
    my $tname = shift;
    my ($total , $equal , $notequal) = (0,0,0);
    my $ps_begin = 0;
    my $ps_href = {};
    my $ps_shot_aref = [];
    my $ps_shot_epoch = -1;
    my $ps_aref = [];
    my $t_epoch0 = -1;
	my $keyword;
    open T , "<$tname";
    while (<T>) {
        chomp;
#=============Tue Sep 29 10:44:09 2020=
        if (/^={4,}(\w+ \w+\s+\d+ \d+:\d+:\d+ \d+) ?= (\d+)/) {
            my $at_time = str2time($1);
            my $t_epoch = $2;
            #print "timetag = [$1] [$at_time] [$t_epoch]\n";
            if ($at_time == $t_epoch) {
                $equal++;
            } else {
                $notequal++;
            }
            $total++;
            
            if ($t_epoch0 >0) {
                my $delta_t = $t_epoch - $t_epoch0;
                #if ( exists $ps_shot_duration{$delta_t} ) {
                    $ps_shot_duration{$delta_t}++;
                #} else {
                #    $ps_shot_duration{$delta_t} = 1;
                #}
                
                $t_epoch0 = $t_epoch;
            }
            $t_epoch0 = $t_epoch;
            
            if (scalar @$ps_shot_aref > 0) {
                push @$ps_aref , { shot_epoch => $ps_shot_epoch,
                                ps_tree => $ps_shot_aref};
                $ps_shot_aref = [];
            }
            $ps_shot_epoch = $at_time;
        } elsif (/^>{40}$/) {
            $ps_begin = 1;
        } elsif (/^<{40}$/) {
            $ps_begin = 0;
            push @$ps_shot_aref , $ps_href;
            $ps_href = {};
			undef $keyword;
        } else {
            die "Should see shot begin and ps begin in $tname" if ($ps_begin == 0);
            if (/^(\w+)\s+(\S+)(.*)/) {
                $ps_href->{$1} = $2 . $3;
				$keyword = $1;
            } elsif(/^(\w+)\s+$/) {
                $ps_href->{$1} = "NA";
				$keyword = $1;
          #  } else {
          #      warn "Should be keyword [$_] in $tname, pre key [$keyword]";
            }
        }
    }
    if (scalar @$ps_shot_aref > 0) {
            push @$ps_aref , { shot_epoch => $ps_shot_epoch,
                                ps_tree => $ps_shot_aref};
    }
    close T;
    return ($total , $equal , $notequal , $ps_aref);
}


sub find_file_callback
{
    my $fname = $File::Find::name;
    if ($fname =~ /ptree\.log$/ ) {
        #print $fname , "\n";
        push @$ptree_aref , $fname;
    }
}
