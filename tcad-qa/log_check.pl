#!/usr/bin/perl
#use strict;
#use warnings;

my $ex = "/build/arnoldh/base_tests/examples";
chdir($ex);
my @decks = glob "*/*.in";
my @dones = glob "*/*.done";

for my $case (@decks) {
#	print $case , "\n";

	my @path_sep = split /\// , $case;
	
	my $case_head = $path_sep[0];
	my $case_path = $case_head;
	my $last_seg = $path_sep[$#path_sep];
	my $deckfull = $last_seg;
	$last_seg =~ /(.*)\.in/;
	my $DeckName = $1 || 'dummy';

	my $logf = $DeckName . ".log";
	my $done = $DeckName . ".done";
	my $new = $DeckName . ".new";
	my $l_str = "";
	my $d_str = "";
	my $n_str = "";
	my $d_ts = 0;
	my $n_ts = 0;
	my $delta_ts = 0;
	
	if (-f "$case_path/$logf") {
		my $epoch_timestamp = (stat("$case_path/$logf"))[9];
		my $timestamp       = localtime($epoch_timestamp);
		$l_str = "$logf, " . $timestamp . " ";
	} else {
		$l_str = "$log, NA";
	}

	if (-f "$case_path/$done") {
		my $epoch_timestamp = (stat("$case_path/$done"))[9];
		$d_ts = $epoch_timestamp;
		my $timestamp       = localtime($epoch_timestamp);
		$d_str = "$done, " . $timestamp . " ";
	} else {
		$d_str = "$done, NA";
	}
	if (-f "$case_path/$new") {
		my $epoch_timestamp = (stat("$case_path/$new"))[9];
		$n_ts = $epoch_timestamp;
		my $timestamp       = localtime($epoch_timestamp);
		$n_str = "$new, " . $timestamp . " ";
	} else {
		$n_str = "$new, NA";
	}

	if (($d_ts > 0) and ($n_ts > 0)) {
		$delta_ts = $d_ts - $n_ts;
	} else {
		$delta_ts = 0;
	}

	print "$case,	$l_str,	$d_str,	$delta_ts\n";
}
