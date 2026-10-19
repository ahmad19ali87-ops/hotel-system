$p = Get-Process -Name 'hotel_desktop' -ErrorAction SilentlyContinue
if ($p) {
    Write-Output "RUNNING: ID=$($p.Id), Name=$($p.ProcessName), WindowTitle='$($p.MainWindowTitle)'"
} else {
    Write-Output "NOT RUNNING"
}

