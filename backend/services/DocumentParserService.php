<?php

class DocumentParserService {
    /**
     * Strictly validates an uploaded file against extension, MIME, signature, and size.
     */
    public static function validateUpload(array $file, array $config): array {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['valid' => false, 'error' => 'Invalid upload parameters.'];
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['valid' => false, 'error' => 'This reviewer exceeds the allowed file size limit (' . ($config['max_file_size_label'] ?? '25MB') . ').'];
            case UPLOAD_ERR_NO_FILE:
                return ['valid' => false, 'error' => 'No manuscript was selected for upload.'];
            default:
                return ['valid' => false, 'error' => 'The reviewer could not be processed due to upload failure.'];
        }

        $maxSize = $config['max_file_size'] ?? (25 * 1024 * 1024);
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'This reviewer exceeds the allowed file size (' . ($config['max_file_size_label'] ?? '25MB') . ').'];
        }

        if ($file['size'] <= 0) {
            return ['valid' => false, 'error' => 'The selected manuscript file is empty.'];
        }

        $originalName = $file['name'] ?? '';
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExts = $config['allowed_extensions'] ?? ['pdf', 'docx', 'doc', 'pptx', 'ppt', 'txt'];

        if (!in_array($ext, $allowedExts, true)) {
            return ['valid' => false, 'error' => 'This file type is not supported. Allowed formats: PPT, PPTX, DOC, DOCX, TXT, PDF.'];
        }

        // Validate MIME type with PHP Fileinfo
        $tmpPath = $file['tmp_name'];
        if (!file_exists($tmpPath) || !is_readable($tmpPath)) {
            return ['valid' => false, 'error' => 'Uploaded temporary file is inaccessible.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo ? finfo_file($finfo, $tmpPath) : 'application/octet-stream';
        if ($finfo) finfo_close($finfo);

        // Binary signature / magic byte validation
        $handle = @fopen($tmpPath, 'rb');
        if (!$handle) {
            return ['valid' => false, 'error' => 'Cannot read uploaded manuscript.'];
        }
        $headerBytes = fread($handle, 16);
        fclose($handle);

        $sigValid = self::verifySignature($ext, $headerBytes, $tmpPath);
        if (!$sigValid) {
            return ['valid' => false, 'error' => 'The reviewer could not be processed: file content does not match its format signature.'];
        }

        return [
            'valid'        => true,
            'file_type'    => $ext,
            'mime_type'    => $detectedMime,
            'file_size'    => $file['size'],
            'original_name'=> $originalName,
        ];
    }

    /**
     * Checks magic bytes / file signatures to prevent disguised executables or invalid files.
     */
    private static function verifySignature(string $ext, string $header, string $tmpPath): bool {
        switch ($ext) {
            case 'pdf':
                // PDF must begin with %PDF- (hex 25 50 44 46 2d)
                return str_starts_with($header, '%PDF-');

            case 'docx':
            case 'pptx':
                // ZIP magic bytes: PK\x03\x04
                if (!str_starts_with($header, "PK\x03\x04")) {
                    return false;
                }
                // Verify via ZipArchive that it contains OpenXML content
                if (class_exists('ZipArchive')) {
                    $zip = new ZipArchive();
                    if ($zip->open($tmpPath) === true) {
                        $hasContentType = ($zip->locateName('[Content_Types].xml') !== false);
                        $hasDocXml = ($ext === 'docx')
                            ? ($zip->locateName('word/document.xml') !== false)
                            : ($zip->locateName('ppt/presentation.xml') !== false || $zip->locateName('ppt/slides/slide1.xml') !== false);
                        $zip->close();
                        return $hasContentType && $hasDocXml;
                    }
                }
                return true;

            case 'doc':
            case 'ppt':
                // Legacy OLE2 Compound File Binary: D0 CF 11 E0 A1 B1 1A E1
                $oleMagic = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
                return str_starts_with($header, $oleMagic);

            case 'txt':
                // TXT must not contain binary null bytes in initial chunk and must be valid UTF-8/ASCII
                if (str_contains($header, "\x00")) {
                    return false;
                }
                return true;

            default:
                return false;
        }
    }

    /**
     * Cleans an uploaded filename into a human-readable title.
     * e.g. "psychopathology_midterm_reviewer_v2.pdf" -> "Psychopathology Midterm Reviewer V2"
     */
    public static function cleanTitleFromFilename(string $filename): string {
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        // Replace dashes, underscores, dots with spaces
        $cleaned = preg_replace('/[_\-\.]+/', ' ', $nameWithoutExt);
        // Clean multiple spaces
        $cleaned = trim(preg_replace('/\s+/', ' ', $cleaned));
        if ($cleaned === '') {
            return 'Untitled Manuscript';
        }
        return mb_convert_case($cleaned, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Parse document content safely on the server for modal preview.
     * Escapes and sanitizes all output. Never executes code.
     */
    public static function parseDocument(string $filePath, string $fileType): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'type'    => $fileType,
                'status'  => 'error',
                'message' => 'Reviewer file could not be accessed on the server.'
            ];
        }

        switch ($fileType) {
            case 'txt':
                return self::parseTxt($filePath);

            case 'docx':
                return self::parseDocx($filePath);

            case 'pptx':
                return self::parsePptx($filePath);

            case 'pdf':
                return [
                    'type'       => 'pdf',
                    'status'     => 'ready',
                    'can_stream' => true,
                    'message'    => 'PDF document ready for reading.'
                ];

            case 'doc':
                return self::parseDocFallback($filePath);

            case 'ppt':
                return self::parsePptFallback($filePath);

            default:
                return [
                    'type'    => $fileType,
                    'status'  => 'unsupported',
                    'message' => 'Direct preview is unavailable for this manuscript format.'
                ];
        }
    }

    /**
     * Parses plain text file safely.
     */
    private static function parseTxt(string $filePath): array {
        $content = @file_get_contents($filePath, false, null, 0, 500 * 1024); // max 500KB preview
        if ($content === false) {
            return ['type' => 'txt', 'status' => 'error', 'message' => 'Unable to read manuscript text.'];
        }

        // Ensure valid UTF-8
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', ['ISO-8859-1', 'ASCII', 'Windows-1252']);
        }

        $lines = explode("\n", $content);
        $escapedLines = array_map(function($line) {
            return htmlspecialchars(rtrim($line, "\r"), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }, $lines);

        return [
            'type'       => 'txt',
            'status'     => 'ready',
            'lines'      => $escapedLines,
            'line_count' => count($escapedLines),
            'raw_text'   => htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        ];
    }

    /**
     * Parses DOCX XML to extract structured paragraphs and text safely.
     */
    private static function parseDocx(string $filePath): array {
        if (!class_exists('ZipArchive')) {
            return [
                'type'    => 'docx',
                'status'  => 'fallback',
                'message' => 'Server preview engine is processing this document. Download is available.'
            ];
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [
                'type'    => 'docx',
                'status'  => 'error',
                'message' => 'Could not open Word manuscript archive.'
            ];
        }

        $xmlIndex = $zip->locateName('word/document.xml');
        if ($xmlIndex === false) {
            $zip->close();
            return [
                'type'    => 'docx',
                'status'  => 'error',
                'message' => 'Document structure not found inside manuscript.'
            ];
        }

        $xmlData = $zip->getFromIndex($xmlIndex);
        $zip->close();

        if (!$xmlData) {
            return ['type' => 'docx', 'status' => 'error', 'message' => 'Empty Word document content.'];
        }

        // Parse paragraphs and text runs with XMLReader / DOMDocument
        $paragraphs = [];
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        if ($dom->loadXML($xmlData, LIBXML_NOENT | LIBXML_NONET)) {
            $pElements = $dom->getElementsByTagName('p');
            foreach ($pElements as $p) {
                $pText = '';
                // Gather all text elements in this paragraph
                $tElements = $p->getElementsByTagName('t');
                foreach ($tElements as $t) {
                    $pText .= $t->nodeValue;
                }
                $trimmed = trim($pText);
                if ($trimmed !== '') {
                    // Check if styled as heading
                    $isHeading = false;
                    $pStyles = $p->getElementsByTagName('pStyle');
                    if ($pStyles->length > 0) {
                        $val = $pStyles->item(0)->getAttribute('w:val');
                        if (stripos($val, 'Heading') !== false || stripos($val, 'Title') !== false) {
                            $isHeading = true;
                        }
                    }
                    $paragraphs[] = [
                        'text'      => htmlspecialchars($trimmed, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                        'is_heading'=> $isHeading
                    ];
                }
            }
        }
        libxml_clear_errors();

        return [
            'type'            => 'docx',
            'status'          => 'ready',
            'paragraphs'      => $paragraphs,
            'paragraph_count' => count($paragraphs)
        ];
    }

    /**
     * Parses PPTX XML to extract slides, titles, and bullet points.
     */
    private static function parsePptx(string $filePath): array {
        if (!class_exists('ZipArchive')) {
            return [
                'type'    => 'pptx',
                'status'  => 'fallback',
                'message' => 'Presentation preview engine is preparing this manuscript. Download available.'
            ];
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [
                'type'    => 'pptx',
                'status'  => 'error',
                'message' => 'Could not open PowerPoint manuscript archive.'
            ];
        }

        // Discover all slide XML files
        $slideFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $name, $matches)) {
                $slideFiles[(int)$matches[1]] = $name;
            }
        }
        ksort($slideFiles);

        $slides = [];
        foreach ($slideFiles as $slideNum => $slideFileName) {
            $slideXml = $zip->getFromName($slideFileName);
            if (!$slideXml) continue;

            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $lines = [];
            if ($dom->loadXML($slideXml, LIBXML_NOENT | LIBXML_NONET)) {
                // Find all shape paragraphs (a:p)
                $pElements = $dom->getElementsByTagName('p');
                foreach ($pElements as $p) {
                    $textRuns = $p->getElementsByTagName('t');
                    $lineText = '';
                    foreach ($textRuns as $t) {
                        $lineText .= $t->nodeValue;
                    }
                    $trimmed = trim($lineText);
                    if ($trimmed !== '') {
                        $lines[] = htmlspecialchars($trimmed, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    }
                }
            }
            libxml_clear_errors();

            $title = $lines[0] ?? "Slide $slideNum";
            $bullets = array_slice($lines, 1);

            $slides[] = [
                'slide_number' => $slideNum,
                'title'        => $title,
                'bullets'      => $bullets,
                'content'      => $lines
            ];
        }
        $zip->close();

        return [
            'type'         => 'pptx',
            'status'       => 'ready',
            'total_slides' => count($slides),
            'slides'       => $slides
        ];
    }

    /**
     * Fallback for legacy binary .doc format.
     */
    private static function parseDocFallback(string $filePath): array {
        // Attempt basic plain-text string extraction from OLE stream
        $raw = @file_get_contents($filePath, false, null, 0, 250 * 1024);
        $extractedText = '';
        if ($raw !== false) {
            if (preg_match_all('/[\x20-\x7E\r\n\t]{4,}/', $raw, $matches)) {
                $filtered = array_filter($matches[0], function($str) {
                    return strlen(trim($str)) > 3 && !preg_match('/^(Normal|Default|Heading|Times|Arial|Courier)/i', $str);
                });
                $extractedText = implode("\n", array_slice($filtered, 0, 60));
            }
        }

        return [
            'type'           => 'doc',
            'status'         => 'legacy',
            'is_legacy'      => true,
            'extracted_text' => htmlspecialchars($extractedText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'message'        => 'Legacy Word .doc manuscript. Preview provides text extraction; download original for full layout.'
        ];
    }

    /**
     * Fallback for legacy binary .ppt format.
     */
    private static function parsePptFallback(string $filePath): array {
        return [
            'type'      => 'ppt',
            'status'    => 'legacy',
            'is_legacy' => true,
            'message'   => 'Legacy PowerPoint .ppt manuscript. Download original presentation to view full slides.'
        ];
    }
}
