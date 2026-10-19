$psi = New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName = "c:\Users\ahmad\hotel-system\apps\hotel_desktop\build\windows\x64\runner\Release\hotel_desktop.exe"
$psi.WorkingDirectory = "c:\Users\ahmad\hotel-system\apps\hotel_desktop\build\windows\x64\runner\Release"
$psi.RedirectStandardOutput = $true
$psi.RedirectStandardError = $true
$psi.UseShellExecute = $false
$psi.CreateNoWindow = $true

$proc = [System.Diagnostics.Process]::Start($psi)
$stdout = $proc.StandardOutput.ReadToEnd()
$stderr = $proc.StandardError.ReadToEnd()
$proc.WaitForExit(4000)

Write-Output "ExitCode: $($proc.ExitCode)"
Write-Output "STDOUT: $stdout"
Write-Output "STDERR: $stderr"

