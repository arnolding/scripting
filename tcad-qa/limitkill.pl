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
use ProcWin;
use Getopt::Long;

my $nokill = 0;
my $guir_pid = $ARGV[0];
if ($guir_pid < 0) {
	$guir_pid = -1 * $guir_pid;
	$nokill = 100;
}
my $pwin = new ProcWin($guir_pid);

while(1) {
$pwin->find_children_n_windows();
$pwin->save_children_tmp();
#print Data::Dumper->Dump([$pwin]);

print $pwin->print_ptree($guir_pid , "", 1);
print $pwin->print_windows_str();
last if ($nokill > 2);
if ( $pwin->kill_overlimit(60) == 0) {
	$nokill++;
} else {
	$nokill = 0;
}
$pwin->find_children_n_windows();
print $pwin->print_ptree($guir_pid , "", 1);


system("sleep 6");
}

