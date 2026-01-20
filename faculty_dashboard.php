<?php
session_start();
include 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty') {
    header("Location: login.php");
    exit();
}

$faculty_id   = $_SESSION['user_id'];
$faculty_name = $_SESSION['user_name'];
$appraisal_year = date('Y');

// fetch department of faculty
$stmt = $conn->prepare("SELECT department FROM faculty WHERE faculty_id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$stmt->bind_result($faculty_department);
$stmt->fetch();
$stmt->close();

// existing appraisal
$appraisal_id = null;
$status = 'Pending';
$stmt = $conn->prepare("SELECT appraisal_id, status FROM faculty_appraisal WHERE faculty_id = ? AND appraisal_year = ?");
$stmt->bind_param("ii", $faculty_id, $appraisal_year);
$stmt->execute();
$stmt->bind_result($aid, $astatus);
if ($stmt->fetch()) {
    $appraisal_id = $aid;
    $status = $astatus;
}
$stmt->close();

// admin role
$stmt = $conn->prepare("SELECT admin_role FROM faculty_roles WHERE faculty_id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$stmt->bind_result($admin_role);
$stmt->fetch();
$stmt->close();

// existing responses
$existing = [];
if ($appraisal_id) {
    $stmt = $conn->prepare("SELECT part, question_no, answer FROM faculty_appraisal_responses WHERE appraisal_id = ?");
    $stmt->bind_param("i", $appraisal_id);
    $stmt->execute();
    $stmt->bind_result($part, $qno, $ans);
    while ($stmt->fetch()) {
        if ($part === 'A' && intval($qno) === 13) {
            $json = json_decode($ans, true);
            if (is_array($json)) {
                $existing['A']['13_1'] = $json['p7'] ?? '';
                $existing['A']['13_2'] = $json['p5_6'] ?? '';
                $existing['A']['13_3'] = $json['p4'] ?? '';
                $existing['A']['13_4'] = $json['p3'] ?? '';
            } else {
                $existing['A'][13] = $ans;
            }
        } else {
            $existing[$part][$qno] = $ans;
        }
    }
    $stmt->close();
}

// parts (unchanged)
$parts = [
    'A' => [
        1 => "What is the average academic result (excluding labs and projects) of the subjects you handled?",
        2 => "How many student projects under your guidance have resulted in conference or journal publications?",
        3 => "How many product development activities have been carried out by your students under your guidance?",
        4 => "How many students participated in seminars, workshops, symposiums, conferences, or similar co-curricular and extracurricular activities under your mentorship?",
        5 => "How many students won prizes in seminars, workshops, symposiums, conferences, or similar co-curricular and extracurricular activities under your mentorship?",
        6 => "How many students participated in project competitions or MNC contests under your guidance?",
        7 => "How many students won prizes in project competitions or MNC contests under your guidance?",
        8 => "How many students completed online certification courses (e.g., NPTEL, Coursera, Udemy, etc.) under your mentorship?",
        9 => "How many students completed internships or in-plant training programs of at least 15 days under your guidance?",
        10 => "How many students received special awards from institutes or industries under your mentorship?",
        11 => "How many students registered for competitive examinations (e.g., GATE, GRE, UPSC, etc.) under your mentorship?",
        12 => "How many students successfully cleared competitive examinations under your mentorship?",
        13 => "How many students were placed under your mentorship? (provide counts for packages below)"
    ],
    'B' => [
        1 => "How many research papers have you published in journals indexed in Scopus/SCI/UGC?",
        2 => "How many conference papers have you presented or published?",
        3 => "How many book chapters have you published with ISBN/Scopus indexing?",
        4 => "How many patents have you filed during this appraisal period?",
        5 => "How many patents have been granted to you?",
        6 => "How many copyrights (software/teaching material) have you registered?",
        7 => "How many research project proposals have you applied for (Govt/Private funding)?",
        8 => "How many funded research projects have been sanctioned to you?",
        9 => "How many Ph.D. scholars are you currently guiding?",
        10 => "How many Ph.D./M.Phil scholars have successfully completed under your guidance?"
    ],
    'C' => [
        1 => "How many companies did you help in arranging for on-campus recruitment?",
        2 => "How many guest lectures have you delivered at other institutions?",
        3 => "How many guest lectures have you delivered within your institution/department?",
        4 => "How many online certification courses (e.g., NPTEL, SWAYAM, MNC) have you completed (minimum 4 weeks)?",
        5 => "How many newsletters or magazines have you contributed to or coordinated?",
        6 => "How many industry training programs, workshops, or seminars have you participated in?",
        7 => "How many events (seminars, conferences, FDPs, etc.) have you organized?",
        8 => "How many special awards/fellowships have you received from institutes or industries?",
        9 => "How many memberships in recognized professional bodies (e.g., IEEE, ISTE) did you hold during this period?",
        10 => "How many alumni networking activities (mock interviews, guest lectures, trainings) have you organized?"
    ]
];


// handle form (unchanged)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // create appraisal row if not exists
    if (!$appraisal_id) {
        $stmt = $conn->prepare("INSERT INTO faculty_appraisal (faculty_id, department, appraisal_year, status) VALUES (?, ?, ?, 'Pending')");
        $stmt->bind_param("isi", $faculty_id, $faculty_department, $appraisal_year);
        $stmt->execute();
        $appraisal_id = $stmt->insert_id;
        $stmt->close();
    }

    // save responses
    if (isset($_POST['submit_appraisal']) && $status === 'Pending') {
        foreach ($parts['A'] as $qno => $qtext) {
    // Question 13 special case (your existing JSON logic)
    if ($qno === 13) {
        $p7   = intval($_POST['A13_1'] ?? 0);
        $p5_6 = intval($_POST['A13_2'] ?? 0);
        $p4   = intval($_POST['A13_3'] ?? 0);
        $p3   = intval($_POST['A13_4'] ?? 0);
        $ans_json = json_encode(['p7' => $p7, 'p5_6' => $p5_6, 'p4' => $p4, 'p3' => $p3]);

        $stmt = $conn->prepare("SELECT response_id FROM faculty_appraisal_responses WHERE appraisal_id=? AND part='A' AND question_no=?");
        $stmt->bind_param("ii", $appraisal_id, $qno);
        $stmt->execute();
        $stmt->bind_result($resp_id);
        if ($stmt->fetch()) {
            $stmt->close();
            $stmt2 = $conn->prepare("UPDATE faculty_appraisal_responses SET answer = ? WHERE response_id = ?");
            $stmt2->bind_param("si", $ans_json, $resp_id);
            $stmt2->execute();
            $stmt2->close();
        } else {
            $stmt->close();
            $stmt2 = $conn->prepare("INSERT INTO faculty_appraisal_responses (appraisal_id, part, question_no, answer) VALUES (?, 'A', ?, ?)");
            $stmt2->bind_param("iis", $appraisal_id, $qno, $ans_json);
            $stmt2->execute();
            $stmt2->close();
        }
        continue;
    }

    // Normal questions (A2–A12, excluding A1)
    if (isset($_POST['A' . $qno])) {
        $val = trim($_POST['A' . $qno]) ?: '0';
        $stmt = $conn->prepare("SELECT response_id FROM faculty_appraisal_responses WHERE appraisal_id=? AND part='A' AND question_no=?");
        $stmt->bind_param("ii", $appraisal_id, $qno);
        $stmt->execute();
        $stmt->bind_result($resp_id);
        if ($stmt->fetch()) {
            $stmt->close();
            $stmt2 = $conn->prepare("UPDATE faculty_appraisal_responses SET answer=? WHERE response_id=?");
            $stmt2->bind_param("si", $val, $resp_id);
            $stmt2->execute();
            $stmt2->close();
        } else {
            $stmt->close();
            $stmt2 = $conn->prepare("INSERT INTO faculty_appraisal_responses (appraisal_id, part, question_no, answer) VALUES (?, 'A', ?, ?)");
            $stmt2->bind_param("iis", $appraisal_id, $qno, $val);
            $stmt2->execute();
            $stmt2->close();
        }

        // ===========================
        // Handle File Upload (A2–A12)
        // ===========================
        if ($qno !== 1 && isset($_FILES["A{$qno}_files"])) {
            $uploadDir = "uploads/appraisal_files/";
            $uploadedFiles = [];

            foreach ($_FILES["A{$qno}_files"]["tmp_name"] as $key => $tmpName) {
                if (!empty($tmpName)) {
                    $fileName = basename($_FILES["A{$qno}_files"]["name"][$key]);
                    $targetPath = $uploadDir . time() . "_" . $fileName;
                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $uploadedFiles[] = $targetPath;
                    }
                }
            }

            if (!empty($uploadedFiles)) {
                // Save uploaded file paths (JSON encoded)
                $filesJson = json_encode($uploadedFiles);
                $stmt3 = $conn->prepare("UPDATE faculty_appraisal_responses SET upload_files=? WHERE appraisal_id=? AND part='A' AND question_no=?");
                $stmt3->bind_param("sii", $filesJson, $appraisal_id, $qno);
                $stmt3->execute();
                $stmt3->close();
            }
        }
    }
}


    foreach ($parts['B'] as $qno => $qtext) {
    $val = trim($_POST['B' . $qno] ?? '0');
    $stmt = $conn->prepare("SELECT response_id FROM faculty_appraisal_responses WHERE appraisal_id=? AND part='B' AND question_no=?");
    $stmt->bind_param("ii", $appraisal_id, $qno);
    $stmt->execute();
    $stmt->bind_result($resp_id);
    if ($stmt->fetch()) {
        $stmt->close();
        $stmt2 = $conn->prepare("UPDATE faculty_appraisal_responses SET answer=? WHERE response_id=?");
        $stmt2->bind_param("si", $val, $resp_id);
        $stmt2->execute();
        $stmt2->close();
    } else {
        $stmt->close();
        $stmt2 = $conn->prepare("INSERT INTO faculty_appraisal_responses (appraisal_id, part, question_no, answer) VALUES (?, 'B', ?, ?)");
        $stmt2->bind_param("iis", $appraisal_id, $qno, $val);
        $stmt2->execute();
        $stmt2->close();
    }

    // ===========================
    // Handle File Upload (B)
    // ===========================
    if (isset($_FILES["B{$qno}_files"])) {
        $uploadDir = "uploads/appraisal_files/";
        $uploadedFiles = [];

        foreach ($_FILES["B{$qno}_files"]["tmp_name"] as $key => $tmpName) {
            if (!empty($tmpName)) {
                $fileName = basename($_FILES["B{$qno}_files"]["name"][$key]);
                $targetPath = $uploadDir . time() . "_" . $fileName;
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $targetPath;
                }
            }
        }

        if (!empty($uploadedFiles)) {
            $filesJson = json_encode($uploadedFiles);
            $stmt3 = $conn->prepare("UPDATE faculty_appraisal_responses SET upload_files=? WHERE appraisal_id=? AND part='B' AND question_no=?");
            $stmt3->bind_param("sii", $filesJson, $appraisal_id, $qno);
            $stmt3->execute();
            $stmt3->close();
        }
    }
}

        foreach ($parts['C'] as $qno => $qtext) {
    $val = trim($_POST['C' . $qno] ?? '0');
    $stmt = $conn->prepare("SELECT response_id FROM faculty_appraisal_responses WHERE appraisal_id=? AND part='C' AND question_no=?");
    $stmt->bind_param("ii", $appraisal_id, $qno);
    $stmt->execute();
    $stmt->bind_result($resp_id);
    if ($stmt->fetch()) {
        $stmt->close();
        $stmt2 = $conn->prepare("UPDATE faculty_appraisal_responses SET answer=? WHERE response_id=?");
        $stmt2->bind_param("si", $val, $resp_id);
        $stmt2->execute();
        $stmt2->close();
    } else {
        $stmt->close();
        $stmt2 = $conn->prepare("INSERT INTO faculty_appraisal_responses (appraisal_id, part, question_no, answer) VALUES (?, 'C', ?, ?)");
        $stmt2->bind_param("iis", $appraisal_id, $qno, $val);
        $stmt2->execute();
        $stmt2->close();
    }

    // ===========================
    // Handle File Upload (C)
    // ===========================
    if (isset($_FILES["C{$qno}_files"])) {
        $uploadDir = "uploads/appraisal_files/";
        $uploadedFiles = [];

        foreach ($_FILES["C{$qno}_files"]["tmp_name"] as $key => $tmpName) {
            if (!empty($tmpName)) {
                $fileName = basename($_FILES["C{$qno}_files"]["name"][$key]);
                $targetPath = $uploadDir . time() . "_" . $fileName;
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $targetPath;
                }
            }
        }

        if (!empty($uploadedFiles)) {
            $filesJson = json_encode($uploadedFiles);
            $stmt3 = $conn->prepare("UPDATE faculty_appraisal_responses SET upload_files=? WHERE appraisal_id=? AND part='C' AND question_no=?");
            $stmt3->bind_param("sii", $filesJson, $appraisal_id, $qno);
            $stmt3->execute();
            $stmt3->close();
        }
    }
}

        header("Location: ".$_SERVER['REQUEST_URI']);
        exit();
    }

    // ✅ Approve & Forward logic
    if (isset($_POST['approve_appraisal']) && $status === 'Pending' && $appraisal_id) {

        // Fetch admin_role from faculty_roles instead of designation
        $stmt = $conn->prepare("SELECT admin_role FROM faculty_roles WHERE faculty_id = ?");
        $stmt->bind_param("i", $faculty_id);
        $stmt->execute();
        $stmt->bind_result($admin_role);
        $stmt->fetch();
        $stmt->close();

        // Determine new status based on role
        if (strtoupper($admin_role) === 'HOD') {
            $newStatus = 'Forwarded_Principal';
        } else {
            $newStatus = 'Forwarded'; // goes to HRM
        }

        $stmt = $conn->prepare("UPDATE faculty_appraisal SET status=? WHERE appraisal_id=?");
        $stmt->bind_param("si", $newStatus, $appraisal_id);
        $stmt->execute();
        $stmt->close();

        $status = $newStatus;
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}


