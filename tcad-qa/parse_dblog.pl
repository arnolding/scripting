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

my $p1 = XML::LibXML->new();
my $dom = $p1->parse_string($rto);

my ($user_time , $sys_time, $elapse_time, $cpu_util);
for my $top_nodes ($dom->findnodes('.//*')) {
	my $outtext = $top_nodes->textContent();
	if ($outtext =~ /([\d\.]+)user/) {
		$user_time = $1;
	}
	if ($outtext =~ /([\d\.]+)system ([\d\.:]+)elapsed ([\d\.]+)%CPU/) {
		$sys_time = $1;
		$elapse_time = $2;
		$cpu_util = $3;
	}
}
	print "usertime $user_time systime $sys_time elapse $elapse_time CPU util $cpu_util\n";

