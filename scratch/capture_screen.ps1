Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

$screen = [System.Windows.Forms.Screen]::PrimaryScreen.Bounds
$bitmap = New-Object System.Drawing.Bitmap $screen.Width, $screen.Height
$graphics = [System.Drawing.Graphics]::FromImage($bitmap)
$graphics.CopyFromScreen($screen.Location, [System.Drawing.Point]::Empty, $screen.Size)

$outPath = "C:\Users\ahmad\.gemini\antigravity\brain\0e02d514-4369-494e-a242-d6a4519b12dc\desktop_screen_notifications_print.png"
$bitmap.Save($outPath, [System.Drawing.Imaging.ImageFormat]::Png)
$graphics.Dispose()
$bitmap.Dispose()
Write-Output "Screenshot saved to $outPath"

