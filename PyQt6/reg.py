#!/usr/local/bin/python3

import subprocess
import os
import json
from regLog import logx
from c import Ui_MainWindow
from ps import ps
from installation import installation
from reg_wm import wm
from deepdiff import DeepDiff
from runOneDeck import oneDeck
from datetime import datetime
import errno


inst = installation("deckbuild")
print(inst.tool_path)
print(inst.examples_path)
print("decks number: " , len(inst.decks))

#reg_ui = Ui_MainWindow()
#reg_ui.installPath = "ABC"
#reg_ui.show()
#wins = wm.get_windows()
#print(json.dumps(wins['0x2000003']["_NET_WM_NAME"]))
#print(wins['0x2000003']["_NET_WM_PID"])

outs = ""
errs = ""
wins0 = {}
wins = {}
proc0 = {}
procs = ps.get_processes(os.getpid())
for i in inst.decks:
	r1 = oneDeck("deckbuild", i)
	r1.run()
	subprocess.Popen(["xraise", r1.wid])
	subprocess.Popen(["xsendkey", "-window", r1.wid, "F9"])

	while (r1.deck_run.returncode == None):
		print("in loop, pid=", r1.deck_run.pid, " wid=", r1.wid)
		currentDateAndTime = datetime.now().strftime("%m-%d %H:%M:%S")

		'''
		wins = wm.get_windows()
		windiff = DeepDiff(wins0, wins)
		if (windiff):
			logx(json.dumps(wins, indent = "\t"))
			logx("windiff -- " + windiff.pretty())
			wins0 = wins
		'''
		procs = ps.get_processes(os.getpid())
		psdiff = DeepDiff(proc0,procs,math_epsilon=10)
		if (psdiff):
			#logx(json.dumps(procs, indent = "\t"))
			print(currentDateAndTime + "psdiff -- \n" + psdiff.pretty())
			proc0 = procs
		'''
		try:
			outs, errs = r1.deck_run.communicate(timeout=10)
		except subprocess.TimeoutExpired:
			#outs, errs = deck_run.communicate(timeout=5)
			print("expired, out=", outs , " errs=", errs)
	
	wins = wm.get_windows()
	windiff = DeepDiff(wins0, wins)
	if (windiff):
		logx(json.dumps(wins, indent = "\t"))
		logx("windiff -- " + windiff.pretty())
		wins0 = wins
	print("out of while out=", outs , " errs=", errs)
	'''


