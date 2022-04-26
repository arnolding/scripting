#!/usr/bin/perl
use strict;
use warnings;
use File::Find;
use Date::Parse;
use Data::Dumper;
use Getopt::Long;
$Data::Dumper::Sortkeys = 1;
$Data::Dumper::Deepcopy = 1;
my %ps_shot_duration;

my $ptree_head = "/build/arnoldh/reg/tests";
$ptree_head = "/Users/arnold/MyWork/Silvaco/twtcad01" if (! -e $ptree_head);
my $run_tag = "last";
my $help;
GetOptions(
        'tag=s' => \$run_tag,
        'help' => \$help
        );
if ($help) {
	print "$0 --tag <run-tag>\n";
	print "example:\n";
	print "$0 --tag last\n";
	exit;
}

my $output_path = "$ptree_head/$run_tag/output";
die "$output_path does not exist" if (! -d $output_path);

my @ptree_folder_path = ($output_path);
my $ptree_aref = [];
my @fname_diff = ();
#/build/arnoldh/reg/tests/tcad-0929-0/cases/4.2.9.R/*/*/.*/deckbuild/rto.xml";
find(\&find_file_callback, @ptree_folder_path);
my $ps_set = extract_ptree($ptree_aref);

my $reg_summary = get_ps_summary($ps_set);

#open P , "> reg_ptree.set";
#print P Data::Dumper->Dump($ps_set);
#close P;
open P , "> $output_path/reg_ptree.summary";
print P Data::Dumper->Dump($reg_summary);
close P;
open P , "> $output_path/fname_diff.log";
print P Data::Dumper->Dump([\@fname_diff]);
close P;

my $sum_table = summary_in_table($reg_summary);
open P, "> $output_path/reg_ptable.summary";
print P "case, process, size(MB), ctime, time, elapse, calc_ctime, adj_ctime" , "\n";
for my $ent1 (@$sum_table) {
    #for (@$ent1) {
    #    print P $_ , ",";
    #}
    if (scalar @$ent1 > 6) {
        printf P "%s,%s,%d,%d,%d,%d,%d,%d\n" ,
            $ent1->[0],$ent1->[1],
            int($ent1->[2]), int($ent1->[3]),
            int($ent1->[4]), int($ent1->[5]),
            int($ent1->[6]), int($ent1->[7]);
    } else {
        printf P "%s,%s,%d,%.2f,%.2f,%d\n" ,
            $ent1->[0],$ent1->[1],
            int($ent1->[2]), $ent1->[3],
            $ent1->[4], int($ent1->[5]);
    }
    #print P "\n";
}
close P;
#print Data::Dumper->Dump([\%ps_shot_duration]);

sub flat_ptree
{
    my $ps_tree = shift;
    my $case = shift;
    my @table = ();
    push @table , [ $case, $ps_tree->{fname}, $ps_tree->{size} , $ps_tree->{ctime} , $ps_tree->{time}, $ps_tree->{end} - $ps_tree->{start}];
    for my $p1 (@{$ps_tree->{child}}) {
        push @table , @{flat_ptree($p1 , $case)};
    }
    
    return \@table;
}
sub summary_in_table
{
    my $reg_sum = shift;
    my @ps_list;
    my $pre_ctime = 0;
    for my $c1 (@$reg_sum) {
        my $case = $c1->{case};
        my @table_in_case1 = @{flat_ptree($c1->{ps_summary} , $case)};
        my $calc_ctime = 0;
        for my $i (1 .. $#table_in_case1) {
            $calc_ctime += $table_in_case1[$i]->[3] + $table_in_case1[$i]->[4];
        }
        print $calc_ctime , "\n";
        push @{$table_in_case1[0]} , $calc_ctime;
        push @{$table_in_case1[0]} , $table_in_case1[0]->[3] - $pre_ctime;
        $pre_ctime = $table_in_case1[0]->[3];
        push @ps_list, @table_in_case1;
    }
    return \@ps_list;
}

sub get_ps_summary
{
    my $ps_set = shift;
    my @tmp_ps;
    for my $ps1 (@$ps_set) {
        my $href = get_ps1_summary($ps1);
        push @tmp_ps , $href;
    }
    
    my @reg_ps = sort {$a->{first_epoch} <=> $b->{first_epoch}} @tmp_ps;
    
    return \@reg_ps;
}

