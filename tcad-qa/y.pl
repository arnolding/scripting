use Date::Parse;
my @config = (
        '.config/Silvaco, Inc.',
        '.config/silvaco',
        '.silvaco'
        );


for my $p (@config) {
	do_one($p);
}

print "pid and comma test\n";
my $pid="x123 , 456,789";
my $target = 456;
my @pid_array = split /,/,$pid;
for my $p1 (@pid_array) {
	print "==" , "[$p1]\n";
	if ($target == $p1) {
		print "yes they are equal\n";
	} else {
		print "no\n";
	}
}

my $jj = join(',',@pid_array);
print "joined [$jj]\n";



my $recent = "/build/qa/tests/last";

if ( -f $recent) {
	print "exist\n";
} else {
	print "NONO\n";
}

my $line = "893692  ";
$line = "Fri Apr 15 05:30:28 CST 2022";
my $single_dt = str2time($line);
if (defined $single_dt) {
	my ($ss,$mm,$hh,$day,$month,$year,$zone) = strptime($line);
	print "[$ss,$mm,$hh,$day,$month,$year,$zone]\n";
}








sub do_one{
my $small_path=shift;
my $home = $ENV{HOME};
print "home=[" , $ENV{HOME} , "\n";
my $path=$home . '/' . $small_path;
if (-e $path) {
	print "[" , $path , "] exists\n";
	my $res = `rm -r \"$path\"`;
	print "$res\n";
}
if (-e $path) {
	print "after remove:\n";
	print "[" , $path , "] exists\n";
} else {
	print "it's gone\n";
}
}
