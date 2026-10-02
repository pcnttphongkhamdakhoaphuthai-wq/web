<?php
declare(strict_types=1);
require_once 'config.php';

// Chỉ cho phép admin đã đăng nhập tải file mẫu
require_admin_login();

$type = trim((string)($_GET['type'] ?? ''));

$allowedTypes = [
    'ai_prompt_pricing'    => 'mau_bang_gia_dich_vu.csv',
    'ai_prompt_procedures' => 'mau_quy_trinh_kham_benh.csv',
    'ai_prompt_documents'  => 'mau_ho_so_thu_tuc_hanh_chinh.csv',
    'ai_prompt_benefits'   => 'mau_quyen_loi_va_che_do_bhyt.csv'
];

if (!array_key_exists($type, $allowedTypes)) {
    http_response_code(400);
    echo "Loại file mẫu không hợp lệ.";
    exit;
}

$filename = $allowedTypes[$type];

// Thiết lập header tải file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Ghi UTF-8 BOM để Excel hiển thị tiếng Việt chuẩn không bị lỗi font
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');
if (!$output) {
    exit;
}

if ($type === 'ai_prompt_pricing') {
    // Tiêu đề cột
    fputcsv($output, ['STT', 'Mã Dịch Vụ', 'Tên Dịch Vụ / Khám Chuyên Khoa', 'Giá Dịch Vụ (VNĐ)', 'Bảo Hiểm Y Tế (BHYT)', 'Ghi Chú / Thời Gian Thực Hiện']);
    
    // Dữ liệu mẫu
    fputcsv($output, ['1', 'DV001', 'Khám nội tổng quát', '100,000', 'Có áp dụng', 'Khám lâm sàng ban đầu']);
    fputcsv($output, ['2', 'DV002', 'Siêu âm ổ bụng tổng quát (4D)', '150,000', 'Có áp dụng', 'Yêu cầu nhịn ăn sáng']);
    fputcsv($output, ['3', 'DV003', 'Chụp X-quang tim phổi thẳng', '120,000', 'Có áp dụng', 'Thời gian trả kết quả: 15 phút']);
    fputcsv($output, ['4', 'DV004', 'Xét nghiệm công thức máu toàn bộ (24 chỉ số)', '150,000', 'Có áp dụng', 'Lấy máu tĩnh mạch, trả kết quả sau 45 phút']);
    fputcsv($output, ['5', 'DV005', 'Điện tâm đồ (ECG)', '80,000', 'Có áp dụng', 'Chẩn đoán nhịp tim, các bệnh lý mạch vành']);
    fputcsv($output, ['6', 'DV006', 'Nội soi dạ dày không đau (gây mê)', '1,200,000', 'Hỗ trợ một phần', 'Cần có người nhà đi cùng, nhịn ăn uống 6 tiếng']);
    fputcsv($output, ['7', 'DV007', 'Tầm soát ung thư cổ tử cung (Pap Smear)', '250,000', 'Không áp dụng', 'Dành cho nữ giới đã kết hôn']);
    fputcsv($output, ['8', 'DV008', 'Khám chuyên khoa Răng Hàm Mặt', '120,000', 'Có áp dụng', 'Kiểm tra răng sâu, cao răng, nha chu']);

} elseif ($type === 'ai_prompt_procedures') {
    fputcsv($output, ['Bước', 'Tên Bước Thực Hiện', 'Nơi Thực Hiện', 'Chi Tiết Quy Trình / Hướng Dẫn Cho Bệnh Nhân', 'Giấy Tờ Cần Chuẩn Bị']);
    
    fputcsv($output, [
        'Bước 1', 
        'Đăng ký & Lấy số thứ tự', 
        'Quầy lễ tân (Tầng 1)', 
        'Bệnh nhân đến quầy xuất trình giấy tờ cá nhân, nêu rõ nhu cầu khám (hoặc mã đặt lịch online) để nhân viên tiếp đón hỗ trợ lấy số thứ tự chuyên khoa.', 
        'CCCD / Thẻ BHYT / Mã đặt lịch online (nếu có)'
    ]);
    fputcsv($output, [
        'Bước 2', 
        'Đóng phí khám ban đầu', 
        'Quầy thu ngân (Kế bên quầy lễ tân)', 
        'Đóng phí khám lâm sàng ban đầu theo chuyên khoa đã đăng ký. Bệnh nhân nhận biên lai và sổ khám bệnh y tế.', 
        'Biên lai thu tiền / Sổ khám bệnh'
    ]);
    fputcsv($output, [
        'Bước 3', 
        'Khám lâm sàng tại phòng bác sĩ', 
        'Các phòng khám chuyên khoa (Tầng 1 & Tầng 2)', 
        'Bệnh nhân ngồi đợi tại hàng ghế trước cửa phòng khám ghi trên phiếu. Khi bảng điện tử hiển thị số thứ tự, bệnh nhân vào phòng gặp bác sĩ chuyên khoa để khám và trao đổi triệu chứng.', 
        'Sổ khám bệnh / Giấy tờ tiền sử bệnh án cũ'
    ]);
    fputcsv($output, [
        'Bước 4', 
        'Thực hiện cận lâm sàng (nếu có)', 
        'Phòng Siêu âm, X-Quang, Xét nghiệm, Điện tim', 
        'Nếu bác sĩ chỉ định xét nghiệm hoặc chẩn đoán hình ảnh, bệnh nhân quay lại quầy thu tiền để đóng phí cận lâm sàng, sau đó đến các phòng chức năng để thực hiện theo hướng dẫn.', 
        'Phiếu chỉ định của bác sĩ / Biên lai đóng tiền cận lâm sàng'
    ]);
    fputcsv($output, [
        'Bước 5', 
        'Nhận kết luận và đơn thuốc', 
        'Phòng khám chuyên khoa ban đầu', 
        'Bệnh nhân mang toàn bộ kết quả cận lâm sàng (kết quả xét nghiệm, phim chụp) quay lại phòng khám ban đầu. Bác sĩ đọc kết quả, đưa ra chẩn đoán cuối cùng, tư vấn chế độ dinh dưỡng và kê đơn thuốc.', 
        'Đầy đủ kết quả cận lâm sàng vừa thực hiện'
    ]);
    fputcsv($output, [
        'Bước 6', 
        'Mua thuốc / Lấy thuốc BHYT & Ra viện', 
        'Nhà thuốc phòng khám (Tầng 1)', 
        'Bệnh nhân đến nhà thuốc nộp đơn thuốc. Nhân viên y tế đối chiếu giấy tờ, cấp phát thuốc BHYT (hoặc bán thuốc theo đơn) và hướng dẫn liều lượng uống chi tiết trước khi ra về.', 
        'Đơn thuốc của bác sĩ / Thẻ BHYT (để đối chiếu)'
    ]);

} elseif ($type === 'ai_prompt_documents') {
    fputcsv($output, ['STT', 'Tên Giấy Tờ / Thủ Tục', 'Đối Tượng Áp Dụng', 'Chi Tiết Quy Định / Yêu Cầu Hồ Sơ', 'Thời Hạn / Lưu Ý']);
    
    fputcsv($output, [
        '1', 
        'Căn cước công dân (CCCD)', 
        'Tất cả bệnh nhân', 
        'Yêu cầu CCCD còn nguyên vẹn, rõ ảnh, rõ số để làm thủ tục tạo hồ sơ bệnh án mới hoặc tra cứu hồ sơ cũ trên hệ thống y tế.', 
        'Bắt buộc đối với tất cả lượt khám'
    ]);
    fputcsv($output, [
        '2', 
        'Thẻ Bảo hiểm y tế (BHYT) / Ứng dụng VssID', 
        'Bệnh nhân có BHYT', 
        'Bệnh nhân xuất trình thẻ BHYT giấy còn hạn sử dụng HOẶC mã QR thẻ BHYT trên ứng dụng VssID/VNeID mức độ 2 tại quầy tiếp đón ngay từ đầu.', 
        'Phải trình trước khi bác sĩ chỉ định dịch vụ'
    ]);
    fputcsv($output, [
        '3', 
        'Giấy chuyển tuyến', 
        'Bệnh nhân khám trái tuyến cần hưởng đúng tuyến', 
        'Giấy chuyển tuyến hợp lệ từ cơ sở khám chữa bệnh ban đầu đến Phòng khám đa khoa Phú Thái. Giấy phải có dấu đỏ, chữ ký của người có thẩm quyền và còn thời hạn hiệu lực.', 
        'Có giá trị sử dụng theo thời gian ghi trên giấy'
    ]);
    fputcsv($output, [
        '4', 
        'Giấy hẹn tái khám', 
        'Bệnh nhân được bác sĩ hẹn lịch khám lại', 
        'Giấy hẹn tái khám do bác sĩ của Phòng khám cấp trong đợt điều trị trước đó. Giấy hẹn tái khám chỉ có giá trị sử dụng 01 lần duy nhất trong thời hạn hẹn.', 
        'Quá hạn hẹn phải xin giấy chuyển tuyến mới'
    ]);
    fputcsv($output, [
        '5', 
        'Hồ sơ sức khỏe cá nhân & đơn thuốc cũ', 
        'Bệnh nhân có tiền sử bệnh mãn tính', 
        'Bệnh nhân nên mang theo các kết quả xét nghiệm, đơn thuốc cũ hoặc sổ y bạ của các lần điều trị trước đó để giúp bác sĩ có cái nhìn toàn diện nhất.', 
        'Không bắt buộc nhưng khuyến khích mang theo'
    ]);

} elseif ($type === 'ai_prompt_benefits') {
    fputcsv($output, ['Mục', 'Chính Sách & Chế Độ', 'Mức Hưởng Chi Tiết', 'Điều Kiện Áp Dụng', 'Quy Định Pháp Luật Liên Quan']);
    
    fputcsv($output, [
        '1. Khám BHYT đúng tuyến', 
        'Được hưởng đầy đủ quyền lợi bảo hiểm chi trả theo quy định của Bộ Y tế.', 
        'Hưởng 80% đến 100% chi phí khám chữa bệnh nằm trong danh mục bảo hiểm chi trả (tùy thuộc vào mã đối tượng ghi trên thẻ BHYT).', 
        'Xuất trình thẻ BHYT hợp lệ + CCCD (hoặc ứng dụng VssID) ngay khi đăng ký tại quầy tiếp đón.', 
        'Theo Luật Bảo hiểm y tế hiện hành'
    ]);
    fputcsv($output, [
        '2. Khám BHYT trái tuyến', 
        'Hưởng tỷ lệ thanh toán của BHYT khi tự đi khám chữa bệnh không đúng tuyến ban đầu.', 
        'Hưởng 100% chi phí khám chữa bệnh đúng tuyến tại các bệnh viện tuyến huyện, phòng khám đa khoa khu vực trên toàn quốc (thông tuyến huyện).', 
        'Áp dụng khi đi khám tại Phòng khám đa khoa Phú Thái (là phòng khám đa khoa tương đương tuyến huyện). Bệnh nhân không cần giấy chuyển tuyến vẫn được hưởng đúng quyền lợi.', 
        'Chính sách thông tuyến huyện theo Nghị định 146/2018/NĐ-CP'
    ]);
    fputcsv($output, [
        '3. Chế độ cho Trẻ em dưới 6 tuổi', 
        'Ưu tiên tiếp đón và miễn hoàn toàn chi phí khám bệnh cơ bản.', 
        'Được hưởng 100% chi phí khám chữa bệnh bảo hiểm y tế chi trả và không phải đồng chi trả.', 
        'Xuất trình thẻ BHYT của bé hoặc Giấy chứng sinh/Giấy khai sinh (đối với trẻ chưa được cấp thẻ BHYT).', 
        'Luật trẻ em và Luật Bảo hiểm y tế'
    ]);
    fputcsv($output, [
        '4. Chế độ cho Người cao tuổi (từ 80 tuổi)', 
        'Ưu tiên tiếp đón và hỗ trợ di chuyển lâm sàng.', 
        'Được ưu tiên lấy số thứ tự khám trước, hỗ trợ xe lăn và nhân viên y tế đưa đón tận phòng khám.', 
        'Áp dụng cho tất cả các cụ già từ 80 tuổi trở lên khi đến khám lâm sàng tại phòng khám.', 
        'Luật Người cao tuổi hiện hành'
    ]);
    fputcsv($output, [
        '5. Quyền lợi bảo lãnh viện phí tư nhân', 
        'Phòng khám hỗ trợ xuất hóa đơn VAT và hồ sơ y khoa để làm thủ tục thanh toán bảo hiểm sức khỏe tư nhân.', 
        'Bệnh nhân thanh toán trước, phòng khám cung cấp đầy đủ hóa đơn đỏ, bảng kê chi tiết dịch vụ, bệnh án để nộp về công ty bảo hiểm nhận hoàn tiền.', 
        'Yêu cầu nhân viên thu ngân xuất hóa đơn điện tử ngay trong ngày khám bệnh.', 
        'Hỗ trợ liên kết các hãng BH: Dai-ichi, Prudential, Manulife, Bảo Việt...'
    ]);
}

fclose($output);
exit;