sub fname_diff_in
{
    my $from_name = shift;
    my $to_name = shift;
    
    my $same = 0;
    for my $fdiff (@fname_diff) {
        if (($fdiff->[0] eq $from_name) and
            ($fdiff->[1] eq $to_name) ) {
            $fdiff->[2]++;
            $same = 1;
            last;
        }
    }
    
    if ($same == 0) {
        push @fname_diff , [$from_name , $to_name , 1];
    }
}
sub tag_end
{
    my $ptree = shift;
    my $epoch = shift;
    
    $ptree->{end} = $epoch if (not defined $ptree->{end});
    my @child_new = @{$ptree->{child}};
    for my $c1 (@child_new) {
        tag_end($c1 , $epoch);
    }
}
sub ps_running
{
    my $ptree_pre = shift;
    my $ptree = shift;
    my $ps_epoch = shift;
    my $cover = 1;
    
    # should be exactly same
    my @same_f = ("pid" , "ppid" , "start");
    # large or equal
    my @le_f = ("ctime" ,  "time");
    # should be same (string)
    my @str_f = ("fname");
    for my $s (@same_f) {
        die "missing fields $s" if ((not exists $ptree_pre->{$s}) or 
                                    (not exists $ptree->{$s}));
        return "diff ps" if ($ptree_pre->{$s} != $ptree->{$s});
    }
    for my $s (@le_f) {
        die "missing fields $s" if ((not exists $ptree_pre->{$s}) or 
                                    (not exists $ptree->{$s}));
        return "reverse ps" if ($ptree_pre->{$s} > $ptree->{$s});
    }
    for my $s (@str_f) {
        die "missing fields $s" if ((not exists $ptree_pre->{$s}) or 
                                    (not exists $ptree->{$s}));
        if ($ptree_pre->{$s} ne $ptree->{$s}) {
            fname_diff_in($ptree_pre->{$s},$ptree->{$s});
        }
               
#        return "diff ps" if ($ptree_pre->{$s} ne $ptree->{$s});
    }
    
    my @child_pre = @{$ptree_pre->{child}};
    my @child_new = @{$ptree->{child}};
    
    my $found;
    for my $c_pre1 (@child_pre) {
        if ( defined $c_pre1->{end}) {
            unshift @{$ptree->{child}} , $c_pre1;
            next;
        }
        $found = 0;
        for my $c_new1 (@child_new) {
            if ( ( $c_new1->{pid} == $c_pre1->{pid}) and
                ($c_new1->{start} == $c_pre1->{start}) ) {
                die if (defined $c_pre1->{end});
                $found = 1;
                if (ps_running($c_pre1 , $c_new1, $ps_epoch) ne "OK") {
                    print Data::Dumper->Dump([$c_pre1 , $c_new1]);
                    die;
                }
                next;
            }
        }
        if ($found == 0) {
            tag_end($c_pre1 , $ps_epoch);
            unshift @{$ptree->{child}} , $c_pre1;
        }
    }
    return "OK";
}
sub get_ps1_summary
{
    my $ps1 = shift;
    my $ps_case = $ps1->{fname};
    my @ps_shot = @{$ps1->{ps_tree_array}};
    my $ps_one_file = {};
    my $aref = [];
    my $first_epoch = -1;
    my $ps_summary;
    my $ps_lead_pre;
    my $ps_lead;
    for my $ps_shot1 (@ps_shot) {
        #warn "$ps_case";
        my $shot_epoch = $ps_shot1->{shot_epoch};
        $first_epoch = $shot_epoch if (($first_epoch < 0) or ($first_epoch > $shot_epoch));
        
        #warn "$ps_case  $shot_epoch";
        $ps_lead = make_tree($ps_shot1->{ps_tree});
        if ( defined $ps_lead_pre) {
            if (ps_running($ps_lead_pre , $ps_lead, $shot_epoch) ne "OK") {
                    print Data::Dumper->Dump([$ps_lead_pre , $ps_lead]);
                    die;
            }
        }
        $ps_lead->{end} = $shot_epoch;
        $ps_lead_pre = $ps_lead;
        
        
#        push @$aref , {
#            tag_epoch => $shot_epoch,
#            ps_lead => $ps_lead
#            };
#        update_summary($ps_summary, $ps_lead);
    }
    $ps_one_file = {
        case => $ps_case,
        first_epoch => $first_epoch,
#        ps_all => $aref
        ps_summary =>   $ps_lead
        };
    return $ps_one_file;
}

