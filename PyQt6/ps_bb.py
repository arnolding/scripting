# Date 2023/1/17
# This is to define the functions to get processes
# usage:
import psutil
import subprocess
import os
from anytree import Node, RenderTree

class ps:
	def get_processes(pid):
		ProcessNames = {}
		process_list = []
		tree_node = {}
		
		procs = {p.pid: p.info for p in psutil.process_iter(['exe', 'memory_percent', 'cpu_percent', 
															 'pid', 'ppid', 'create_time', 'cmdline', 'name', 'username'])}
		group_id = [pid]
		ProcessNames[pid] = procs[pid]
		while (len(group_id) > 0):
			cid = group_id[0]
			for i,v in procs.items():  ##process_list:
				if (v['ppid'] == cid):
					ProcessNames[i] = v
					group_id.append(v['pid'])
				if (v['name'] == 'deckbuild'):
					ProcessNames['deckbuild'] = v
			del group_id[0]

		
		# Iterate over all running processes
		for proc in psutil.process_iter():
			# Get process detail as dictionary
			#pInfoDict = proc.as_dict(attrs=['pid', 'name', 'cpu_percent'])
			pInfoDict = proc.as_dict(attrs=['exe', 'memory_percent', 'cpu_percent', 'pid', 'ppid', 'create_time', 'cmdline', 'name', 'username'])
			
			# Append dict of process detail in list
			#ProcessNames[pInfoDict['pid']] = pInfoDict
			process_list.append(pInfoDict)
			if (pInfoDict['pid'] == pid) :
				ProcessNames[pid] = pInfoDict
			##print("pid=" , pInfoDict['pid'], "\tppid=",pInfoDict['ppid'])
			if (pInfoDict['ppid'] == 0):
				n1 = Node(pInfoDict['pid'] , parent=None, cmd = pInfoDict['name']  )
			else:
				n1 = Node(pInfoDict['pid'] , parent=tree_node[pInfoDict['ppid']], cmd = pInfoDict['name'] )
			tree_node[pInfoDict['pid']] = n1

		print(RenderTree(tree_node[pid]))
		group_id = [pid]
		while (len(group_id) > 0):
			cid = group_id[0]
			for i in process_list:
				if (i['ppid'] == cid):
					ProcessNames[i['pid']] = i
					group_id.append(i['pid'])
				if (i['name'] == 'deckbuild'):
					ProcessNames['deckbuild'] = i['pid']
			del group_id[0]

		return ProcessNames
