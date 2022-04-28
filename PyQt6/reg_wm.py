# Date 2023/1/16
# This is to define the functions to interact with windows manager
# usage:

import subprocess
from pprint import pprint


class wm:
	def prop2dic(prop):
		plist_dic = {}
		continuation = 0
		key = ""
		val = ""
		plist = prop.split("\n")
		for p1 in plist:
			#print ("[" , p1 , "]")
			if (len(p1) ==0):
				pass
			elif (p1 == "\u001b[0m"):
				val = val + p1 + "\n"
				plist_dic[key] = val
			elif (p1[0] == "\t"):
				val = val + p1 + "\n"
				plist_dic[key] = val
			elif ((pos := p1.find(" = ")) > 0):
				key = p1[0:pos]
				val = p1[pos+3:]
				plist_dic[key] = val
				if (len(val) > 1 and (val[-1] == ":")):
					continuation = 1
			elif (p1[-1] == ":"):
				key = p1[0:-1]
				val = ""
				plist_dic[key] = val
			elif ((pos := p1.find(": ")) > 0):
				key = p1[0:pos]
				val = p1[pos+2]
			else:
				print("ERROR " , p1)
		return plist_dic
	def get_windows():
		NET_STACK_STR = "_NET_CLIENT_LIST(WINDOW): window id #"
		wins = {}
		rootwin_prop = subprocess.run(["xprop", "-root"], capture_output=True, text=True).stdout.split("\n")
		for i in rootwin_prop:
			if ((pos := i.find(NET_STACK_STR)) >=0):
				win_stack = i[pos+len(NET_STACK_STR):].strip()
				if (len(win_stack) == 0) :
					continue
				win_stack_list = win_stack.split(",")
				for j in win_stack_list:
					wid = j.strip()
					#print("===================================window id" , wid)
					win_prop = subprocess.run(["xprop", "-notype", "-id", wid], capture_output=True, text=True).stdout.strip("\n")
					wins[wid] = wm.prop2dic(win_prop)
		return wins
	
			
if __name__ == "__main__":
	wins = wm.get_windows()

	pprint(wins)
	print("length of wins:" , len(wins))