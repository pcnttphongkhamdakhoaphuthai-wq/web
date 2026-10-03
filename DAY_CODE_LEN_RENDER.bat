@echo off
chcp 65001 >nul
title ĐẨY MÃ NGUỒN PHÒNG KHÁM PHÚ THÁI LÊN RENDER
color 0b
echo ====================================================================
echo    CẬP NHẬT GIAO DIỆN MỚI 52 HẠNG MỤC LÊN RENDER (AUTO-DEPLOY)
echo ====================================================================
echo.
echo [*] Đang chuẩn bị đẩy 4 commit mới nhất lên GitHub pcnttphongkhamdakhoaphuthai-wq/web...
echo [*] Các thay đổi bao gồm:
echo     - Đại tu giao diện đăng nhập 2 cột theo đúng mẫu thiết kế
echo     - Đưa form lên đầu trang trên điện thoại di động
echo     - Loại bỏ các khối dịch vụ trùng lặp, chuẩn hóa icon SVG y tế
echo     - Xóa bỏ toàn bộ câu từ nhầm lẫn của quản trị viên (Admin)
echo     - Sửa khoa bác sĩ (Khoa Khám bệnh & Nội tổng quát, bỏ số ID thô)
echo     - Tạo mới trang Hỗ trợ công khai (support.php)
echo     - Đổi toàn bộ "Gmail" thành "Email", che mờ thông tin nhận OTP
echo.
cd /d "f:\web phòng khám"
echo [*] Đang thực thi: git push origin main...
git push origin main

if %errorlevel% equ 0 (
    echo.
    echo ====================================================================
    echo [THÀNH CÔNG] Mã nguồn mới đã được đẩy lên GitHub thành công!
    echo Render.com đang tự động kéo Docker build và cập nhật website.
    echo Vui lòng đợi khoảng 1-2 phút rồi truy cập: https://conghotrophongkhamphuthai.io.vn
    echo ====================================================================
) else (
    echo.
    echo ====================================================================
    echo [THÔNG BÁO] Cần xác thực tài khoản GitHub pcnttphongkhamdakhoaphuthai-wq:
    echo Nếu cửa sổ trình duyệt bật lên, bạn chỉ cần bấm "Sign in with your browser"
    echo và chọn "Authorize Git Credential Manager".
    echo ====================================================================
)
echo.
pause
