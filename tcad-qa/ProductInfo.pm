package ProductInfo;
use strict;
use warnings;
require Exporter;
our @ISA = qw(Exporter);
our @EXPORT = qw(get_pv );

sub get_pv
{
	my $pid = $_[0];
	my $exe = $_[1] || "none";

	my ($cmd_name , $cmd_ver , $cmd_platform , $cmd_exe) = ('NotStd','XXX','YYY','ZZZ');
	my @pslines = `ps -p $pid -o pid,cmd`;

	for my $oneline (@pslines) {
		chomp $oneline;
#		print "[$oneline]\n";
		if ( $oneline =~ /^ *(\d+) +(\S*)/ ) {
#			print "matched [$1] [$2]\n";
			my $cmd = $2;
			my @cmd_segs = split /\// , $cmd;
			for (my $i = 0 ; $i <= $#cmd_segs ; $i++) {
				my $seg = $cmd_segs[$i];
#				print "seg [$seg] total [$#cmd_segs]\n";
				if ($seg eq "lib") {
					last if ($#cmd_segs < ($i+4)); 
					$cmd_name = $cmd_segs[$i+1];
					$cmd_ver  = $cmd_segs[$i+2];
					$cmd_platform = $cmd_segs[$i + 3];
					$cmd_exe  = $cmd_segs[$i+4];
					last;
				}
			}
		}
	}

	return (	$cmd_name , $cmd_ver , $cmd_platform , $cmd_exe);
}

1;
