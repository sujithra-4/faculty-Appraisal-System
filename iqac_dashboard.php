<?php
session_start();
include 'config.php';

// Ensure IQAC is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'iqac') {
    header("Location: login.php");
    exit();
}

// Handle Verify button
if (isset($_POST['verify_appraisal_id'])) {
    $verify_id = $_POST['verify_appraisal_id'];
    $stmt = $conn->prepare("UPDATE faculty_appraisal SET status='finalized' WHERE appraisal_id=?");
    $stmt->bind_param("i", $verify_id);
    $stmt->execute();
    $stmt->close();
    header("Location: iqac_dashboard.php");
    exit();
}

// Fetch all appraisals ready for IQAC verification
$appraisals = [];
$query = "
SELECT fa.appraisal_id, fa.faculty_id, f.faculty_name, fr.role, fr.designation, fr.department, fa.appraisal_year, 
       fa.responses_score, fa.final_score, fa.hod_or_principal_feedback, fc.credits AS student_feedback
FROM faculty_appraisal fa
JOIN faculty f ON fa.faculty_id = f.faculty_id
JOIN faculty_roles fr ON f.faculty_id = fr.faculty_id
LEFT JOIN faculty_credits fc ON f.faculty_id = fc.faculty_id
WHERE fa.hod_or_principal_feedback IS NOT NULL AND fa.status != 'finalized'
ORDER BY fa.submitted_on ASC
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $appraisals[] = $row;
}

// Hardcoded questions
$questionsA = [
    1 => "What is the average academic result (excluding labs and projects)?",
    2 => "How many student projects under your guidance resulted in publications?",
    3 => "How many product development activities were carried out by your students?",
    4 => "How many students participated in seminars/workshops/symposiums?",
    5 => "How many students won prizes in co-curricular or extracurricular activities?",
    6 => "How many students participated in project competitions?",
    7 => "How many students won prizes in project competitions?",
    8 => "How many students completed online certification courses (NPTEL, etc.)?",
    9 => "How many students completed internships or in-plant training programs?",
    10 => "How many students received special awards from institutes or industries?",
    11 => "How many students registered for competitive examinations?",
    12 => "How many students cleared competitive examinations?",
    13 => "How many students were placed under your mentorship (mention packages)?",
    14 => "Student Feedback",
    15 => "HOD Feedback"
];
$questionsB = [
    1=>"How many research papers published in Scopus/SCI/UGC indexed journals?",
    2=>"How many conference papers have you presented or published?",
    3=>"How many book chapters have you published?",
    4=>"How many patents have you filed?",
    5=>"How many patents have been granted?",
    6=>"How many copyrights have you registered?",
    7=>"How many research project proposals have you applied for?",
    8=>"How many funded research projects have been sanctioned?",
    9=>"How many Ph.D. scholars are you currently guiding?",
    10=>"How many Ph.D./M.Phil scholars have completed under your guidance?"
];
$questionsC = [
    1=>"How many companies did you help in arranging for campus recruitment?",
    2=>"How many guest lectures have you delivered outside your institution?",
    3=>"How many guest lectures have you delivered within your institution?",
    4=>"How many online certification courses have you completed (min 4 weeks)?",
    5=>"How many newsletters or magazines have you contributed to?",
    6=>"How many workshops or seminars have you participated in?",
    7=>"How many events (seminars, FDPs, etc.) have you organized?",
    8=>"How many awards/fellowships have you received?",
    9=>"How many professional body memberships do you hold?",
    10=>"How many alumni networking activities have you organized?"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>IQAC Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #003566, #00b4d8);
    min-height: 100vh;
    color: #fff;
    background-attachment: fixed; /* <-- Add this line */
    background-size: cover;
}
.navbar-custom {
    background: rgba(0, 0, 0, 0.25);
    backdrop-filter: blur(6px);
    padding: 10px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.navbar-custom h3 { color: #fff; font-weight: 700; }
.sidebar {
    width: 220px;
    background: rgba(255,255,255,0.05);
    padding-top: 25px;
    position: fixed;
    top: 60px;
    bottom: 0;
    left: 0;
    text-align: center;
    border-right: 1px solid rgba(255,255,255,0.2);
}
.sidebar h4 { color: #f8f9fa; margin-bottom: 25px; font-weight: 700; }
.sidebar button {
    width: 180px;
    margin: 8px 0;
    padding: 10px;
    border: none;
    border-radius: 10px;
    background: rgba(255,255,255,0.15);
    color: #fff;
    font-weight: 600;
    transition: all 0.3s;
}
.sidebar button:hover {
    background: linear-gradient(90deg, #00b4d8, #0077b6);
    transform: scale(1.05);
}
.main-content {
    margin-left: 240px;
    margin-top: 80px;
    padding: 30px;
}
.card {
    background: rgba(255,255,255,0.15);
    border: none;
    border-radius: 15px;
    color: #fff;
    transition: transform .3s, box-shadow .3s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.25);
}
.card-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #fff;
}
.card-text {
    color: #f8f9fa;
    font-weight: 500;
}
.modal-content {
    background: #fff;
    color: #003566;
    border-radius: 12px;
    border: 2px solid #0077b6;
}
.modal-header {
    background: linear-gradient(90deg, #0077b6, #00b4d8);
    color: #fff;
}
.modal-footer .btn-secondary {
    background-color: #6c757d;
    border: none;
}
.modal-footer .btn-success {
    background: linear-gradient(90deg,#00b4d8,#0077b6);
    border: none;
}
</style>
</head>
<body>

<nav class="navbar navbar-custom fixed-top">
  <h3>FACULTY APPRAISAL SYSTEM</h3>
  <div class="d-flex align-items-center user-info">
      <span class="me-3"><?php echo htmlspecialchars($_SESSION['user_name']); ?> (IQAC)</span>
      <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
  </div>
</nav>

<div class="sidebar">
    <h4>IQAC Panel</h4>
    <button onclick="showSection('dashboard')">Dashboard</button>
    <button onclick="showSection('approvals')">Appraisal Verification</button>
</div>

<div class="main-content">
    <div id="dashboard">
        <h2 class="text-center">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
        <p class="text-center fs-5">IQAC Department</p>
    </div>

    <div id="approvals" style="display:none;">
        <h2 class="text-center mb-4">Appraisals to Verify</h2>
        <?php if (count($appraisals) === 0): ?>
            <div class="alert alert-info text-center">No appraisals pending verification.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($appraisals as $a): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm text-center">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($a['faculty_name']); ?></h5>
                            <p class="card-text mb-1"><?php echo htmlspecialchars($a['designation'])." (".htmlspecialchars($a['role']).")"; ?></p>
                            <p class="card-text mb-2">Year: <?php echo $a['appraisal_year']; ?></p>
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
                                        if ($i==14) echo htmlspecialchars($a['student_feedback']);
                                        elseif ($i==15) echo htmlspecialchars($a['hod_or_principal_feedback']);
                                        else echo htmlspecialchars($responses['A'][$i] ?? '-');
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

                                <div class="text-center mt-4">
                                    <h4>Final Score: <?php echo $a['final_score']; ?>/100</h4>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <form method="POST">
                                    <input type="hidden" name="verify_appraisal_id" value="<?php echo $a['appraisal_id']; ?>">
                                    <button type="submit" class="btn btn-success">Verify</button>
                                </form>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    document.getElementById('approvals').style.display = 'none';
    document.getElementById(section).style.display = 'block';
}
</script>
</body>
</html>