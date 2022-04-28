# Date 2023/1/13
# This is to define the tool installation and its decks
# usage:
# inst = installation("deckbuild")
# inst.
import subprocess
import os
class installation:
	def collect_deck(self, ex_path):
		decks = []
		ext = ('.in')
		for path, dirc, files in os.walk(ex_path):
			for name in files:
				if name.endswith(ext):
					decks.append(os.path.join(path, name))
		prefix = os.path.commonprefix(decks)
		
		self.decks = decks
		self.examples_path = prefix
			
	def __init__(self, tool):
		tool_path = subprocess.run(["which", tool], capture_output=True, text=True).stdout.strip("\n")
		self.tool_path = tool_path
		self.examples_path = os.path.join(os.path.dirname(os.path.dirname(tool_path)) , "examples")
		self.collect_deck(self.examples_path)