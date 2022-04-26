#!/home/arnoldh/perl520/bin/perl
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
use DeckInfo;
use Getopt::Long;
use Date::Parse;
use Data::Dumper;


my $reg_home = $ENV{REG_HOME} || "/home/qa";
my $case_path = "$reg_home/tests/case_do";
my $OutPath = "$reg_home/tests";
my @log_names = glob "$OutPath/*/r.log";
my $log_name_help = $log_names[0];
my $log_name;
my $help ="";
GetOptions(
        'path=s' => \$case_path,
        'output=s' => \$OutPath,
        'log=s' => \$log_name,
	'help' => \$help
        );

if ($help) {
	print "$0 --path <case-path> --output <work-path> --log <log-file> --keep\n";
	print "example:\n";
	print "$0 --path $case_path --output $OutPath --log $log_name_help\n";
	exit;
}

if ($log_name) {
	my $reg_aref = read_reg($log_name);
	print "call of logx :" . scalar @$reg_aref . "\n";

	my $reg_report = analyze_reg($reg_aref);

	print Data::Dumper->Dump([$reg_report]);

	open my $fh , ">cpu.csv";
	print $fh "fname,all_ctime,epoch,time,error,sim,sim_tim\n";
	for (@{$reg_report->{decks}}) {
		my $d1 = $_;
		my $sim = 'NA';
		my $sim_cpu=0;
		for (@{$d1->{child}}) {
			last if (substr($_->{name},0,8) eq 'tonyplot');
			if ($_->{time} > $sim_cpu) {
				$sim_cpu = $_->{time};
				$sim = $_->{name};
				if (index($sim , " ") > 0) {
					$sim = substr($sim , 0 , index($sim , " "));
				}
			}
		}
		if (! $sim) {
			warn "XXXX";
			print Data::Dumper->Dump([$d1]);
		}
		print $fh $d1->{db}->{fname}, ",",
			$d1->{db}->{all_ctime}, ",",
			$d1->{db}->{pre_epoch}, ",",
			$d1->{db}->{deckbuild}->{time}, ",",
			$d1->{db}->{err}, ",",
			$sim, ",",
			$sim_cpu, "\n";
	}
	close $fh;

} else {
	for $log_name (@log_names) {
		my $reg_aref = read_reg($log_name);
		print "call of logx :" . scalar @$reg_aref . "\n";
	}
}

