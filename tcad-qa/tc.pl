#!/usr/bin/perl
use strict;
use warnings;

use Data::Dumper;
use Proc::ProcessTable;
use POSIX ":sys_wait_h";
my $log_fname = "child_track.txt";
 
if ($#ARGV == -1) {
	die "add a pid to track\n";
}
#getwindows();
my $pid = $ARGV[0];

my $head = DeckInfo->new($pid , $log_fname);

while (1) {
	$head->find_children();
	my $e = "";
	$head->print_ptree($head->{pid} , 1 , "" , 1);
	print "-----\n";
	system("pstree $pid");
	sleep(3);
}







package DeckInfo;
sub new
{
        my $class = shift;
        my $pid = shift;
	my $log_f = shift;
	
	my $self = {pid => $pid}; 
	$self->{ptree} = {};
	$self->{ctime} = 0;
	unlink($log_f) if (-f $log_f);
	$self->{logf} = $log_f;
	bless $self, $class;
}

sub find_children
{
        my $self = shift;
        my $pid_check;
	my $space_num = 2;

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
		my @local_child = ();
                my $pname = $t_hash{$ppid}->{'cmndline'};
                $pid_check .= "$ppid - [$pname]\n";

                for my $cid (keys %t_hash) {
                        if ($t_hash{$cid}->{ppid} == $ppid) {
                                push(@child , $cid);
                                push(@local_child , $cid);
                        }
                }
		$ptree{$ppid}->{local_child} = \@local_child;
        }

        $self->{child} = \@child;
	$self->update_ptree(\%ptree);
	print Data::Dumper->Dump([\%ptree]);
}

sub update_ptree
{
	my $self = shift;
	my $ntree = shift;
	my $ptree = $self->{ptree};
	if (scalar keys %$ptree) {
	  if (  $self->{ctime} != $ptree->{$self->{pid}}->{ctime}  ) {
		my $log_f;
		open($log_f , ">>$self->{logf}");
		select $log_f;
		my $e = "";
		$self->print_ptree($self->{pid}, 1, "", 1);
		select STDOUT;
		close($log_f);
		$self->{ptree} = {};
		$ptree = $self->{ptree};
		$self->{ctime} = $ntree->{$self->{pid}}->{ctime};
	  }
	} else {
		$self->{ctime} = $ntree->{$self->{pid}}->{ctime};
	}
	for my $pid (keys %$ntree) {
		$ptree->{$pid} = $ntree->{$pid};
	}
}

sub print_ptree
{
	my $self = shift;
	my $pid = shift;
	my $level = shift;
	my $padding = shift;
	my $last_one = shift || 0;
	my $prefix = "  " x ($level - 1);
	my $pt = $self->{ptree}->{$pid};
	my @local_children = @{$pt->{local_child}};
	
	my $cmndline = $pt->{cmndline};
	if (length($cmndline) > 80 - 2*$level - 8) {
###print "[$cmndline]\n";
		my @cmnds = split ' ', $cmndline;
		my @cmnd1st = split '/' , $cmnds[0];
		shift @cmnds;
		$cmndline = "..." . $cmnd1st[$#cmnd1st] . ' ' . join(' ' , @cmnds);
	}
	if (length($cmndline) > 80 - 2*$level - 8) {
		$cmndline = substr($cmndline , 0 , 80 - 2*$level - 8) . "...";
	}


	if ($level == 0 ) {
		my $dd = `date`;
		print "\n\n\n" , $dd;
	}

	$prefix = "";
	if ($level > 1) {
		$prefix .= "  ";
	}
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
	print $heading , $pid , "  " , $cmndline , "\n";

	$heading = $prefix;
	
	unless (($#local_children<0) && $last_one) {
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
	
	print $heading, " " x (1+length($pid)) , "ctime [$pt->{ctime}] time [$pt->{time}] \n";
	if ($level) {
		$prefix .= "  ";
	}
	for my $cid (@local_children) {
		if ($cid == $local_children[$#local_children]) {
			$self->print_ptree($cid , $level + 1 , $padding."0" , 1);
		} else {
			$self->print_ptree($cid , $level + 1 , $padding."1" , 0);
		}
	}
}


1;
