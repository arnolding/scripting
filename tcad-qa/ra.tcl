#!/usr/bin/wish
package require json

set fp [open "/home/arnold/Desktop/1225_0/reg_result_29351.json" r]
set array_tag {case_result ptree}
set data [read $fp]
close $fp

set reg_res [::json::json2dict $data]

foreach id [dict keys $reg_res] {
	puts "id $id"
}

puts "================================"
set case_res [dict get $reg_res case_result]

set res1 [lindex $case_res 0]
foreach id [dict keys $res1] {
	puts "id $id"
}



proc locate_dat { lpath } {
	global reg_res

	set cur_dat $reg_res
	set use_list 0
	foreach p1 [lrange $lpath 0 end] {
puts "p1 $p1 and use_list $use_list"
		if {$use_list} {
			set cur_dat [lindex $cur_dat $p1]
		} else {
			set cur_dat [dict get $cur_dat $p1]
		}
		if { [is_list $p1] } {
			set use_list 1
		} else {
			set use_list 0
		}
	}
	return $cur_dat
}
			
		

proc is_list {tag} {
	global array_tag
	foreach ta1 [lrange $array_tag 0 end ] {
		if { $tag == $ta1 } {
			return 1
		}
	}
	return 0
}

set a [locate_dat "case_root"]

