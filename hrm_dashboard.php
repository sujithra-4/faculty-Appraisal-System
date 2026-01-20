<?php
session_start();
include 'config.php';

// Ensure HRM is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'hrm') {
    header("Location: login.php");
    exit();
}

$hrm_id = $_SESSION['user_id'];

// Get HRM department
$stmt = $conn->prepare("SELECT department FROM faculty_roles WHERE faculty_id = ? AND admin_role='HRM'");
$stmt->bind_param("i", $hrm_id);
$stmt->execute();
$stmt->bind_result($hrm_dept);
$stmt->fetch();
$stmt->close();

// Handle verify & forward
if (isset($_POST['verify_appraisal_id'])) {
    $verify_id = $_POST['verify_appraisal_id'];
    $update_stmt = $conn->prepare("UPDATE faculty_appraisal SET status='Verified' WHERE appraisal_id=?");
    $update_stmt->bind_param("i", $verify_id);
    $update_stmt->execute();
    $update_stmt->close();
    header("Location: hrm_dashboard.php");
    exit();
}

// Fetch all forwarded appraisals for this department
$appraisals = [];
$query = "
SELECT fa.appraisal_id, f.faculty_name, fr.role, fr.designation, fa.appraisal_year, fc.credits AS student_feedback
FROM faculty_appraisal fa
JOIN faculty f ON fa.faculty_id = f.faculty_id
JOIN faculty_roles fr ON f.faculty_id = fr.faculty_id
LEFT JOIN faculty_credits fc ON f.faculty_id = fc.faculty_id
WHERE fa.status = 'Forwarded'
  AND fa.department = ?
  AND fr.admin_role != 'HOD'
ORDER BY fa.submitted_on ASC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $hrm_dept);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $appraisals[] = $row;
}
$stmt->close();

// Appraisal Questions
$questionsA = [
    1 => "What is the average academic result of the subjects you handled?",
    2 => "How many student projects under your guidance have resulted in publications?",
    3 => "How many product development activities have been carried out under your guidance?",
    4 => "How many students participated in seminars, workshops, symposiums, etc.?",
    5 => "How many students won prizes in co-curricular or extracurricular activities?",
    6 => "How many students participated in project competitions under your guidance?",
    7 => "How many students won prizes in project competitions or MNC contests?",
    8 => "How many students completed online certification courses under your mentorship?",
    9 => "How many students completed internships or training programs?",
    10 => "How many students received special awards from institutes or industries?",
    11 => "How many students registered for competitive examinations?",
    12 => "How many students cleared competitive examinations?",
    13 => "How many students were placed under your mentorship?",
    14 => "Student Feedback",
    15 => "HOD Feedback"
];

$questionsB = [
    1 => "How many research papers have you published?",
    2 => "How many conference papers have you presented?",
    3 => "How many book chapters have you published?",
    4 => "How many patents have you filed?",
    5 => "How many patents have been granted to you?",
    6 => "How many copyrights have you registered?",
    7 => "How many research project proposals have you applied for?",
    8 => "How many projects have been sanctioned to you?",
    9 => "How many Ph.D. scholars are you currently guiding?",
    10 => "How many Ph.D./M.Phil scholars have completed under your guidance?"
];

$questionsC = [
    1 => "How many companies did you help in arranging for recruitment?",
    2 => "How many guest lectures have you delivered outside your institution?",
    3 => "How many guest lectures have you delivered within your institution?",
    4 => "How many online certification courses have you completed?",
    5 => "How many newsletters or magazines have you contributed to?",
    6 => "How many workshops or seminars have you participated in?",
    7 => "How many events have you organized?",
    8 => "How many awards or fellowships have you received?",
    9 => "How many professional memberships did you hold?",
    10 => "How many alumni networking activities have you organized?"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HRM Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #0077b6, #00b4d8);
    min-height: 100vh;
    color: #fff;
}