$locked = ($status !== 'Pending');
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Faculty Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="faculty_dashboard.css">
</head>
<body>
<nav class="navbar-custom">
  <h3>FACULTY APPRAISAL SYSTEM</h3>
  <a href="logout.php" class="logout-btn">Logout</a>
</nav>

<div class="wrapper">
  <div class="sidebar">
    <h2>Faculty Panel</h2>
    <a href="#" id="dashboardLink" class="active">Dashboard</a>
    <a href="#" id="selfAppraisalsLink">Self Appraisal</a>
  </div>

  <div class="main-content">
    <div class="container">

      <!-- DASHBOARD SECTION -->
      <div id="dashboardSection">
        <h2>Welcome, <?php echo htmlspecialchars($faculty_name); ?></h2>
        <h5>Department: <?php echo htmlspecialchars($faculty_department); ?></h5>
        <p class="mt-3">Access your self-appraisal, view your appraisal status, and manage your academic achievements here.</p>
        <div class="mt-4">
          <span class="badge bg-secondary">Appraisal Year: <?php echo $appraisal_year; ?></span>
          <span class="badge bg-info">Status: <?php echo htmlspecialchars($status); ?></span>
        </div>
      </div>

      <!-- SELF APPRAISAL SECTION (all your existing form here) -->
      <div id="selfAppraisalSection" style="display:none;">
        <!-- paste your entire appraisal form HTML exactly as-is -->
        <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Welcome, <?php echo htmlspecialchars($faculty_name); ?></h3>
        <div>
            <span class="badge bg-secondary">Year: <?php echo $appraisal_year; ?></span>
            <?php if ($status !== 'Pending'): ?>
                <span class="badge bg-info">Status: <?php echo htmlspecialchars($status); ?></span>
            <?php else: ?>
                <span class="badge bg-warning text-dark">Status: <?php echo htmlspecialchars($status); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($locked): ?>
        <div class="alert alert-info">Your appraisal has been forwarded and is no longer editable.</div>
    <?php endif; ?>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Appraisal saved.</div>
    <?php endif; ?>

    <div class="mb-3">
        <button class="btn btn-outline-primary tab-btn tab-active" data-tab="A">PART A - Academic & Student Mentorship</button>
        <button class="btn btn-outline-primary tab-btn" data-tab="B">PART B - Research & Development</button>
        <button class="btn btn-outline-primary tab-btn" data-tab="C">PART C - Faculty Contribution</button>
    </div>

    <form method="post" id="appraisalForm" enctype="multipart/form-data">
        <!-- PART A -->
        <div class="tab-pane" id="tab-A">
            <?php foreach ($parts['A'] as $qno => $qtext): ?>
    <?php if ($qno === 1): ?>
        <!-- Question 1: Input only, no file upload -->
        <div class="mb-3">
            <label class="form-label"><?php echo $qno . ". " . $qtext; ?></label>
            <input type="number" name="A<?php echo $qno; ?>" class="form-control"
                   value="<?php echo isset($existing['A'][$qno]) ? htmlspecialchars($existing['A'][$qno]) : ''; ?>"
                   <?php echo $locked ? 'readonly' : ''; ?>>
        </div>

    <?php elseif ($qno >= 2 && $qno <= 12): ?>
        <!-- Questions 2–12: Input + file upload -->
        <div class="mb-3">
            <label class="form-label"><?php echo $qno . ". " . $qtext; ?></label>
            <input type="number" name="A<?php echo $qno; ?>" class="form-control"
                   value="<?php echo isset($existing['A'][$qno]) ? htmlspecialchars($existing['A'][$qno]) : ''; ?>"
                   <?php echo $locked ? 'readonly' : ''; ?>>
            <?php if (!$locked): ?>
                <input type="file" name="A<?php echo $qno; ?>_files[]" multiple class="form-control mt-1">
            <?php endif; ?>
        </div>

    <?php elseif ($qno === 13): ?>
        <!-- Question 13 special placement inputs -->
        <div class="mb-3">
            <label class="form-label"><?php echo $qno . ". " . $qtext; ?></label>
            <div class="row">
                <div class="col-md-3 mb-2">
                    <input type="number" name="A13_1" class="form-control" placeholder="Placed 7 LPA+" value="<?php echo isset($existing['A']['13_1']) ? htmlspecialchars($existing['A']['13_1']) : ''; ?>" <?php echo $locked ? 'readonly' : ''; ?>>
                    <small class="small-note">Placed 7 LPA+</small>
                </div>
                <div class="col-md-3 mb-2">
                    <input type="number" name="A13_2" class="form-control" placeholder="Placed 5–6.99 LPA" value="<?php echo isset($existing['A']['13_2']) ? htmlspecialchars($existing['A']['13_2']) : ''; ?>" <?php echo $locked ? 'readonly' : ''; ?>>
                    <small class="small-note">Placed 5–6.99 LPA</small>
                </div>
                <div class="col-md-3 mb-2">
                    <input type="number" name="A13_3" class="form-control" placeholder="Placed 4–4.99 LPA" value="<?php echo isset($existing['A']['13_3']) ? htmlspecialchars($existing['A']['13_3']) : ''; ?>" <?php echo $locked ? 'readonly' : ''; ?>>
                    <small class="small-note">Placed 4–4.99 LPA</small>
                </div>
                <div class="col-md-3 mb-2">
                    <input type="number" name="A13_4" class="form-control" placeholder="Placed 3–3.99 LPA" value="<?php echo isset($existing['A']['13_4']) ? htmlspecialchars($existing['A']['13_4']) : ''; ?>" <?php echo $locked ? 'readonly' : ''; ?>>
                    <small class="small-note">Placed 3–3.99 LPA</small>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>



            <div class="text-end">
                <button type="button" class="btn btn-primary" id="toB" <?php echo $locked ? 'disabled' : ''; ?>>Next → PART B</button>
            </div>
        </div>

        <!-- PART B -->
        <div class="tab-pane" id="tab-B" style="display:none;">
            <?php foreach ($parts['B'] as $qno => $qtext): ?>
                <div class="mb-3">
                    <label class="form-label"><?php echo $qno . ". " . $qtext; ?></label>
                    <input type="number" name="B<?php echo $qno; ?>" class="form-control"
                           value="<?php echo isset($existing['B'][$qno]) ? htmlspecialchars($existing['B'][$qno]) : ''; ?>"
                           <?php echo $locked ? 'readonly' : ''; ?>>
                    <?php if (!$locked): ?>
                    <input type="file" name="B<?php echo $qno; ?>_files[]" multiple class="form-control mt-1">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" id="backA">← Back</button>
                <button type="button" class="btn btn-primary" id="toC" <?php echo $locked ? 'disabled' : ''; ?>>Next → PART C</button>
            </div>
        </div>

        <!-- PART C -->
        <div class="tab-pane" id="tab-C" style="display:none;">
            <?php foreach ($parts['C'] as $qno => $qtext): ?>
                <div class="mb-3">
                    <label class="form-label"><?php echo $qno . ". " . $qtext; ?></label>
                    <input type="number" name="C<?php echo $qno; ?>" class="form-control"
                           value="<?php echo isset($existing['C'][$qno]) ? htmlspecialchars($existing['C'][$qno]) : ''; ?>"
                           <?php echo $locked ? 'readonly' : ''; ?>>

                    <?php if (!$locked): ?>
                    <input type="file" name="C<?php echo $qno; ?>_files[]" multiple class="form-control mt-1">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <button type="button" class="btn btn-secondary" id="backB">← Back</button>
                </div>
                <div>
                    <?php if (!$locked): ?>
                        <button type="submit" name="submit_appraisal" class="btn btn-success">Submit</button>
                        <?php if ($appraisal_id && $status === 'Pending'): ?>
                            <?php
                                 // ✅ Dynamic label based on role
                                $btn_label = (isset($admin_role) && strtoupper($admin_role) === 'HOD')
                                    ? 'Approve & Forward to Principal'
                                    : 'Approve & Forward to HRM';
                            ?>
                            <button type="submit" name="approve_appraisal" class="btn btn-warning ms-2">
                                <?php echo $btn_label; ?>
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">Appraisal forwarded — editing disabled.</span>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </form>
</div>
</div>


      </div>

    </div>
  </div>
