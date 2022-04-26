#!/usr/bin/perl
use strict;
use warnings;
use Report;
use Data::Dumper;

my $rr = Report::parse_monitor("/home/arnold/reg/tests/last/output-0414-0/running/ganfetex20/monitor.log");
Report::analyze_monitor($rr);

print Data::Dumper->Dump([$rr]);
#my $s= $rr->[0]->{
