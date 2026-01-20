<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Simple debug logger (appends)
function dbg($msg) {
    $f = __DIR__ . '/uploads/appraisal_files/upload_debug.log';
    @file_put_contents($f, "[".date('Y-m-d H:i:s')."] ".$msg.PHP_EOL, FILE_APPEND);
}

// auth
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit();
}

$faculty_id = $_SESSION['user_id'];
// Accept both POST styles:
// 1) AJAX: files[] + part + qno
// 2) Direct form submit: keys like A2_files[], B3_files[], C10_files[]

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    exit();
}

// Basic params from AJAX style (may be empty if using HTML inputs style)
$appraisal_id = intval($_POST['appraisal_id'] ?? 0);
$ajax_part = $_POST['part'] ?? '';
$ajax_qno = intval($_POST['qno'] ?? 0);

// directory base
$baseUploadDir = __DIR__ . "/uploads/appraisal_files/";
// ensure base exists
if (!is_dir($baseUploadDir)) {
    @mkdir($baseUploadDir, 0755, true);
}

// Collect processed results to return
$processed = [];

// Helper: save files array, return saved paths
function save_files_array($filesArray, $destDir) {
    $saved = [];
    // filesArray should be like $_FILES['some_key']
    // where filesArray['name'] can be array (multiple) or single
    if (!isset($filesArray['name'])) return $saved;

    // Normalize to arrays
    $names = $filesArray['name'];
    $tmps = $filesArray['tmp_name'];
    $errs  = $filesArray['error'];
    $sizes = $filesArray['size'];

    // If single file, make it array
    if (!is_array($names)) {
        $names = [$names];
        $tmps  = [$tmps];
        $errs  = [$errs];
        $sizes = [$sizes];
    }

    foreach ($names as $i => $origName) {
        $err = $errs[$i] ?? UPLOAD_ERR_NO_FILE;
        $tmp = $tmps[$i] ?? '';
        $size = $sizes[$i] ?? 0;

        if ($err !== UPLOAD_ERR_OK) {
            // skip but continue
            continue;
        }
        if (empty($tmp) || !is_uploaded_file($tmp)) {
            continue;
        }

        // sanitize & unique
        $safe = preg_replace("/[^A-Za-z0-9_\-.]/", "_", basename($origName));
        $fileName = time() . "_" . bin2hex(random_bytes(4)) . "_" . $safe;
        $target = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        if (!is_dir(dirname($target))) {
            @mkdir(dirname($target), 0755, true);
        }

        if (move_uploaded_file($tmp, $target)) {
            // Store relative path (from project root) so DB values match your existing layout
            $rel = str_replace('\\','/', str_replace(__DIR__ . '/', '', $target));
            $saved[] = $rel;
        }
    }
    return $saved;
}

// CASE 1: AJAX style with files[] (key 'files')
if (isset($_FILES['files']) && !empty($ajax_part) && $ajax_qno > 0 && $appraisal_id > 0) {
    $uploadDir = $baseUploadDir . $appraisal_id . '/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

    $saved = save_files_array($_FILES['files'], $uploadDir);

    // merge with existing DB row (insert if not exists)
    // fetch existing
    $stmt = $conn->prepare("SELECT upload_files FROM faculty_appraisal_responses WHERE appraisal_id=? AND part=? AND question_no=?");
    $stmt->bind_param("isi", $appraisal_id, $ajax_part, $ajax_qno);
    $stmt->execute();
    $stmt->bind_result($existing);
    $stmt->fetch();
    $stmt->close();

    $existingArr = [];
    if (!empty($existing)) {
        $tmp = json_decode($existing,true);
        if (is_array($tmp)) $existingArr = $tmp;
    }
    $all = array_merge($existingArr, $saved);
    $json = json_encode($all);

    // do insert or update
    $stmt = $conn->prepare("SELECT COUNT(*) FROM faculty_appraisal_responses WHERE appraisal_id=? AND part=? AND question_no=?");
    $stmt->bind_param("isi", $appraisal_id, $ajax_part, $ajax_qno);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();

    if ($cnt > 0) {
        $stmt = $conn->prepare("UPDATE faculty_appraisal_responses SET upload_files=? WHERE appraisal_id=? AND part=? AND question_no=?");
        $stmt->bind_param("sisi", $json, $appraisal_id, $ajax_part, $ajax_qno);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO faculty_appraisal_responses (appraisal_id, part, question_no, upload_files) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $appraisal_id, $ajax_part, $ajax_qno, $json);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['success'=>true, 'files'=>$saved, 'part'=>$ajax_part, 'qno'=>$ajax_qno]);
    exit();
}

