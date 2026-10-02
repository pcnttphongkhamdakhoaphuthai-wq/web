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

function extract_txt(string $path): string {
    $content = file_get_contents($path);
    if ($content === false) return '';
    // Detect and convert encoding
    $enc = mb_detect_encoding($content, ['UTF-8', 'UTF-16', 'ISO-8859-1'], true);
    if ($enc && $enc !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $enc);
    }
    return trim($content);
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
    $rows = [];
    $enc = null;
    $raw = file_get_contents($path);
    if ($raw !== false) {
        $enc = mb_detect_encoding($raw, ['UTF-8', 'UTF-16', 'ISO-8859-1'], true);
        if ($enc && $enc !== 'UTF-8') {
            $raw = mb_convert_encoding($raw, 'UTF-8', $enc);
            // Write to tmp for fgetcsv
            $path = tempnam(sys_get_temp_dir(), 'csv_');
            file_put_contents($path, $raw);
        }
    }

    $handle = fopen($path, 'r');
    if (!$handle) return '';

    // Detect delimiter
    $firstLine = fgets($handle);
    rewind($handle);
    $delim = (substr_count((string)$firstLine, "\t") > substr_count((string)$firstLine, ",")) ? "\t" : ",";

    while (($cols = fgetcsv($handle, 0, $delim)) !== false) {
        $rows[] = implode("\t", $cols);
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
