Add-Type -AssemblyName System.Windows.Forms
Stop-Process -Name httpd -Force -ErrorAction SilentlyContinue
Stop-Process -Name mysqld -Force -ErrorAction SilentlyContinue

& "C:\xampp\apache\bin\httpd.exe" -k install
& "C:\xampp\mysql\bin\mysqld.exe" --install mysql --defaults-file="C:\xampp\mysql\bin\my.ini"

Set-Service -Name Apache2.4 -StartupType Automatic
Set-Service -Name mysql -StartupType Automatic

Start-Service Apache2.4
Start-Service mysql

[System.Windows.Forms.MessageBox]::Show("Đã cài đặt thành công! Apache và MySQL giờ đây sẽ chạy ngầm 24/7 như Windows Services.", "Hoàn tất cài đặt", 0, [System.Windows.Forms.MessageBoxIcon]::Information)