/* Top Header */
.navbar-custom {
    background: rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(6px);
    padding: 10px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.navbar-custom h3 {
    margin: 0;
    font-weight: 700;
    color: #fff;
}
.navbar-custom .user-info {
    font-weight: 500;
    font-size: 1rem;
}

/* Sidebar */
.sidebar {
    width: 220px;
    background: rgba(255,255,255,0.05);
    padding-top: 25px;
    display: flex;
    flex-direction: column;
    align-items: center;
    border-right: 1px solid rgba(255,255,255,0.2);
    position: fixed;
    top: 60px;
    bottom: 0;
    left: 0;
}
.sidebar h4 {
    color: #fff; /* Changed font color for HRM Panel heading */
    margin-bottom: 25px;
    font-weight: 700;
}
.sidebar button {
    width: 180px;
    margin: 8px 0;
    padding: 10px;
    border: none;
    border-radius: 10px;
    background: rgba(255,255,255,0.1);
    color: #fff;
    font-weight: 600;
    transition: all 0.3s;
}
.sidebar button:hover {
    background: linear-gradient(90deg, #00b4d8, #0077b6);
    transform: scale(1.05);
}

/* Main content */
.main-content {
    margin-left: 240px;
    margin-top: 80px;
    padding: 30px;
}

/* Cards */
.card {
    background: rgba(255,255,255,0.08);
    border: none;
    border-radius: 15px;
    transition: transform .3s, box-shadow .3s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.25);
}
.card-title {
    color: #fff; /* Faculty name color changed for visibility */
    font-size: 1.3rem;
    font-weight: 700;
}

/* Modal */
.modal-content {
    background: #fff;
    color: #004c91;
    border-radius: 12px;
    border: 2px solid #0077b6;
}
.modal-header {
    background: linear-gradient(90deg, #0077b6, #00b4d8);
    color: #fff;
    border: none;
}
.modal-title { color: #fff; font-weight: 700; }
.btn-close { filter: invert(1); }
.modal-body h6 { color: #0077b6; font-weight: 600; }
.btn-success {
    background: linear-gradient(90deg,#00b4d8,#0077b6);
    border: none;
}
.alert-info {
    background: rgba(255,255,255,0.1);
    border: none;
    color: #fff;
}
</style>
</head>
<body>

<!-- Top Header -->
<nav class="navbar navbar-custom fixed-top">
  <h3>FACULTY APPRAISAL SYSTEM</h3>
  <div class="d-flex align-items-center user-info">
      <span class="me-3"><?php echo htmlspecialchars($_SESSION['user_name']); ?> (<?php echo htmlspecialchars($hrm_dept); ?>)</span>
      <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
  </div>
</nav>

<!-- Sidebar -->
<div class="sidebar">
    <h4>HRM Panel</h4>
    <button onclick="showSection('dashboard')">Dashboard</button>
    <button onclick="showSection('appraisals')">Appraisal Approval</button>
</div>

<!-- Main Content -->
<div class="main-content">
    <div id="dashboard">
        <h2 class="text-center">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
        <p class="text-center fs-5">Department: <?php echo htmlspecialchars($hrm_dept); ?></p>
    </div>

    <div id="appraisals" style="display:none;">
        <h2 class="text-center mb-4">Appraisals To Verify</h2>
        <?php if(count($appraisals) === 0): ?>
            <div class="alert alert-info text-center">No appraisals to verify</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach($appraisals as $a): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column text-center">
                            <h5 class="card-title"><?php echo htmlspecialchars($a['faculty_name']); ?></h5>
                            <p class="card-text text-light mb-1"><?php echo htmlspecialchars($a['designation'])." (".htmlspecialchars($a['role']).")"; ?></p>
                            <p class="card-text mb-2 text-light">Year: <?php echo $a['appraisal_year']; ?></p>
                            <button class="btn btn-primary mt-auto" data-bs-toggle="modal" data-bs-target="#viewAppraisalModal<?php echo $a['appraisal_id']; ?>">View</button>
                        </div>
                    </div>
                </div>
                <!-- Modal -->
                <div class="modal fade" id="viewAppraisalModal<?php echo $a['appraisal_id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?php echo htmlspecialchars($a['faculty_name']); ?> - Appraisal</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <?php
                                $res_stmt = $conn->prepare("SELECT part, question_no, answer FROM faculty_appraisal_responses WHERE appraisal_id=? ORDER BY part, question_no");
                                $res_stmt->bind_param("i", $a['appraisal_id']);
                                $res_stmt->execute();
                                $res_result = $res_stmt->get_result();
                                $responses = [];
                                while ($r = $res_result->fetch_assoc()) {
                                    $responses[$r['part']][$r['question_no']] = $r['answer'];
                                }
                                $res_stmt->close();
                                ?>
                                <h6>PART A - Academic & Student Mentorship</h6>
                                <ol>
                                    <?php foreach($questionsA as $i => $q): ?>
                                    <li><?php echo htmlspecialchars($q); ?><br>
                                        <strong>
                                            <?php
                                            if($i==14){echo htmlspecialchars($a['student_feedback']);}
                                            elseif($i==15){echo "<em>Answered by HOD</em>";}
                                            else{echo htmlspecialchars($responses['A'][$i] ?? '-');}
                                            ?>
                                        </strong>
                                    </li>
                                    <?php endforeach; ?>
                                </ol>

                                <h6>PART B - Research & Development</h6>
                                <ol>
                                    <?php foreach($questionsB as $i => $q): ?>
                                    <li><?php echo htmlspecialchars($q); ?><br>
                                        <strong><?php echo htmlspecialchars($responses['B'][$i] ?? '-'); ?></strong>
                                    </li>
                                    <?php endforeach; ?>
                                </ol>

                                <h6>PART C - Faculty Contribution</h6>
                                <ol>
                                    <?php foreach($questionsC as $i => $q): ?>
                                    <li><?php echo htmlspecialchars($q); ?><br>
                                        <strong><?php echo htmlspecialchars($responses['C'][$i] ?? '-'); ?></strong>
                                    </li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                            <div class="modal-footer">
                                <form method="POST" class="m-0">
                                    <input type="hidden" name="verify_appraisal_id" value="<?php echo $a['appraisal_id']; ?>">
                                    <button type="submit" class="btn btn-success">Verify & Forward to HOD</button>
                                </form>
                                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showSection(section) {
    document.getElementById('dashboard').style.display = 'none';
    document.getElementById('appraisals').style.display = 'none';
    document.getElementById(section).style.display = 'block';
}
</script>
</body>
</html>