sub get_ps
{
    my $target = shift;
    my $tree = shift;
    
    my $found;
    for my $c1 (@{$tree->{child}}) {
        $found = get_ps($target, $c1);
        if (defined $found ) {
            last;
        }
        if ( ($target->{pid} == $c1->{pid}) and
             ($target->{start} == $c1->{pid}) ) {
            $found = $c1;
            last;
        }
    }
    
    return $found;
}
sub update_summary
{
    my $summary = shift;
    my $plead = shift;
    
    update_1data($summary , $plead);
    if (exists $summary->{simulator}) {
        for my $s_child (@{$summary->{simulator}}) {
            my $s_update = get_ps($s_child, $plead);
            if (defined $s_update) {
                update_1data($s_child, $s_update);
            }
        }
    }
}
sub update_1data
{
    my $origin = shift;
    my $newdata = shift;
    
    if (defined $origin) {
        die "process lead, deckbuild goes mad" if (
                ($origin->{pid} != $newdata->{pid}) or
                ($origin->{ppid} != $newdata->{ppid}) or
                ($origin->{start} != $newdata->{start}) or
                ($origin->{fname} != $newdata->{fname})
            );
        die "process lead time and size should NOT be decreased" if (
                ($origin->{time} > $newdata->{time}) or
                ($origin->{size} > $newdata->{size}) or
                ($origin->{ctime} > $newdata->{ctime})
            );
            
        $origin->{time} = $newdata->{time};
        $origin->{size} = $newdata->{size};
        $origin->{ctime} = $newdata->{ctime};
    } else {
        $origin->{pid} = $newdata->{pid};
        $origin->{ppid} = $newdata->{ppid};
        $origin->{start} = $newdata->{start};
        $origin->{fname} = $newdata->{fname};
        $origin->{time} = $newdata->{time};
        $origin->{size} = $newdata->{size};
        $origin->{ctime} = $newdata->{ctime};
    }
}

## make_tree:
## input array ref of ps result in one shot sampling
## output one process as top, should be deckbuild, and with @child
## only the following elements are stored and return
##      pid,
##      ppid,
##      child - array of child processes,
##      utime       user mode time (1/100s of seconds) (Linux only)
##      stime       kernel mode time                   (Linux only)
##      time        user + system time                
##      cutime      child utime                        (Linux only)
##      cstime      child stime                        (Linux only)
##      ctime       child user + system time
##      size        virtual memory size (bytes)
##      fname
sub make_tree
{
    my $ps_tree_aref = shift;
    my @tA = @$ps_tree_aref;
    my @transfer_fields = ("pid", "ppid", "time",
                        "ctime", "size", "fname", "start",
                        "cmndline");
    my @pre_simplified_ps = ();
    for my $i (0 .. $#tA) {
        my $ps1 = $tA[$i];
        my $ps1x = {};
        if ($ps1->{ctime} != ($ps1->{cutime} + $ps1->{cstime})) {
            die "child CPU time not correct for pid $ps1->{pid}";
        }
        $ps1->{ctime} /= 1000000.0; 
        if ($ps1->{time} != ($ps1->{utime} + $ps1->{stime})) {
            die "self CPU time not correct for pid $ps1->{pid}";
        }
        $ps1->{time} /= 1000000.0;
        $ps1->{size} /= (1024*1024);
        for (@transfer_fields) {
            $ps1x->{$_} = $ps1->{$_};
        }
        $ps1x->{has_parent} = 0;
        $ps1x->{child} = [];
        push @pre_simplified_ps , $ps1x;
    }
    my @simplified_ps = sort {$a->{start} <=> $b->{start}} @pre_simplified_ps;
    
    my $ptree_lead;
    for my $i (0 .. $#simplified_ps) {  
        my $ps1 = $simplified_ps[$i];
        my @child;
        for my $j (0 .. $#simplified_ps) {
            my $ps2 = $simplified_ps[$j];
            next if ($ps2->{start} < $ps1->{start});
            next if ($ps2->{has_parent});
            if ($ps2->{ppid} == $ps1->{pid}) {
                push @child , $ps2;
                $ps2->{has_parent} = 1;
            }
        }
        $ps1->{child} = \@child;
    }
    $ptree_lead = $simplified_ps[0];
    for my $i (1 .. $#simplified_ps) {
        my $ps1 = $simplified_ps[$i];
        if ($ps1->{has_parent} ==0) {
            if (($ps1->{cmndline} eq "/bin/csh") or
                ($ps1->{cmndline} eq "sh") ) {
                push @{$ptree_lead->{child}}, $ps1;
            } else {
                    die "floating process pid=$ps1->{pid}, no parent found";
            }
        }
    }
    for my $i (0 .. $#simplified_ps) {
        my $ps1 = $simplified_ps[$i];
        delete $ps1->{cmndline};
        delete $ps1->{has_parent};
    }
    
    
    return $ptree_lead;
}

