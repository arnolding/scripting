#!/usr/local/bin/python3

import subprocess
import os
import json
import time

from regLog import logx

from c import Ui_MainWindow
from ps import ps
from installation import installation
from reg_wm import wm
from deepdiff import DeepDiff
from datetime import datetime
import errno

class oneDeck():
    def __init__(self,exeName, deckFullPath):
        self.exeName = exeName
        self.fullPath = deckFullPath
        self.dir = os.path.dirname(self.fullPath)
        self.shortName = os.path.basename(self.fullPath)
        self.outFile = self.shortName.split('.')[0] + '.outf'

    def getWindow(self):
        wins = wm.get_windows()
        for x in wins.keys():
            if int(wins[x]["_NET_WM_PID"]) == self.deck_run.pid:
                self.wid = x
                print(x)
                break
            else:
                self.wid = 0
        return self.wid
    def run(self):
        os.chdir(self.dir)
        self.deck_run = subprocess.Popen([self.exeName, self.shortName, "-outfile", self.outFile])
        while self.getWindow() == 0:
            print("wait window\n")
            time.sleep(1)


if __name__ == "__main__":
    inst = installation("deckbuild")
    print(inst.tool_path)
    print(inst.examples_path)
    print("decks number: ", len(inst.decks))



    outs = ""
    errs = ""
    wins0 = {}
    wins = {}
    proc0 = {}
    procs = ps.get_processes(os.getpid())

    i = oneDeck("deckbuild" , inst.decks[0])
    i.run()
    print("after invoke pid=", i.deck_run.pid , " wid=" , i.wid)
    subprocess.Popen(["xraise", i.wid])
    subprocess.Popen(["xsendkey", "-window", i.wid, "F9"])
    time.sleep(10)
    subprocess.Popen(["xsendkey", "-window", i.wid, "Control+q"])


    '''
        while (deck_run.returncode == None):
            print("in while, pid=", deck_run.pid)
            wins = wm.get_windows()
            windiff = DeepDiff(wins0, wins)
            if (windiff):
                logx(json.dumps(wins, indent="\t"))
                logx("windiff -- " + windiff.pretty())
                wins0 = wins
    
            procs = ps.get_processes(os.getpid())
            psdiff = DeepDiff(proc0, procs, math_epsilon=10)
            if (psdiff):
                logx(json.dumps(procs, indent="\t"))
                logx("psdiff -- " + psdiff.pretty())
                proc0 = procs
    
            try:
                outs, errs = deck_run.communicate(timeout=10)
            except subprocess.TimeoutExpired:
                # outs, errs = deck_run.communicate(timeout=5)
                print("expired, out=", outs, " errs=", errs)
            try:
                print("deckbuild pid ", procs["deckbuild"])
            except:
                print("No procs deckbuild")
            deckbuild_win = 0
            deckbuild_win_l = [x for x in wins.keys() if int(wins[x]["_NET_WM_PID"]) == procs["deckbuild"]["pid"]]
            if (len(deckbuild_win_l) == 1):
                deckbuild_win = deckbuild_win_l[0]
                print("deckbuild wid ", deckbuild_win)
                subprocess.Popen(["xraise", deckbuild_win])
                subprocess.Popen(["xsendkey", "-window", deckbuild_win, "F9"])
    
        wins = wm.get_windows()
        windiff = DeepDiff(wins0, wins)
        if (windiff):
            logx(json.dumps(wins, indent="\t"))
            logx("windiff -- " + windiff.pretty())
            wins0 = wins
        print("out of while out=", outs, " errs=", errs)
    '''

