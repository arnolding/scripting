#!/home/arnoldh/perl520/bin/perl
use strict;
use warnings;
use utf8;
use Getopt::Std;
use XML::LibXML;



open DATA, "<"  , "/build/qa/tests/tcad-1116-0/cases/athena_advanced_diffusion/.advdifex03/deckbuild/rto.xml";

my $rto = "";
while (<DATA>) {
	$rto .= $_;
}
close DATA;

my ($user_time , $sys_time, $elapse_time, $cpu_util);
	my $outtext = $rto;
	if ($outtext =~ /([\d\.]+)u ([\d\.]+)s ([\d\.:]+) ([\d\.]+)%/) {
		$user_time = $1;
		$sys_time = $2;
		$elapse_time = $3;
		$cpu_util = $4;
	}
	print "usertime $user_time systime $sys_time elapse $elapse_time CPU util $cpu_util\n";