sub extract_ptree
{
    my $ptree_aref = shift;
    my ($total , $equal , $notequal, $ps_set) = (0,0,0, []);
    for my $ptree1 (@$ptree_aref) {
        print $ptree1 , "\n";
        my ($t , $e , $ne , $ps_a) = extract1tree($ptree1);
        $total += $t;
        $equal += $e;
        $notequal += $ne;
        push @$ps_set , { fname => substr($ptree1 , length($ptree_head)),
                        ps_tree_array => $ps_a};
    }
    
    print "t: $total  e: $equal n: $notequal\n";
    return $ps_set;
}

sub extract1tree
{
    my $tname = shift;
    my ($total , $equal , $notequal) = (0,0,0);
    my $ps_begin = 0;
    my $ps_href = {};
    my $ps_shot_aref = [];
    my $ps_shot_epoch = -1;
    my $ps_aref = [];
    my $t_epoch0 = -1;
    open T , "<$tname";
    while (<T>) {
        chomp;
#========================================Tue Sep 29 10:44:09 2020=
        if (/^={40}(\w+ \w+\s+\d+ \d+:\d+:\d+ \d+)= (\d+)/) {
            my $at_time = str2time($1);
            my $t_epoch = $2;
            #print "timetag = [$1] [$at_time] [$t_epoch]\n";
            if ($at_time == $t_epoch) {
                $equal++;
            } else {
                $notequal++;
            }
            $total++;
            
            if ($t_epoch0 >0) {
                my $delta_t = $t_epoch - $t_epoch0;
                #if ( exists $ps_shot_duration{$delta_t} ) {
                    $ps_shot_duration{$delta_t}++;
                #} else {
                #    $ps_shot_duration{$delta_t} = 1;
                #}
                
                $t_epoch0 = $t_epoch;
            }
            $t_epoch0 = $t_epoch;
            
            if (scalar @$ps_shot_aref > 0) {
                push @$ps_aref , { shot_epoch => $ps_shot_epoch,
                                ps_tree => $ps_shot_aref};
                $ps_shot_aref = [];
            }
            $ps_shot_epoch = $at_time;
        } elsif (/^>{40}$/) {
            $ps_begin = 1;
        } elsif (/^<{40}$/) {
            $ps_begin = 0;
            push @$ps_shot_aref , $ps_href;
            $ps_href = {};
        } else {
            die "Should see shot begin and ps begin in $tname" if ($ps_begin == 0);
            if (/(\w+)\s+(\S+)/) {
                $ps_href->{$1} = $2;
            } elsif(/(\w+)\s+$/) {
                $ps_href->{$1} = "NA";
            } else {
                die "Should be keyword ";
            }
        }
    }
    if (scalar @$ps_shot_aref > 0) {
            push @$ps_aref , { shot_epoch => $ps_shot_epoch,
                                ps_tree => $ps_shot_aref};
    }
    close T;
    return ($total , $equal , $notequal , $ps_aref);
}


sub find_file_callback
{
    my $fname = $File::Find::name;
    if ($fname =~ /ptree\.log$/ ) {
        #print $fname , "\n";
        push @$ptree_aref , $fname;
    }
}
