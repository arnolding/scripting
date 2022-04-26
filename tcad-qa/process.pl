#!/home/arnoldh/perl520/bin/perl
use strict;
use warnings;

use Proc::ProcessTable;
my $current = time();
print "current epoch time: [$current]\n";
my $t = new Proc::ProcessTable;
foreach my $p (@{$t->table}) {
  print "--------------------------------\n";
  foreach my $f ($t->fields){
	if ($p->{$f}) {
    print $f, ":  ", $p->{$f}, "\n";
	}
  }
 }
