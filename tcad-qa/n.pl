use Date::Parse;
use warnings;
use POSIX qw(strftime);
my $newer="/build/arnoldh/tcad-qa/rrr.log";
chdir("/build/arnoldh/tcad-qa");
my $count = 0;
for (@nn) {
chomp;
print "$count , [$_]\n";
$count++;
}
#chdir("/home/arnold/reg/tests/last/cases/4.2.12.R/bjt/bjtex01");
#system("deckbuild -run bjtex01.in");
my $dd = `date`;
chomp $dd;
print "dd [$dd]\n";
my $tt = str2time($dd);
print "tt = [$tt]\n";
$tt = str2time("Tue Mar  1 11:23:45");
if ($tt) {
print "hms = [$tt]\n";
} else {
print "hms no\n";
}
my $start_time = strftime("%a %b %e %H:%M:%S %Z %Y", localtime);
print "start time [$start_time]" , "\n";
$tt = str2time($start_time);
print "tt = [$tt]\n";

my $top = `top -b -n 1 | head -17`;


print "=" x 60 , "\n";
print $top;
