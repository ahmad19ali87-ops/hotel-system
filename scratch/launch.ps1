$exe = "c:\Users\ahmad\hotel-system\apps\hotel_desktop\build\windows\x64\runner\Release\hotel_desktop.exe"
$dir = "c:\Users\ahmad\hotel-system\apps\hotel_desktop\build\windows\x64\runner\Release"

Write-Output "Starting $exe ..."
$proc = Start-Process -FilePath $exe -WorkingDirectory $dir -PassThru
Start-Sleep -Seconds 1
Write-Output "Launched process ID: $($proc.Id)"
