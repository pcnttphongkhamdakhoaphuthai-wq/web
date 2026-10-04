<?php
declare(strict_types=1);
require_once 'config.php';

// Only accessible by admins with chatbot management permission
require_admin_login();

if (!admin_can('manage_chatbot')) {
    http_response_code(403);
    echo json_encode(['error' => 'Không có quyền thao tác.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$target = trim((string)($_POST['target_field'] ?? ''));
$allowedTargets = ['ai_prompt_procedures', 'ai_prompt_pricing', 'ai_prompt_documents', 'ai_prompt_benefits'];
if (!in_array($target, $allowedTargets, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Trường đích không hợp lệ.']);
    exit;
}

if (!isset($_FILES['ai_file']) || $_FILES['ai_file']['error'] !== UPLOAD_ERR_OK) {
    $errorCode = $_FILES['ai_file']['error'] ?? -1;
    echo json_encode(['error' => 'Lỗi upload file (mã lỗi: ' . $errorCode . ')']);
    exit;
}

$tmpPath  = (string)$_FILES['ai_file']['tmp_name'];
$origName = (string)$_FILES['ai_file']['name'];
$ext      = strtolower((string)pathinfo($origName, PATHINFO_EXTENSION));
$maxBytes = 5 * 1024 * 1024; // 5 MB

if ($_FILES['ai_file']['size'] > $maxBytes) {
    echo json_encode(['error' => 'File quá lớn (tối đa 5 MB).']);
    exit;
}

$allowedExt = ['txt', 'docx', 'xlsx', 'csv', 'pdf'];
if (!in_array($ext, $allowedExt, true)) {
    echo json_encode(['error' => 'Định dạng không được hỗ trợ. Chấp nhận: .txt .docx .xlsx .csv .pdf']);
    exit;
}

// ─── Text extraction functions ───────────────────────────────────────────────

function normalize_text_encoding(string $raw): string {
    if ($raw === '') {
        return '';
    }

    // 1. Cắt bỏ ký tự UTF-8 BOM nếu có ở đầu chuỗi
    if (str_starts_with($raw, "\xEF\xBB\xBF")) {
        $raw = substr($raw, 3);
    }

    // 2. Kiểm tra UTF-8 trước tiên. Nếu hợp lệ thì giữ nguyên tuyệt đối (chống mojibake / double-encoding)
    if (mb_check_encoding($raw, 'UTF-8')) {
        return $raw;
    }

    // 3. Nhận diện UTF-16LE / UTF-16BE qua BOM
    if (str_starts_with($raw, "\xFF\xFE")) {
        return (string)mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16LE');
    }
    if (str_starts_with($raw, "\xFE\xFF")) {
        return (string)mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16BE');
    }

    // 4. Nhận diện UTF-16LE / UTF-16BE không có BOM (chứa null byte \0 giữa các ký tự)
    if (str_contains($raw, "\0")) {
        $enc = mb_detect_encoding($raw, ['UTF-16LE', 'UTF-16BE'], true);
        if ($enc) {
            return (string)mb_convert_encoding($raw, 'UTF-8', $enc);
        }
    }

    // 5. Chuyển đổi bảng mã tiếng Việt cũ: Windows-1258 hoặc TCVN3 (ABC)
    $asWin = @iconv('Windows-1258', 'UTF-8//IGNORE', $raw);
    if ($asWin !== false && function_exists('normalizer_normalize')) {
        $asWin = (string)normalizer_normalize($asWin, Normalizer::FORM_C);
    }
    $asTcvn = @iconv('TCVN', 'UTF-8//IGNORE', $raw);

    $evalVn = static function (?string $t): int {
        if ($t === null || $t === '') return -999;
        $vnChars   = (int)preg_match_all('/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/iu', $t);
        $penDouble = (int)preg_match_all('/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹ]{2}/u', $t);
        $penSym    = (int)preg_match_all('/[¶¸÷Ö®]/u', $t);
        return $vnChars - ($penDouble * 5) - ($penSym * 5);
    };

    $scoreWin  = ($asWin !== false) ? $evalVn($asWin) : -999;
    $scoreTcvn = ($asTcvn !== false) ? $evalVn($asTcvn) : -999;

    if ($scoreWin > 0 && $scoreWin >= $scoreTcvn) {
        return (string)$asWin;
    }
    if ($scoreTcvn > 0) {
        return (string)$asTcvn;
    }

    return $raw;
}

function extract_txt(string $path): string {
    $content = file_get_contents($path);
    if ($content === false) return '';
    return trim(normalize_text_encoding($content));
}

function extract_docx(string $path): string {
    if (!class_exists('ZipArchive')) return '';
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return '';

    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    if ($xml === false) return '';

    // Replace paragraph/break tags with newlines, then strip tags
    $xml = preg_replace('/<w:p[ >]/', "\n<w:p>", $xml) ?? $xml;
    $xml = preg_replace('/<w:br[^>]*\/>/', "\n", $xml) ?? $xml;
    $text = strip_tags($xml);

    // Collapse excessive blank lines
    $text = preg_replace("/(\n\s*){3,}/", "\n\n", $text) ?? $text;
    return trim($text);
}

function extract_xlsx(string $path): string {
    if (!class_exists('ZipArchive')) return '';
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return '';

    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    $sheetXml  = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if ($sheetXml === false) return '';

    // Build shared strings lookup
    $shared = [];
    if ($sharedXml !== false) {
        libxml_use_internal_errors(true);
        $sharedDom = simplexml_load_string($sharedXml);
        if ($sharedDom) {
            foreach ($sharedDom->si as $si) {
                // Collect all <t> text children
                $text = '';
                foreach ($si->r as $run) {
                    $text .= (string)($run->t ?? '');
                }
                if ($text === '') $text = (string)($si->t ?? '');
                $shared[] = $text;
            }
        }
    }

    // Parse sheet
    libxml_use_internal_errors(true);
    $sheet = simplexml_load_string($sheetXml);
    if (!$sheet) return '';

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $cell) {
            $type  = (string)($cell['t'] ?? '');
            $val   = (string)($cell->v ?? '');
            if ($type === 's') {
                $val = $shared[(int)$val] ?? '';
            } elseif ($type === 'str' || $type === 'inlineStr') {
                $val = (string)($cell->is->t ?? $val);
            }
            $cells[] = $val;
        }
        if (!empty(array_filter($cells))) {
            $rows[] = implode("\t", $cells);
        }
    }

    return trim(implode("\n", $rows));
}

function extract_csv(string $path): string {
    $raw = file_get_contents($path);
    if ($raw === false) return '';

    $raw = normalize_text_encoding($raw);
    if ($raw === '') return '';

    // Dùng php://memory để parse CSV trực tiếp trên bộ nhớ đã chuẩn hóa UTF-8, không tạo file rác trên ổ cứng
    $handle = fopen('php://memory', 'r+');
    if (!$handle) return '';
    fwrite($handle, $raw);
    rewind($handle);

    // Phát hiện ký tự phân cách (delimiter: tab, semicolon, comma)
    $firstLine = fgets($handle);
    rewind($handle);

    $tabCount   = substr_count((string)$firstLine, "\t");
    $semiCount  = substr_count((string)$firstLine, ';');
    $commaCount = substr_count((string)$firstLine, ',');
    $delim = ',';
    if ($tabCount > $commaCount && $tabCount >= $semiCount) {
        $delim = "\t";
    } elseif ($semiCount > $commaCount && $semiCount > $tabCount) {
        $delim = ';';
    }

    $rows = [];
    while (($cols = fgetcsv($handle, null, $delim, '"', '\\')) !== false) {
        $cleanCols = array_map(static fn($c) => trim((string)$c), $cols);
        if (!empty(array_filter($cleanCols, static fn($v) => $v !== ''))) {
            $rows[] = implode("\t", $cleanCols);
        }
    }
    fclose($handle);

    return trim(implode("\n", $rows));
}

function extract_pdf(string $path): string {
    // Basic PDF text extraction (works only for non-encrypted, text-based PDFs)
    $content = file_get_contents($path);
    if ($content === false) return '';

    // Extract all BT...ET blocks (text streams)
    preg_match_all('/BT\s+(.*?)\s+ET/s', $content, $matches);
    $lines = [];
    foreach ($matches[1] as $block) {
        preg_match_all('/\((.*?)\)\s*Tj/', $block, $tj);
        foreach ($tj[1] as $chunk) {
            $chunk = preg_replace('/\\\\(\d{3})/', '', $chunk) ?? $chunk;
            $chunk = str_replace(['\\n', '\\r', '\\t'], ["\n", '', ''], $chunk);
            $lines[] = trim($chunk);
        }
    }
    $text = implode("\n", array_filter($lines));
    return trim($text);
}

// ─── Route by extension ───────────────────────────────────────────────────────

$extractedText = match ($ext) {
    'txt'  => extract_txt($tmpPath),
    'docx' => extract_docx($tmpPath),
    'xlsx' => extract_xlsx($tmpPath),
    'csv'  => extract_csv($tmpPath),
    'pdf'  => extract_pdf($tmpPath),
    default => '',
};

if ($extractedText === '') {
    echo json_encode(['error' => 'Không thể trích xuất nội dung từ file này. File có thể bị mã hóa hoặc rỗng.']);
    exit;
}

// Trim to reasonable length (50,000 chars)
if (mb_strlen($extractedText) > 50000) {
    $extractedText = mb_substr($extractedText, 0, 50000) . "\n... [Đã cắt bớt do quá dài]";
}

echo json_encode([
    'success' => true,
    'text'    => $extractedText,
    'chars'   => mb_strlen($extractedText),
    'file'    => $origName,
]);