sub analyze_reg
{
	my $r_aref = shift;
	my $count = 20;
	for (@$r_aref) {
		print $_->{content} , "\n";
		print "-" x 60 , "\n";
		last unless ($count--);
	}

	my %count;
	$count{$_->{tag}}++ for @$r_aref;
	for my $v1 (keys %count) {
		print "v = $v1 count [$count{$v1}]\n";
	}

	
	my $db_info_href = {};
	my $deck_ptr;
	my $sim_ptr;
	my $err_ptr;
	my $t1;
	for (@$r_aref) {
		my $tag = $_->{tag};
		my $ttag = $_->{epoch};
		my $rec = $_->{content};
		if ($tag eq 'Started') {
			$t1 = $ttag;
		} elsif ($tag eq 'Start') {
			if ($rec =~ /Start (\d+) - \[(.*)\]/) {
				$db_info_href->{pid} = $1;
				my $path = $2;
				$db_info_href->{path} = $path;
				if ($path =~ /\/(\d+\.\d+\.\d+\.[ABCR])\/([^\/]+)\//) {
					$db_info_href->{ver} = $1;
					$db_info_href->{platform} = $2;
					$db_info_href->{starttime} = $ttag - $t1;
					$db_info_href->{decks} = [];
				} else {
					warn $path;
				}
			} else {
				warn $rec;
			}
		} elsif ($tag eq 'before') {
			my $deck_new = {};
			my @sim_all = ();
			my %err_all = ();
			$deck_ptr = \$deck_new;
			$sim_ptr = \@sim_all;
			$err_ptr = \%err_all;
			if ($rec =~ /before (.*)/) {
				my $case_path = $1;
				my @segs = split(/\// , $case_path);
				$$deck_ptr->{fname} = $segs[-1];
				$$deck_ptr->{pre_ctime} = 0;
				$$deck_ptr->{pre_epoch} = $ttag;
			} else {
				warn $rec;
			}
		} elsif ($tag eq '') {
			my @procss = split(/\n/ , $rec);
			for (my $i = 0 ; $i <= $#procss ; $i++) {
				if ($procss[$i] =~ /$db_info_href->{pid} (.*)/) {
					$procss[$i+1] =~ /ctime \[(\d+)\] time \[(\d+)\]/;
					my $ctime_m = $1 / 1000000.0;
					my $time_m = $2/ 1000000.0;
					if ($$deck_ptr->{pre_ctime} == 0) {
						$$deck_ptr->{pre_ctime} = $ctime_m;
					}
					$$deck_ptr->{all_ctime} =
						$ctime_m - $$deck_ptr->{pre_ctime};
					$$deck_ptr->{deckbuild} = {
						ctime => $ctime_m,
						time	=> $time_m
					};
					
					$i++;
				} elsif (($procss[$i] =~ /\.\.\.csh/) or
						 ($procss[$i] =~ /\.\.\.time/) or 
						 ($procss[$i] =~ /\.\.\.sh/) or 
						 ($procss[$i] =~ /\/csh/)) {
					$i++;
				} elsif ($procss[$i] =~ /(\d+)\s+\.\.\.(.*)$/) {
					my $sim_pid = $1;
					my $sim = $2;
					$procss[$i+1] =~ /ctime \[(\d+)\] time \[(\d+)\]/;
					my $sim_href = {
						pid		=> $sim_pid,
						name	=> $sim,
						ctime	=> $1/1000000.0,
						time	=> $2/1000000.0
					};
					my $replace = 0;
					for (@$sim_ptr) {
						if ($_->{pid} == $sim_href->{pid}) {
							$_->{ctime} = $sim_href->{ctime};
							$_->{time} = $sim_href->{time};
							$replace = 1;
							last;
						}
					}
					if (!$replace) {
						push @$sim_ptr , $sim_href;
					}
					$i++;
				}
			}
		} elsif ($tag eq 'find_windows') {
			my @windows = split(/\n/ , $rec);
			for (my $i = 0 ; $i <= $#windows ; $i++) {
				if ($windows[$i] =~ /\[(.*?)\]/) {
					my $win_title = $1;
					if ($win_title =~ /error/i) {
						$err_ptr->{$win_title}++;
					}
				}
			}
		} elsif ($tag eq 'deckbuild') {
			my $err_str = "";
			$err_str = join("|" , keys %$err_ptr);
			$$deck_ptr->{err} = $err_str;
			my $deck = {
				db	=> $$deck_ptr,
				child	=> $sim_ptr
			};
			push @{$db_info_href->{decks}}, $deck;
		}
	}

	return $db_info_href;
}

sub read_reg
{
	my $rlog = shift;
	
	my $fh;
	print "Processing $rlog\n";
	my $total_line = 0;
	my $recognized_line = 0;
	my $logx_call = "";
	my @all_logx = ();
	my $tag;
	my $epoch;
	open $fh , "< $rlog" or die $?;
	while (<$fh>) {
		chomp;
		my $line = $_;
		my $timetag;
		if ($timetag = date_start($line)) {
			if ($logx_call) {
				push @all_logx , {
									epoch	=> $epoch,
									tag		=> $tag,
									content	=> $logx_call
								};
			}
			$logx_call = $line;
			$tag = $timetag->{tag};
			$epoch = $timetag->{epoch};
		} else {
			$logx_call .= "\n" . $line;
		}
	}
	close ($fh);
	if ($logx_call) {
				push @all_logx , {
									epoch	=> $epoch,
									tag		=> $tag,
									content	=> $logx_call
								};
	}
	

	return \@all_logx;
}

sub date_start
{
	my $l1 = shift;
	my $epoch;
	my $tag;
	my $href;
	if ($l1 =~ /(.+) : (.*)/) {
		my $dstr = $1;
		$tag = $2;
		my $pos = index($tag , " ");
		if ($pos > 0) {
			$tag = substr($tag , 0 , $pos);
		}
		my ($ss,$mm,$hh,$day,$month,$year,$zone) = strptime($dstr);
		if (!$zone) {
			$epoch = str2time($dstr);
		}
	}
	if ($epoch) {
		$href = {
			epoch	=> $epoch,
			tag		=> $tag
		}
	}
	return $href;
}

