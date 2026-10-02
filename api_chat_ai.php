<?php
declare(strict_types=1);

/**
 * api_chat_ai.php - Trợ lý AI Phòng Khám
 * Model cascade: gemini-2.5-pro → 2.5-flash → 2.0-flash → 2.0-flash-lite → 1.5-flash → 1.5-flash-8b
 * Tự động giảm tier model khi quota hết hoặc lỗi, đảm bảo luôn có phản hồi.
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Đọc input
$rawInput = file_get_contents('php://input');
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

if (empty($allApiKeys)) {
    echo json_encode([
        'reply' => 'Xin lỗi, tính năng Chatbot AI hiện chưa được cấu hình. Vui lòng gọi hotline hoặc đến trực tiếp phòng khám để được hỗ trợ.'
    ]);
    exit;
}

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
4. Không tư vấn điều trị chuyên sâu, không chẩn đoán bệnh cụ thể — hãy khuyến nghị bệnh nhân đến gặp bác sĩ.
5. Nếu không chắc về thông tin phòng khám: "Vui lòng liên hệ hotline {$clinicSettings['support_hotline']} để được hỗ trợ chính xác hơn."
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
    curl_close($ch);
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

// ── Vòng lặp: thử TẤT CẢ KEY với cùng model tier trước, rồi xuống tier thấp hơn ──
// Chiến lược này ưu tiên giữ model chất lượng cao, tận dụng quota từ nhiều key khác nhau
$result    = null;
$usedKey   = '';
$usedModel = '';

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



if ($result === null || $result['code'] !== 200) {
    $lastCode = $result['code'] ?? 0;
    security_log('ai_chat_api_error', ['http_code' => $lastCode, 'all_keys_exhausted' => true]);

    // ── Smart fallback: trả lời tĩnh dựa trên từ khóa khi AI quá tải ──
    $msgLower = mb_strtolower($message, 'UTF-8');
    $staticReply = null;

    if (preg_match('/giá|chi phí|phí|bảng giá|tiền|bao nhiêu/u', $msgLower)) {
        $staticReply = "💰 **Bảng giá dịch vụ** tại {$clinicSettings['clinic_name']}:\n\n"
            . $clinicSettings['ai_prompt_pricing']
            . "\n\n📞 Hotline: **{$clinicSettings['support_hotline']}**.";
    } elseif (preg_match('/quy trình|thủ tục|khám|đăng ký|đặt lịch|hướng dẫn|các bước/u', $msgLower)) {
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
