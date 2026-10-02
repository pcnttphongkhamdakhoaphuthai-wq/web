$action = New-ScheduledTaskAction `
    -Execute "C:\xampp\php\php.exe" `
    -Argument "C:\xampp\htdocs\hospital_full_ALL\scheduled_backup.php" `
    -WorkingDirectory "C:\xampp\htdocs\hospital_full_ALL"

$trigger = New-ScheduledTaskTrigger -Daily -At "16:55"

$settings = New-ScheduledTaskSettingsSet `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 30) `
    -MultipleInstances IgnoreNew `
    -StartWhenAvailable

Register-ScheduledTask `
    -TaskName "HospitalDailyBackup" `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Description "Tu dong sao luu du lieu benh vien luc 16:55 hang ngay" `
    -RunLevel Highest `
    -Force

Write-Host "=== Da tao lich sao luu thanh cong ===" -ForegroundColor Green
Write-Host "Ten: HospitalDailyBackup" -ForegroundColor Cyan
Write-Host "Gio chay: 16:55 hang ngay" -ForegroundColor Cyan
Write-Host "PHP: C:\xampp\php\php.exe" -ForegroundColor Cyan
Write-Host "Script: C:\xampp\htdocs\hospital_full_ALL\scheduled_backup.php" -ForegroundColor Cyan

Add-Type -AssemblyName System.Windows.Forms
[System.Windows.Forms.MessageBox]::Show(
    "Da tao lich sao luu thanh cong!`n`nTen: HospitalDailyBackup`nGio chay: 16:55 hang ngay`n`nHe thong se tu dong backup database va file moi ngay.",
    "Hoan tat - Lich Sao Luu", 0,
    [System.Windows.Forms.MessageBoxIcon]::Information
)