// CASE 2: HTML form submit style with keys like A2_files[], B3_files[], C1_files[]
// Iterate over $_FILES keys and look for pattern
$anyProcessed = false;
foreach ($_FILES as $key => $fileInfo) {
    // key expected: e.g. "A2_files" or "B3_files" or "C10_files"
    if (preg_match('/^([ABC])(\d+)_files$/i', $key, $m)) {
        $part = strtoupper($m[1]);
        $qno = intval($m[2]);

        // Determine appraisal id: prefer $_POST['appraisal_id'], else try session or 0
        $appraisal_id = intval($_POST['appraisal_id'] ?? $appraisal_id);
        if ($appraisal_id <= 0) {
            // if no appraisal id passed, try to derive from DB (not ideal) — bail
            dbg("Missing appraisal_id while processing $key");
            continue;
        }

        $uploadDir = $baseUploadDir . $appraisal_id . '/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

        $saved = save_files_array($fileInfo, $uploadDir);
        if (empty($saved)) {
            // no files for this key
            continue;
        }

        // fetch existing
        $stmt = $conn->prepare("SELECT upload_files FROM faculty_appraisal_responses WHERE appraisal_id=? AND part=? AND question_no=?");
        $stmt->bind_param("isi", $appraisal_id, $part, $qno);
        $stmt->execute();
        $stmt->bind_result($existing_files);
        $stmt->fetch();
        $stmt->close();

        $existingArr = [];
        if (!empty($existing_files)) {
            $tmp = json_decode($existing_files,true);
            if (is_array($tmp)) $existingArr = $tmp;
        }

        $all = array_merge($existingArr, $saved);
        $json = json_encode($all);

        // insert or update:
        $stmt = $conn->prepare("SELECT COUNT(*) FROM faculty_appraisal_responses WHERE appraisal_id=? AND part=? AND question_no=?");
        $stmt->bind_param("isi", $appraisal_id, $part, $qno);
        $stmt->execute();
        $stmt->bind_result($cnt2);
        $stmt->fetch();
        $stmt->close();

        if ($cnt2 > 0) {
            $stmt = $conn->prepare("UPDATE faculty_appraisal_responses SET upload_files=? WHERE appraisal_id=? AND part=? AND question_no=?");
            $stmt->bind_param("sisi", $json, $appraisal_id, $part, $qno);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO faculty_appraisal_responses (appraisal_id, part, question_no, upload_files) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isis", $appraisal_id, $part, $qno, $json);
            $stmt->execute();
            $stmt->close();
        }

        $processed[] = ['part'=>$part,'qno'=>$qno,'files'=>$saved];
        $anyProcessed = true;
    }
}

if ($anyProcessed) {
    echo json_encode(['success'=>true,'processed'=>$processed]);
    exit();
}

// If we get here nothing matched — return helpful error + debug
dbg("No files processed. POST keys: " . json_encode(array_keys($_POST)) . " FILE keys: " . json_encode(array_keys($_FILES)));
echo json_encode(['success'=>false,'message'=>'No files processed or invalid keys','post_keys'=>array_keys($_POST),'file_keys'=>array_keys($_FILES)]);
exit();
