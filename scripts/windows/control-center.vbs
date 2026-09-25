Set objShell = CreateObject("WScript.Shell")
Set objFSO = CreateObject("Scripting.FileSystemObject")
strDir = objFSO.GetParentFolderName(WScript.ScriptFullName)

strPS = "powershell.exe -WindowStyle Hidden -NoProfile -ExecutionPolicy Bypass -File """ & strDir & "\control-panel.ps1"""
objShell.Run strPS, 0, False
