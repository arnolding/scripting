#!/home/arnoldh/tcltk8610/bin/wish

package require Tk


wm title . "My test"
wm geometry . +300+300

label .l1 -text "Search for:"
entry .e1
.e1 insert 0 "Initial value"
text .text -yscrollcommand ".scroll set" -setgrid true \
	-width 60 -height 24 -font fixedFont -wrap word
ttk::scrollbar .scroll -command ".text yview"

grid .l1 -row 0 -column 0
grid .e1 -row 0 -column 1
grid .text -row 1 -column 0
grid .scroll -row 1 -column 1


set fp [open "/build/arnoldh/log/1225_0/reg_result_29351.log" r]
set data [read $fp]
.text insert 0.0 $data
