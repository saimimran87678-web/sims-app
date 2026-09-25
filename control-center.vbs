Set objShell = CreateObject("WScript.Shell")
Set objFSO = CreateObject("Scripting.FileSystemObject")
strDir = objFSO.GetParentFolderName(WScript.ScriptFullName)

If objFSO.FileExists(strDir & "\Adminova-Control-Center.exe") Then
    objShell.Run """" & strDir & "\Adminova-Control-Center.exe""", 1, False
ElseIf objFSO.FileExists(strDir & "\scripts\windows\control-panel.ps1") Then
    strPS = "powershell.exe -WindowStyle Hidden -NoProfile -ExecutionPolicy Bypass -File """ & strDir & "\scripts\windows\control-panel.ps1"""
    objShell.Run strPS, 0, False
ElseIf objFSO.FileExists(strDir & "\control-panel.ps1") Then
    strPS = "powershell.exe -WindowStyle Hidden -NoProfile -ExecutionPolicy Bypass -File """ & strDir & "\control-panel.ps1"""
    objShell.Run strPS, 0, False
End If
