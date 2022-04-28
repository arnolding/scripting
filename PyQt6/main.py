#!/usr/local/bin/python3
from PyQt6 import uic
from PyQt6.QtWidgets import QApplication
import subprocess
import os

Form, Window = uic.loadUiType("/home/arnold/scripting/PyQt6/c.ui")

app = QApplication([])
window = Window()
form = Form()
form.setupUi(window)
window.show()

print("GO")


app.exec()
