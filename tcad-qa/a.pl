
my $ps_lead = "abcd, efg, hi, ";
print "old [$ps_lead]\n";
substr($ps_lead , -2 , 2) = ";";

print "[$ps_lead]\n";