</div>

<script>
  // ===== Sidebar Toggle (you already have this above) =====
  const dashboardLink = document.getElementById('dashboardLink');
  const selfLink = document.getElementById('selfAppraisalsLink');
  const dashSection = document.getElementById('dashboardSection');
  const selfSection = document.getElementById('selfAppraisalSection');

  dashboardLink.addEventListener('click', () => {
    dashSection.style.display = 'block';
    selfSection.style.display = 'none';
    dashboardLink.classList.add('active');
    selfLink.classList.remove('active');
  });

  selfLink.addEventListener('click', () => {
    dashSection.style.display = 'none';
    selfSection.style.display = 'block';
    selfLink.classList.add('active');
    dashboardLink.classList.remove('active');
  });

  // ===== Self-Appraisal Tabs & Navigation =====
  document.addEventListener('DOMContentLoaded', function () {
    // Limit query to inside the self-appraisal section
    const tabButtons = document.querySelectorAll('#selfAppraisalSection .tab-btn');
    const panes = {
      A: document.querySelector('#selfAppraisalSection #tab-A'),
      B: document.querySelector('#selfAppraisalSection #tab-B'),
      C: document.querySelector('#selfAppraisalSection #tab-C')
    };

    tabButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        tabButtons.forEach(b => b.classList.remove('tab-active'));
        btn.classList.add('tab-active');
        const t = btn.getAttribute('data-tab');
        for (const k in panes) panes[k].style.display = 'none';
        panes[t].style.display = 'block';
        window.scrollTo(0, 0);
      });
    });

    // Next/Back navigation
    const toB = document.querySelector('#selfAppraisalSection #toB');
    const toC = document.querySelector('#selfAppraisalSection #toC');
    const backA = document.querySelector('#selfAppraisalSection #backA');
    const backB = document.querySelector('#selfAppraisalSection #backB');

    if (toB) toB.addEventListener('click', () => document.querySelector('#selfAppraisalSection .tab-btn[data-tab="B"]').click());
    if (toC) toC.addEventListener('click', () => document.querySelector('#selfAppraisalSection .tab-btn[data-tab="C"]').click());
    if (backA) backA.addEventListener('click', () => document.querySelector('#selfAppraisalSection .tab-btn[data-tab="A"]').click());
    if (backB) backB.addEventListener('click', () => document.querySelector('#selfAppraisalSection .tab-btn[data-tab="B"]').click());

    // Show Part A by default
    const defaultTab = document.querySelector('#selfAppraisalSection .tab-btn[data-tab="A"]');
    if (defaultTab) defaultTab.click();
  });

  if (window.location.search.includes('saved') || window.location.search.includes('submitted')) {
    dashSection.style.display = 'none';
    selfSection.style.display = 'block';
    selfLink.classList.add('active');
    dashboardLink.classList.remove('active');
  }
</script>

</body>
</html>
