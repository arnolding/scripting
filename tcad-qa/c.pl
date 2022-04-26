sub machine_info
{
	my %i_hash;
    my $lscpu = `lscpu`;
    my @cpu_info = split '\n', $lscpu;
    for my $i1 (@cpu_info) {
		my @k = split ':' , $i1;
		for (@k) {
			s/^\s+|\s+$//g ; 
		}
		$i_hash{$k[0]} = $k[1];
    }

	return \%i_hash;
}

my $cpu_href = machine_info();

for my $key (keys %$cpu_href) {
	print "$key >> $cpu_href->{$key}\n";
}
