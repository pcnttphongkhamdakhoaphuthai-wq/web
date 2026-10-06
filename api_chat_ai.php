<?php
declare(strict_types=1);

/**
 * api_chat_ai.php - Trợ lý AI Phòng Khám
 * Model cascade: gemini-2.5-pro → 2.5-flash → 2.0-flash → 2.0-flash-lite → 1.5-flash → 1.5-flash-8b
 * Tự động giảm tier model khi quota hết hoặc lỗi, đảm bảo luôn có phản hồi.
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// Chỉ chấp nhận POST (hoặc CLI test)
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' && php_sapi_name() !== 'cli') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Đọc input
$rawInput = file_get_contents('php://input');
if ($rawInput === '' && php_sapi_name() === 'cli') {
    $rawInput = file_get_contents('php://stdin');
}
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$message = trim((string) ($input['message'] ?? ''));
if ($message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Tin nhắn không được để trống.']);
    exit;
}

// Giới hạn độ dài tin nhắn
if (mb_strlen($message, 'UTF-8') > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Tin nhắn không được vượt quá 1000 ký tự.']);
    exit;
}

// Rate limit: 15 request / 60 giây / IP
if (rate_limit_hit('ai_chat', 15, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau ít phút.']);
    exit;
}

// Xây dựng danh sách API keys (key chính từ .secrets.php + key phụ từ DB)
$primaryKey   = APP_GEMINI_API_KEY ?? '';
$extraKeysRaw = site_setting('gemini_api_keys', '');
$extraKeys    = array_filter(array_map('trim', explode("\n", $extraKeysRaw)));

// Gộp: key chính trước, key phụ sau, loại bỏ rỗng/placeholder
$allApiKeys = [];
foreach (array_merge([$primaryKey], $extraKeys) as $k) {
    $k = trim($k);
    if ($k !== '' && $k !== 'your-gemini-api-key-here' && $k !== 'SIMULATED_KEY') {
        $allApiKeys[] = $k;
    }
}
$allApiKeys = array_unique($allApiKeys);

// Lấy thông tin phòng khám từ database
try {
    $clinicSettings = site_settings([
        'clinic_name'         => 'Phòng khám đa khoa Phú Thái',
        'clinic_intro'        => '',
        'clinic_mission'      => '',
        'clinic_facility'     => '',
        'clinic_services'     => '',
        'clinic_support'      => '',
        'support_hotline'     => '1900 0000',
        'support_email'       => '',
        'clinic_address'      => 'Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên',
        'google_maps_url'     => 'https://www.google.com/maps?q=Ph%C3%B2ng+Kh%C3%A1m+%C4%90a+Khoa+Ph%C3%BA+Th%C3%A1i,+T%E1%BB%95+2,+Ph%C3%BA+B%E1%BA%A3nh,+Th%C3%A1i+Nguy%C3%AAn&ftid=0x31353d0011d3f57d:0x1453bff31ddf46eb',
        'ai_prompt_procedures'=> "1. Đăng ký tại quầy lễ tân (mang CCCD hoặc thẻ BHYT).\n2. Lấy số thứ tự và đóng phí tạm ứng.\n3. Đến phòng khám chuyên khoa theo hướng dẫn.\n4. Thực hiện các chỉ định cận lâm sàng (nếu có).\n5. Nhận kết luận từ bác sĩ và đơn thuốc.\n6. Thanh toán viện phí tại quầy.",
        'ai_prompt_pricing'   => "Khám nội tổng quát: 100.000 VNĐ\nSiêu âm ổ bụng: 150.000 VNĐ\nChụp X-quang: 120.000 VNĐ\nXét nghiệm máu: 150.000 VNĐ\nĐiện tim đồ: 80.000 VNĐ",
        'ai_prompt_documents' => "Bệnh nhân cần mang theo CCCD và thẻ BHYT hợp lệ. CÓ hỗ trợ khám và CẤP GIẤY NGHỈ ỐM HƯỞNG BẢO HIỂM XÃ HỘI (BHXH) cho bệnh nhân có bảo hiểm theo quy định. ***LƯU Ý QUAN TRỌNG: Phòng khám CHƯA hỗ trợ khám và CẤP GIẤY KHÁM SỨC KHỎE (hai loại giấy tờ này hoàn toàn khác nhau, giấy nghỉ ốm BHXH thì CÓ cấp nhưng giấy khám sức khỏe thì CHƯA hỗ trợ).***",
        'ai_prompt_benefits'  => "BHYT đúng tuyến được hưởng 80% chi phí. Trẻ em dưới 6 tuổi miễn phí.",
        'ai_prompt_schedule'  => "Thứ 2 - Thứ 6: Sáng 7h30 - 11h30, Chiều 13h30 - 17h00. Thứ 7 & Chủ Nhật: Nghỉ.",
    ]);
} catch (Throwable $e) {
    $clinicSettings = [
        'clinic_name'         => 'Phòng khám đa khoa Phú Thái',
        'clinic_intro'        => '',
        'clinic_mission'      => '',
        'clinic_facility'     => '',
        'clinic_services'     => '',
        'clinic_support'      => '',
        'support_hotline'     => '1900 0000',
        'support_email'       => '',
        'clinic_address'      => 'Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên',
        'google_maps_url'     => 'https://www.google.com/maps?q=Ph%C3%B2ng+Kh%C3%A1m+%C4%90a+Khoa+Ph%C3%BA+Th%C3%A1i,+T%E1%BB%95+2,+Ph%C3%BA+B%E1%BA%A3nh,+Th%C3%A1i+Nguy%C3%AAn&ftid=0x31353d0011d3f57d:0x1453bff31ddf46eb',
        'ai_prompt_procedures'=> "1. Đăng ký tại quầy lễ tân.\n2. Lấy số thứ tự và đóng phí.\n3. Đến phòng khám theo hướng dẫn.\n4. Nhận kết luận và thanh toán.",
        'ai_prompt_pricing'   => "Khám nội tổng quát: 100.000 VNĐ\nSiêu âm: 150.000 VNĐ\nXét nghiệm máu: 150.000 VNĐ",
        'ai_prompt_documents' => "Mang theo CCCD và thẻ BHYT. CÓ hỗ trợ khám và CẤP GIẤY NGHỈ ỐM HƯỞNG BẢO HIỂM XÃ HỘI (BHXH) cho bệnh nhân có bảo hiểm theo quy định. ***LƯU Ý QUAN TRỌNG: Phòng khám CHƯA hỗ trợ khám và CẤP GIẤY KHÁM SỨC KHỎE (hai loại giấy tờ này hoàn toàn khác nhau, giấy nghỉ ốm BHXH thì CÓ cấp nhưng giấy khám sức khỏe thì CHƯA hỗ trợ).***",
        'ai_prompt_benefits'  => "BHYT đúng tuyến hưởng 80%.",
        'ai_prompt_schedule'  => "Thứ 2 - Thứ 6: 7h30 - 17h00. Thứ 7 & CN nghỉ.",
    ];
}

// Xây dựng System Prompt đầy đủ từ tất cả dữ liệu hệ thống
$svcList = trim((string)$clinicSettings['clinic_services']) !== '' ? $clinicSettings['clinic_services'] : "Siêu âm, xét nghiệm, khám nội, điện tim, tư vấn sức khỏe.";
$emailLine = trim((string)$clinicSettings['support_email']) !== '' ? "\nEmail: {$clinicSettings['support_email']}" : '';
$mapLine   = trim((string)$clinicSettings['google_maps_url']) !== '' ? "\nBản đồ: {$clinicSettings['google_maps_url']}" : '';

$systemPrompt = <<<PROMPT
Bạn là trợ lý AI thông minh, thân thiện của "{$clinicSettings['clinic_name']}".
Nhiệm vụ: hỗ trợ bệnh nhân giải đáp mọi thắc mắc về phòng khám, dịch vụ y tế, BHYT và các quy định pháp luật y tế.

=== THÔNG TIN PHÒNG KHÁM ===
Tên: {$clinicSettings['clinic_name']}
Địa chỉ: {$clinicSettings['clinic_address']}
Hotline: {$clinicSettings['support_hotline']}{$emailLine}{$mapLine}

=== GIỚI THIỆU ===
{$clinicSettings['clinic_intro']}

=== SỨ MỆNH ===
{$clinicSettings['clinic_mission']}

=== CƠ SỞ VẬT CHẤT ===
{$clinicSettings['clinic_facility']}

=== DỊCH VỤ CUNG CẤP ===
{$svcList}

=== HỖ TRỢ BỆNH NHÂN ===
{$clinicSettings['clinic_support']}

=== QUY TRÌNH KHÁM BỆNH ===
{$clinicSettings['ai_prompt_procedures']}

=== BẢNG GIÁ DỊCH VỤ ===
{$clinicSettings['ai_prompt_pricing']}

=== QUY ĐỊNH BẮT BUỘC VỀ ĐỊNH DẠNG BẢNG GIÁ (TUYỆT ĐỐI TUÂN THỦ) ===
- Khi tư vấn hoặc trả lời bất kỳ thông tin nào liên quan đến chi phí, giá dịch vụ khám chữa bệnh: BẮT BUỘC phải định dạng thành Bảng Markdown (Markdown Table) chuẩn gồm đúng 5 cột:
  | STT | Tên dịch vụ | Giá niêm yết (VNĐ) | Hỗ trợ BHYT | Ghi chú |
- Đơn giá BẮT BUỘC phải định dạng có dấu chấm phân cách hàng nghìn (ví dụ: 100.000 VNĐ, 150.000 VNĐ, 80.000 VNĐ).
- TUYỆT ĐỐI KHÔNG xả danh sách dạng văn bản thô, gạch đầu dòng đơn thuần hoặc TSV/CSV dính liền nhau.
- Bảng Markdown PHẢI đầy đủ dòng tiêu đề (Header) và dòng phân cách (| :---: | :--- | :---: | :---: | :--- |).
- Mẫu bảng Markdown chuẩn bắt buộc áp dụng:

| STT | Tên dịch vụ | Giá niêm yết (VNĐ) | Hỗ trợ BHYT | Ghi chú |
| :---: | :--- | :---: | :---: | :--- |
| 1 | Khám nội tổng quát | 100.000 VNĐ | Có hỗ trợ (80%) | Khám và tư vấn chuyên khoa ban đầu |
| 2 | Siêu âm ổ bụng | 150.000 VNĐ | Có hỗ trợ | Bác sĩ chuyên khoa chẩn đoán hình ảnh |
| 3 | Chụp X-quang | 120.000 VNĐ | Có hỗ trợ | Kỹ thuật số hiện đại |
| 4 | Xét nghiệm máu | 150.000 VNĐ | Có hỗ trợ | Nhịn ăn sáng trước khi lấy máu |
| 5 | Điện tim đồ | 80.000 VNĐ | Có hỗ trợ | Đánh giá chức năng tim mạch |

=== HỒ SƠ THỦ TỤC & CÔNG VĂN ===
{$clinicSettings['ai_prompt_documents']}

=== LỢI ÍCH & CHẾ ĐỘ BHYT ===
{$clinicSettings['ai_prompt_benefits']}

=== GIỜ LÀM VIỆC & THỜI GIAN PHỤC VỤ ===
{$clinicSettings['ai_prompt_schedule']}

=== PHẠM VI HOẠT ĐỘNG (BẮT BUỘC TUÂN THỦ) ===
Bạn CHỈ được trả lời các câu hỏi thuộc lĩnh vực y tế và phòng khám, bao gồm:
- Thông tin, dịch vụ, giá cả của {$clinicSettings['clinic_name']}
- Quy trình khám bệnh, thủ tục hành chính y tế
- Bảo hiểm y tế (BHYT), quyền lợi bệnh nhân
- Thông tư, nghị định, quy định pháp luật về y tế, khám chữa bệnh
- Kiến thức y khoa phổ thông (triệu chứng, phòng ngừa bệnh, thuốc thông thường)
- Hướng dẫn chăm sóc sức khỏe cá nhân

Nếu câu hỏi KHÔNG liên quan đến y tế, sức khỏe hoặc phòng khám (ví dụ: lập trình, pháp luật ngoài y tế, tài chính, giải trí, chính trị, v.v.), bạn PHẢI từ chối lịch sự bằng câu: "Xin lỗi, tôi chỉ có thể hỗ trợ các câu hỏi liên quan đến y tế và dịch vụ của {$clinicSettings['clinic_name']}. Nếu cần hỗ trợ y tế, tôi rất sẵn lòng giúp bạn!"

=== NGUYÊN TẮC TRẢ LỜI ===
1. Luôn trả lời bằng tiếng Việt, lịch sự và chuyên nghiệp.
2. Phân tích câu hỏi, xác định đúng nhu cầu y tế của người dùng rồi trả lời đúng trọng tâm. Dùng danh sách khi cần.
3. Với thông tin nội bộ (giá, thủ tục, dịch vụ phòng khám): dùng dữ liệu trên. Với câu hỏi về thông tư, nghị định, kiến thức y khoa chung: dùng Google Search để tra cứu và trả lời chính xác nhất.
4. BẮT BUỘC KẺ BẢNG GIÁ: Mọi thông tin về giá khám hay chi phí dịch vụ PHẢI kẻ bảng Markdown theo đúng mẫu quy định trên (| STT | Tên dịch vụ | Giá niêm yết (VNĐ) | Hỗ trợ BHYT | Ghi chú |), tuyệt đối không liệt kê thô dạng văn bản.
5. Không tư vấn điều trị chuyên sâu, không chẩn đoán bệnh cụ thể — hãy khuyến nghị bệnh nhân đến gặp bác sĩ.
6. Nếu không chắc về thông tin phòng khám: "Vui lòng liên hệ hotline {$clinicSettings['support_hotline']} để được hỗ trợ chính xác hơn."
PROMPT;

// Chuẩn bị lịch sử hội thoại (tối đa 10 lượt)
$history = [];
if (isset($input['history']) && is_array($input['history'])) {
    $maxHistory = 10;
    $rawHistory = array_slice($input['history'], -$maxHistory);
    foreach ($rawHistory as $turn) {
        $role    = (string) ($turn['role'] ?? '');
        $content = (string) ($turn['content'] ?? '');
        if (in_array($role, ['user', 'model'], true) && $content !== '') {
            $history[] = [
                'role'  => $role,
                'parts' => [['text' => $content]],
            ];
        }
    }
}

// Thêm tin nhắn mới vào cuối
$history[] = [
    'role'  => 'user',
    'parts' => [['text' => $message]],
];

// Payload gửi lên Gemini 2.5 Flash (với Search Grounding)
// Lưu ý: responseMimeType không tương thích với googleSearch, không dùng ở đây
$payload = [
    'contents'          => $history,
    'systemInstruction' => [
        'parts' => [['text' => $systemPrompt]],
    ],
    'tools' => [
        ['googleSearch' => new stdClass()]
    ],
    'generationConfig'  => [
        'temperature'     => 0.4,
        'topK'            => 40,
        'topP'            => 0.95,
        'maxOutputTokens' => 1024,
        'thinkingConfig'  => ['thinkingBudget' => 512],
    ],
    'safetySettings' => [
        ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
    ],
];

// ── Hàm gọi API Gemini ──
function callGemini(string $key, string $model, array $payload): array {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . urlencode($key);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 55,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    @curl_close($ch);
    return ['code' => $httpCode, 'body' => (string)$response, 'curl_error' => $curlError];
}

// ── Danh sách model ưu tiên: cao → thấp ──
// Mỗi entry: [model_id, supports_thinking, supports_search]
$modelTiers = [
    // Tier 1 – Flash 2.5 (phổ thông nhất, quota tốt trên free tier)
    ['gemini-2.5-flash',              true,  true ],
    ['gemini-2.5-flash-preview-04-17',true,  true ],
    // Tier 2 – Pro 2.5 (mạnh nhất nhưng quota thấp hơn)
    ['gemini-2.5-pro',                true,  true ],
    // Tier 3 – Flash 2.0
    ['gemini-2.0-flash',              false, true ],
    ['gemini-2.0-flash-001',          false, true ],
    ['gemini-2.0-flash-exp',          false, true ],
    // Tier 4 – Flash 2.0 Lite (nhẹ, quota dồi dào hơn)
    ['gemini-2.0-flash-lite',         false, false],
    ['gemini-2.0-flash-lite-001',     false, false],
    // Tier 5 – Flash 1.5 (tên chính xác cho v1beta)
    ['gemini-1.5-flash-002',          false, true ],
    ['gemini-1.5-flash-001',          false, true ],
    // Tier 6 – Flash 1.5 8B (nhỏ nhất, luôn sẵn sàng)
    ['gemini-1.5-flash-8b-001',       false, false],
];

// ── Hàm tạo payload phù hợp cho từng model tier ──
function buildPayload(array $basePayload, bool $supportsThinking, bool $supportsSearch): array {
    $p = $basePayload;

    if ($supportsSearch) {
        $p['tools'] = [['googleSearch' => new stdClass()]];
    } else {
        unset($p['tools']);
        $p['generationConfig']['responseMimeType'] = 'text/plain';
    }

    if ($supportsThinking) {
        $p['generationConfig']['thinkingConfig'] = ['thinkingBudget' => 512];
    } else {
        unset($p['generationConfig']['thinkingConfig']);
    }

    return $p;
}

// ── Các hàm xử lý trích xuất và lọc thông minh bảng giá dịch vụ y tế ──
if (!function_exists('fixDoubleUtf8')) {
    function fixDoubleUtf8(string $str): string {
        if (preg_match('/[\xc3\xc4\xc5][\x80-\xbf]/', $str)) {
            $test = @mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
            if ($test !== false && mb_check_encoding($test, 'UTF-8') && preg_match('/[\x{00C0}-\x{1EF9}]/u', $test)) {
                return $test;
            }
        }
        return $str;
    }
}

if (!function_exists('removeVietnameseAccents')) {
    function removeVietnameseAccents(string $str): string {
        $str = mb_strtolower($str, 'UTF-8');
        $patterns = [
            '/[àáạảãâầấậẩẫăằắặẳẵ]/u' => 'a',
            '/[èéẹẻẽêềếệểễ]/u'         => 'e',
            '/[ìíịỉĩ]/u'               => 'i',
            '/[òóọỏõôồốộổỗơờớợởỡ]/u' => 'o',
            '/[ùúụủũưừứựửữ]/u'         => 'u',
            '/[ỳýỵỷỹ]/u'               => 'y',
            '/[đ]/u'                   => 'd',
        ];
        return preg_replace(array_keys($patterns), array_values($patterns), $str) ?? $str;
    }
}

if (!function_exists('hasVietnameseAccents')) {
    function hasVietnameseAccents(string $str): bool {
        return (bool) preg_match('/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/iu', $str);
    }
}

if (!function_exists('parsePricingServices')) {
    function parsePricingServices(string $pricingText): array {
        $lines = explode("\n", $pricingText);
        $services = [];
        $seen = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // Bỏ BOM UTF-8 nếu có
            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            $line = trim($line);

            if (str_starts_with($line, '---') || str_starts_with($line, '===') || str_starts_with($line, '###')) {
                continue;
            }

            $line = fixDoubleUtf8($line);

            $lineLower = mb_strtolower($line, 'UTF-8');
            if (preg_match('/^(stt|mã\s*(?:dịch\s*vụ|dv)|tên\s*(?:dịch\s*vụ|dv)|giá\s*(?:dịch\s*vụ|dv))/u', $lineLower) ||
                (str_contains($lineLower, 'tên dịch vụ') && str_contains($lineLower, 'giá'))) {
                continue;
            }

            $code = '';
            $name = '';
            $price = '';
            $bhyt = '';

            // Format 1: Phân tách bằng Tab (\t)
            if (str_contains($line, "\t")) {
                $parts = array_map('trim', explode("\t", $line));
                $parts = array_values(array_filter($parts, fn($p) => $p !== ''));
                if (count($parts) >= 2) {
                    if (is_numeric($parts[0])) {
                        array_shift($parts);
                    }
                    if (count($parts) >= 3 && preg_match('/^[A-Z0-9_\-\.]{2,12}$/i', $parts[0])) {
                        $code  = $parts[0];
                        $name  = $parts[1];
                        $price = $parts[2];
                        $bhyt  = $parts[3] ?? '';
                    } elseif (count($parts) >= 2) {
                        $name  = $parts[0];
                        $price = $parts[1];
                        $bhyt  = $parts[2] ?? '';
                    }
                }
            }
            // Format 2: "Tên dịch vụ: Giá" hoặc "Mã - Tên: Giá"
            elseif (preg_match('/^(?:([A-Za-z0-9_\-]+)\s*[\-\.]\s*)?([^:]+?)\s*:\s*([0-9\.,]+(?:\s*(?:vnđ|vnd|đ|d))?)(?:\s*[\-\|\(]\s*(.*?)\)?)?$/iu', $line, $m)) {
                $code  = $m[1] ?? '';
                $name  = trim($m[2]);
                $price = trim($m[3]);
                $bhyt  = trim($m[4] ?? '');
            }
            // Format 3: Markdown table row "| STT | Mã | Tên | Giá | BHYT |"
            elseif (str_contains($line, '|')) {
                $parts = array_map('trim', explode('|', trim($line, '|')));
                if (count($parts) >= 3 && !preg_match('/^[\-\:\s]+$/', $parts[0])) {
                    if (is_numeric($parts[0])) {
                        array_shift($parts);
                    }
                    if (count($parts) >= 3 && preg_match('/^[A-Z0-9_\-\.]{2,12}$/i', $parts[0])) {
                        $code  = $parts[0];
                        $name  = $parts[1];
                        $price = $parts[2];
                        $bhyt  = $parts[3] ?? '';
                    } elseif (count($parts) >= 2) {
                        $name  = $parts[0];
                        $price = $parts[1];
                        $bhyt  = $parts[2] ?? '';
                    }
                }
            }

            $name = trim($name, " \t\n\r\0\x0B-•*");
            if ($name === '' || mb_strlen($name, 'UTF-8') < 2) {
                continue;
            }

            $testName = mb_strtolower($name, 'UTF-8');
            if (in_array($testName, ['stt', 'mã dịch vụ', 'mã dv', 'tên dịch vụ', 'tên dv', 'giá dịch vụ', 'đơn giá', 'bảo hiểm y tế', 'bhyt', 'ghi chú'], true)) {
                continue;
            }

            $numPrice = preg_replace('/[^\d]/', '', $price);
            $priceFormatted = $numPrice !== '' ? number_format((float)$numPrice, 0, ',', '.') . ' đ' : 'Liên hệ';

            $bhytClean = trim($bhyt);
            if ($bhytClean === '') {
                $bhytDisplay = 'Theo quy định';
            } elseif (preg_match('/có|áp dụng|hỗ trợ|đúng tuyến/iu', $bhytClean) && !preg_match('/không|chưa/iu', $bhytClean)) {
                $bhytDisplay = 'Có áp dụng';
            } elseif (preg_match('/không|ko|chưa/iu', $bhytClean)) {
                $bhytDisplay = 'Không áp dụng';
            } else {
                $bhytDisplay = $bhytClean;
            }

            $codeDisplay = $code !== '' ? strtoupper($code) : '-';

            $normKey = removeVietnameseAccents($name);
            if (isset($seen[$normKey])) {
                continue;
            }
            $seen[$normKey] = true;

            $services[] = [
                'code'        => $codeDisplay,
                'name'        => $name,
                'price'       => $priceFormatted,
                'bhyt'        => $bhytDisplay,
                'name_lower'  => mb_strtolower($name, 'UTF-8'),
                'name_no_acc' => $normKey,
            ];
        }

        return $services;
    }
}

if (!function_exists('searchPricingServices')) {
    function searchPricingServices(array $allServices, string $userMessage, int $limit = 10): array {
        $cleanInput = preg_replace('/[,\.\?!:;\(\)\[\]"\'\+\*\/\\~_]/u', ' ', $userMessage) ?? $userMessage;
        $msgLower   = mb_strtolower(trim($cleanInput), 'UTF-8');
        $msgNoAcc   = removeVietnameseAccents($msgLower);

        $intentWords = [
            'bảng giá dịch vụ', 'bang gia dich vu', 'bảng giá', 'bang gia', 'báo giá', 'bao gia',
            'giá dịch vụ', 'gia dich vu', 'chi phí khám', 'chi phi kham', 'chi phí', 'chi phi',
            'giá cả', 'gia ca', 'bao nhiêu tiền', 'bao nhieu tien', 'hết bao nhiêu', 'het bao nhieu',
            'bao nhiêu', 'bao nhieu', 'giá bao nhiêu', 'gia bao nhieu', 'giá tiền', 'gia tien',
            'giá', 'gia', 'tiền', 'tien', 'phí', 'phi', 'mức giá', 'muc gia',
            'cho tôi hỏi', 'cho em hỏi', 'cho em xin', 'cho minh hoi', 'làm ơn cho hỏi', 'xin hỏi',
            'phòng khám', 'phong kham', 'dịch vụ', 'dich vu', 'khám bệnh', 'kham benh', 'tư vấn',
            'tra cứu', 'tra cuu', 'xem bảng', 'xem', 'hỏi về', 'hoi ve', 'về', 've',
            'ạ', 'ơi', 'nha', 'nhé', 'với', 'co nhung gi', 'có những gì',
            'không có thật', 'khong co that', 'không', 'khong', 'có thật', 'co that', 'thật', 'that'
        ];

        $cleanQuery = $msgLower;
        foreach ($intentWords as $iw) {
            $cleanQuery = preg_replace('/(?:\b|^)' . preg_quote($iw, '/') . '(?:\b|$)/u', ' ', $cleanQuery);
        }
        $cleanQuery = trim(preg_replace('/\s+/', ' ', $cleanQuery));
        $cleanNoAcc = removeVietnameseAccents($cleanQuery);

        $medicalSynonyms = [
            'tiểu đường' => ['glucose', 'đường huyết'],
            'tieu duong' => ['glucose', 'duong huyet'],
            'đường huyết' => ['glucose', 'đường huyết'],
            'mỡ máu'     => ['cholesterol', 'triglycerid', 'lipid'],
            'mo mau'     => ['cholesterol', 'triglycerid', 'lipid'],
            'men gan'    => ['men gan', 'ast', 'alt', 'got', 'gpt'],
            'nước tiểu'  => ['nước tiểu', 'urinalysis', 'tổng phân tích nước tiểu'],
            'nuoc tieu'  => ['nuoc tieu', 'urinalysis'],
            'nhổ răng'   => ['răng', 'nhổ'],
            'nho rang'   => ['rang', 'nho'],
            'răng'       => ['răng', 'nha chu', 'cao răng'],
            'rang'       => ['rang', 'nha chu', 'cao rang'],
            'mắt'        => ['mắt', 'thị lực', 'đáy mắt', 'khúc xạ', 'hốc mắt'],
            'mat'        => ['mat', 'thi luc', 'day mat', 'khuc xa', 'hoc mat'],
            'máu'        => ['máu', 'huyết', 'công thức máu', 'tế bào máu'],
            'mau'        => ['mau', 'huyet', 'cong thuc mau', 'te bao mau'],
            'x-quang'    => ['xquang', 'x-quang', 'x quang'],
            'x quang'    => ['xquang', 'x-quang', 'x quang'],
            'xquang'     => ['xquang', 'x-quang', 'x quang'],
            'siêu âm'    => ['siêu âm', 'sieu am'],
            'sieu am'    => ['siêu âm', 'sieu am'],
            'nội soi'    => ['nội soi', 'noi soi'],
            'noi soi'    => ['nội soi', 'noi soi'],
            'điện tim'   => ['điện tim', 'điện tâm đồ', 'ecg'],
            'dien tim'   => ['dien tim', 'dien tam do', 'ecg'],
            'ecg'        => ['điện tim', 'điện tâm đồ', 'ecg'],
            'khám thai'  => ['khám thai', 'siêu âm thai', 'phụ sản'],
            'kham thai'  => ['kham thai', 'sieu am thai', 'phu san'],
            'phụ khoa'   => ['phụ khoa', 'phụ sản', 'tử cung', 'âm đạo'],
            'phu khoa'   => ['phu khoa', 'phu san', 'tu cung', 'am dao'],
        ];

        $isGeneral = (mb_strlen($cleanQuery, 'UTF-8') < 2);

        if (!$isGeneral) {
            $userHasAccents = hasVietnameseAccents($cleanQuery);
            $synList = [];
            foreach ($medicalSynonyms as $phrase => $syns) {
                if ($userHasAccents) {
                    if (str_contains($cleanQuery, $phrase)) {
                        $synList = array_merge($synList, $syns);
                    }
                } else {
                    if (str_contains($cleanNoAcc, removeVietnameseAccents($phrase))) {
                        $synList = array_merge($synList, array_map('removeVietnameseAccents', $syns));
                    }
                }
            }
            $synList = array_unique($synList);

            $queryTokens = array_filter(explode(' ', $cleanQuery), fn($w) => mb_strlen($w, 'UTF-8') >= 2);
            $queryTokensNoAcc = array_filter(explode(' ', $cleanNoAcc), fn($w) => strlen($w) >= 2);

            $scored = [];
            foreach ($allServices as $svc) {
                $score = 0;
                $nameLower = $svc['name_lower'];
                $nameNoAcc = $svc['name_no_acc'];

                if ($userHasAccents) {
                    if (str_contains($nameLower, $cleanQuery)) {
                        $score += 200;
                        if (str_starts_with($nameLower, $cleanQuery)) {
                            $score += 50;
                        }
                    }
                    foreach ($synList as $syn) {
                        if (str_contains($nameLower, $syn)) {
                            $score += 100;
                        }
                    }
                    $matchedTokens = 0;
                    foreach ($queryTokens as $tok) {
                        if (preg_match('/(?:\b|^)' . preg_quote($tok, '/') . '(?:\b|$)/u', $nameLower)) {
                            $score += 30;
                            $matchedTokens++;
                        }
                    }
                    if (count($queryTokens) > 1 && $matchedTokens === count($queryTokens)) {
                        $score += 60;
                    }
                } else {
                    if (str_contains($nameNoAcc, $cleanNoAcc)) {
                        $score += 150;
                        if (str_starts_with($nameNoAcc, $cleanNoAcc)) {
                            $score += 40;
                        }
                    }
                    foreach ($synList as $syn) {
                        if (str_contains($nameNoAcc, $syn)) {
                            $score += 80;
                        }
                    }
                    $matchedTokens = 0;
                    foreach ($queryTokensNoAcc as $tok) {
                        if (preg_match('/(?:\b|^)' . preg_quote($tok, '/') . '(?:\b|$)/', $nameNoAcc)) {
                            $score += 25;
                            $matchedTokens++;
                        }
                    }
                    if (count($queryTokensNoAcc) > 1 && $matchedTokens === count($queryTokensNoAcc)) {
                        $score += 50;
                    }
                }

                if ($score < 50) {
                    continue;
                }

                $score -= min(20, (int)(mb_strlen($svc['name'], 'UTF-8') / 6));

                $scored[] = ['score' => $score, 'service' => $svc];
            }

            if (!empty($scored)) {
                usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
                $matched = array_map(fn($item) => $item['service'], array_slice($scored, 0, $limit));

                return [
                    'type'     => 'specific',
                    'keyword'  => $cleanQuery,
                    'services' => $matched,
                ];
            }

            return [
                'type'     => 'not_found',
                'keyword'  => $cleanQuery,
                'services' => [],
            ];
        }

        // Trường hợp hỏi chung chung / click Bảng giá: chọn 8-10 dịch vụ tiêu biểu các chuyên khoa
        $popularPatterns = [
            ['khám nội tổng hợp', 'khám nội', 'kham noi'],
            ['khám ngoại', 'kham ngoai'],
            ['cấp cứu', 'sơ cứu', 'khâu vết thương'],
            ['siêu âm tổng quát', 'siêu âm ổ bụng', 'siêu âm'],
            ['chụp xquang', 'chụp x-quang', 'x-quang'],
            ['xét nghiệm máu', 'xét nghiệm công thức máu', 'tổng phân tích tế bào máu'],
            ['glucose', 'định lượng glucose', 'đường huyết'],
            ['điện tim', 'điện tâm đồ', 'ecg'],
            ['nội soi tai mũi họng', 'nội soi'],
            ['khám phụ sản', 'khám thai', 'khám phụ khoa'],
        ];

        $generalList = [];
        $usedKeys = [];

        foreach ($popularPatterns as $patterns) {
            if (count($generalList) >= $limit) break;
            foreach ($allServices as $svc) {
                $matched = false;
                foreach ($patterns as $p) {
                    if (str_contains($svc['name_lower'], $p) || str_contains($svc['name_no_acc'], removeVietnameseAccents($p))) {
                        $matched = true;
                        break;
                    }
                }
                if ($matched && !isset($usedKeys[$svc['name']])) {
                    $generalList[] = $svc;
                    $usedKeys[$svc['name']] = true;
                    break;
                }
            }
        }

        if (count($generalList) < $limit) {
            foreach ($allServices as $svc) {
                if (count($generalList) >= $limit) break;
                if (!isset($usedKeys[$svc['name']])) {
                    $generalList[] = $svc;
                    $usedKeys[$svc['name']] = true;
                }
            }
        }

        return [
            'type'     => 'general',
            'keyword'  => '',
            'services' => $generalList,
        ];
    }
}

if (!function_exists('buildMarkdownPricingReply')) {
    function buildMarkdownPricingReply(array $searchResult, string $clinicName, string $hotline): string {
        $services = $searchResult['services'];
        $type     = $searchResult['type'];
        $keyword  = $searchResult['keyword'];

        if ($type === 'not_found' || empty($services)) {
            $kwDisplay = $keyword !== '' ? " \"{$keyword}\"" : '';
            return "Dạ, hiện tại hệ thống chưa tìm thấy thông tin đơn giá chính xác cho dịch vụ{$kwDisplay} tại {$clinicName}.\n\n"
                . "💡 *Quý khách có thể nhập tên dịch vụ cụ thể khác (VD: khám nội, siêu âm, x-quang, xét nghiệm máu, nội soi, mắt...) để tra cứu.*\n\n"
                . "📞 Hoặc liên hệ trực tiếp hotline **{$hotline}** để được nhân viên y tế hỗ trợ bảng giá và tư vấn tận tình!";
        }

        $out = '';
        if ($type === 'specific' && $keyword !== '') {
            $out .= "💰 **Bảng giá dịch vụ liên quan đến \"{$keyword}\" tại {$clinicName}:**\n\n";
        } else {
            $out .= "💰 **Bảng giá một số dịch vụ y tế phổ biến tại {$clinicName}:**\n\n";
        }

        $out .= "*(Vuốt bảng sang phải để xem Đơn giá và BHYT trên điện thoại)*\n\n";
        $out .= "| STT | Mã DV | Tên dịch vụ y tế | Đơn giá (VNĐ) | Áp dụng BHYT |\n";
        $out .= "|---|---|---|---|---|\n";

        $stt = 1;
        foreach ($services as $s) {
            $out .= sprintf(
                "| %d | %s | %s | %s | %s |\n",
                $stt++,
                $s['code'],
                $s['name'],
                $s['price'],
                $s['bhyt']
            );
        }

        $out .= "\n💡 *Quý khách có thể nhập tên dịch vụ cụ thể (VD: siêu âm, xét nghiệm, nội soi, nhổ răng, tiểu đường, mắt...) để tra cứu chính xác đơn giá.*\n";
        $out .= "📞 Để được tư vấn chi tiết hoặc đặt lịch khám, Quý khách vui lòng gọi Hotline: **{$hotline}**.";

        return $out;
    }
}

if (!function_exists('getSmartPricingFallback')) {
    function getSmartPricingFallback(string $pricingText, string $userMessage, string $clinicName, string $hotline, int $limit = 10): string {
        $allServices = parsePricingServices($pricingText);
        $searchResult = searchPricingServices($allServices, $userMessage, $limit);
        return buildMarkdownPricingReply($searchResult, $clinicName, $hotline);
    }
}

// ── Vòng lặp: thử TẤT CẢ KEY với cùng model tier trước, rồi xuống tier thấp hơn ──
// Chiến lược này ưu tiên giữ model chất lượng cao, tận dụng quota từ nhiều key khác nhau
$result    = null;
$usedKey   = '';
$usedModel = '';

if (!empty($allApiKeys)) {
    foreach ($modelTiers as [$tryModel, $supportsThinking, $supportsSearch]) {
        $tryPayload = buildPayload($payload, $supportsThinking, $supportsSearch);
        foreach ($allApiKeys as $tryKey) {
            $res = callGemini($tryKey, $tryModel, $tryPayload);

            if ($res['curl_error'] !== '') {
                // Lỗi mạng — thử key tiếp
                continue;
            }

            if ($res['code'] === 200) {
                $result    = $res;
                $usedKey   = $tryKey;
                $usedModel = $tryModel;
                break 2; // Thoát cả 2 vòng
            }

            if ($res['code'] === 429 || $res['code'] === 503) {
                // Quota hết / overload trên key này — thử key khác cùng model
                continue;
            }

            if ($res['code'] === 400 || $res['code'] === 404) {
                // Model không hỗ trợ / params lỗi — bỏ model này, không cần thử key khác
                break; // sang model tier thấp hơn
            }

            // Lỗi 401/403 (key sai) — thử key tiếp
            continue;
        }
    }
}

if ($result === null || $result['code'] !== 200) {
    $lastCode = $result['code'] ?? 0;
    if (!empty($allApiKeys)) {
        security_log('ai_chat_api_error', ['http_code' => $lastCode, 'all_keys_exhausted' => true]);
    }

    // ── Smart fallback: trả lời tĩnh dựa trên từ khóa khi AI quá tải hoặc chưa cấu hình API key ──
    $msgLower = mb_strtolower($message, 'UTF-8');
    $staticReply = null;

    if (preg_match('/giá|chi phí|phí|bảng giá|tiền|bao nhiêu|báo giá/u', $msgLower)) {
        $staticReply = getSmartPricingFallback(
            (string)($clinicSettings['ai_prompt_pricing'] ?? ''),
            $message,
            (string)$clinicSettings['clinic_name'],
            (string)$clinicSettings['support_hotline'],
            10
        );
    } elseif (preg_match('/quy trình|thủ tục|hướng dẫn khám|các bước khám|đăng ký khám|đặt lịch/u', $msgLower)) {
        $staticReply = "📋 **Quy trình khám bệnh** tại {$clinicSettings['clinic_name']}:\n\n"
            . $clinicSettings['ai_prompt_procedures']
            . "\n\n📞 Hotline: **{$clinicSettings['support_hotline']}**.";
    } elseif (preg_match('/địa chỉ|ở đâu|vị trí|đường|tỉnh|đi như thế nào/u', $msgLower)) {
        $addr = $clinicSettings['clinic_address'] ?: 'Vui lòng liên hệ hotline.';
        $mapLink = $clinicSettings['google_maps_url'] ? "\n🗺️ Bản đồ: {$clinicSettings['google_maps_url']}" : '';
        $staticReply = "📍 **Địa chỉ** {$clinicSettings['clinic_name']}:\n{$addr}{$mapLink}"
            . "\n\n📞 Hotline: **{$clinicSettings['support_hotline']}**.";
    } elseif (preg_match('/bhyt|bảo hiểm|thẻ bảo hiểm|đúng tuyến|trái tuyến|chuyển tuyến|mức hưởng/u', $msgLower)) {
        $staticReply = "🏥 **Chính sách BHYT** tại {$clinicSettings['clinic_name']}:\n\n"
            . $clinicSettings['ai_prompt_benefits']
            . "\n\n📞 Hotline: **{$clinicSettings['support_hotline']}**.";
    } elseif (preg_match('/hồ sơ|giấy tờ|mang theo|cần gì|cccd|chứng minh|chuẩn bị/u', $msgLower)) {
        $staticReply = "📄 **Hồ sơ cần mang** khi đến {$clinicSettings['clinic_name']}:\n\n"
            . $clinicSettings['ai_prompt_documents']
            . "\n\n📞 Hotline: **{$clinicSettings['support_hotline']}**.";
    } elseif (preg_match('/hotline|liên hệ|điện thoại|gọi|số điện/u', $msgLower)) {
        $staticReply = "📞 **Liên hệ** {$clinicSettings['clinic_name']}:\n"
            . "• Hotline: **{$clinicSettings['support_hotline']}**\n"
            . ($clinicSettings['support_email'] ? "• Email: {$clinicSettings['support_email']}\n" : '')
            . "• Địa chỉ: {$clinicSettings['clinic_address']}";
    } elseif (preg_match('/giờ|mở cửa|làm việc|hoạt động|thứ|buổi sáng|buổi chiều/u', $msgLower)) {
        $sched = get_appointment_schedule();
        $dayNames = ['1'=>'Thứ Hai','2'=>'Thứ Ba','3'=>'Thứ Tư','4'=>'Thứ Năm','5'=>'Thứ Sáu','6'=>'Thứ Bảy','7'=>'Chủ Nhật'];
        $schedText = "🕐 **Giờ làm việc** {$clinicSettings['clinic_name']}:\n";
        foreach ($dayNames as $k => $name) {
            $d = $sched[$k] ?? [];
            if (!empty($d['open'])) {
                $line = "• {$name}: {$d['from']} – {$d['to']}";
                if (!empty($d['break_open'])) {
                    $line .= " (nghỉ trưa {$d['break_from']}–{$d['break_to']})";
                }
                $schedText .= $line . "\n";
            } else {
                $schedText .= "• {$name}: Nghỉ\n";
            }
        }
        $staticReply = $schedText . "\n📞 Hotline: **{$clinicSettings['support_hotline']}**.";
    }

    // Nếu người dùng không dùng từ 'giá' nhưng gõ thẳng tên dịch vụ y tế (VD: siêu âm, xét nghiệm, nội soi, nhổ răng, x-quang, khám mắt...)
    if ($staticReply === null) {
        $allServices = parsePricingServices((string)($clinicSettings['ai_prompt_pricing'] ?? ''));
        $searchRes   = searchPricingServices($allServices, $message, 10);
        if ($searchRes['type'] === 'specific' && !empty($searchRes['services'])) {
            $staticReply = buildMarkdownPricingReply($searchRes, (string)$clinicSettings['clinic_name'], (string)$clinicSettings['support_hotline']);
        }
    }

    if ($staticReply === null) {
        $staticReply = "Xin chào! Tôi là trợ lý của **{$clinicSettings['clinic_name']}**.\n\n"
            . "Hệ thống AI đang tạm bận, nhưng tôi có thể hỗ trợ về:\n"
            . "• 💰 Bảng giá dịch vụ\n• 📋 Quy trình khám\n• 🏥 Chính sách BHYT\n• 📍 Địa chỉ & giờ làm việc\n\n"
            . "📞 Hoặc gọi hotline **{$clinicSettings['support_hotline']}** để được hỗ trợ trực tiếp!";
    }

    $staticReply = trim($staticReply);
    if ($staticReply !== '') {
        $staticReply = preg_replace('/(?:\n\s*)*\**\s*Liên\s+hệ\s+đến\s+số\s+Hotline\s+để\s+biết\s+thông\s+tin\s+chi\s+tiết\s*!?\s*\**\s*$/iu', '', $staticReply);
        $staticReply .= "\n\n***Liên hệ đến số Hotline để biết thông tin chi tiết !***";
    }
    echo json_encode([
        'reply'      => $staticReply,
        'used_key'   => '',
        'used_model' => 'static-fallback',
        'key_source' => 'fallback',
    ], JSON_UNESCAPED_UNICODE);
    exit;

}

// ── Lấy kết quả ──
$data      = json_decode($result['body'], true);
$replyText = $data['candidates'][0]['content']['parts'][0]['text']
    ?? 'Xin lỗi, tôi không thể trả lời lúc này. Vui lòng thử lại sau hoặc liên hệ trực tiếp phòng khám.';

// Mask key: hiển thị 8 ký tự đầu + *** + 4 ký tự cuối
$maskedKey = '';
if ($usedKey !== '') {
    $len = strlen($usedKey);
    $maskedKey = substr($usedKey, 0, 8) . str_repeat('*', max(0, $len - 12)) . substr($usedKey, -4);
}

// Xác định nguồn key
$keySource = 'unknown';
if ($usedKey === $primaryKey) {
    $keySource = 'primary';
} else {
    $pos = array_search($usedKey, array_values($extraKeys), true);
    if ($pos !== false) {
        $keySource = 'backup_' . ($pos + 1);
    }
}

$replyText = trim($replyText);
if ($replyText !== '') {
    $replyText = preg_replace('/(?:\n\s*)*\**\s*Liên\s+hệ\s+đến\s+số\s+Hotline\s+để\s+biết\s+thông\s+tin\s+chi\s+tiết\s*!?\s*\**\s*$/iu', '', $replyText);
    $replyText .= "\n\n***Liên hệ đến số Hotline để biết thông tin chi tiết !***";
}
echo json_encode([
    'reply'      => $replyText,
    'used_key'   => $maskedKey,
    'used_model' => $usedModel,
    'key_source' => $keySource,
], JSON_UNESCAPED_UNICODE);
