#!/usr/local/bin/python3
#
from datetime import datetime
import errno
import os


def silentremove(filename):
    try:
        os.remove(filename)
    except OSError as e:  # this would be "except OSError, e:" before Python 2.6
        if e.errno != errno.ENOENT:  # errno.ENOENT = no such file or directory
            raise  # re-raise exception if a different error occurred


def logx(s=None):
    if (s):
        currentDateAndTime = datetime.now().strftime("%m-%d %H:%M:%S")

        f = open("log.txt", "a")
        f.writelines(["timetag\t", currentDateAndTime, "\n", s, "\n"])
        f.close()
    else:
        silentremove("log.txt")
