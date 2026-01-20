<?php
session_start();
include 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'hr') {
    header("Location: login.php");
    exit();
}

$hr_name = $_SESSION['username'] ?? "HR";

// Fetch finalized appraisals
$query = "
SELECT f.faculty_id, f.faculty_name, fr.department, fr.designation, fa.final_score
FROM faculty f
JOIN faculty_roles fr ON f.faculty_id = fr.faculty_id
JOIN faculty_appraisal fa ON f.faculty_id = fa.faculty_id
WHERE fa.status = 'finalized'
ORDER BY fr.department, f.faculty_name;
";
$result = $conn->query($query);
$faculties = [];
$departments = [];

while ($row = $result->fetch_assoc()) {
    $faculties[] = $row;
    if (!in_array($row['department'], $departments)) {
        $departments[] = $row['department'];
    }
}

$thresholds = [
    'Professor' => ['increment' => 70, 'incentive' => 75],
    'Associate Professor' => ['increment' => 65, 'incentive' => 70],
    'Assistant Professor' => ['increment' => 55, 'incentive' => 60],
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>HR Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="hr_dashboard.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js"></script>
</head>
<body>
    <!-- Top Navbar -->
    <div class="navbar-custom">
        <h3>FACULTY APPRAISAL SYSTEM</h3>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>HR Panel</h2>
        <div class="sidebar-links">
            <a href="#" class="active" id="dashboardLink"><i class="fas fa-home"></i> Dashboard</a>
            <a href="#" id="eligiblesLink"><i class="fas fa-list"></i> Eligibles</a>
            <a href="#" id="chartsToggle"><i class="fas fa-chart-pie"></i> Charts <i class="fas fa-caret-down float-end"></i></a>
            <div id="chartsSubmenu" class="submenu">
                <a href="#" id="catChartLink">Categorical Based</a>
                <a href="#" id="deptChartLink">Department Based</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Dashboard Section -->
        <div id="dashboardSection" class="content-section">
            <div class="container text-center py-5">
                <h4>Welcome, <?= htmlspecialchars($hr_name) ?></h4>
            </div>
        </div>

        <!-- Eligibles Section -->
        <div id="eligiblesSection" class="content-section" style="display:none;">
            <div class="container">
                <h5 class="text-center mb-4">Eligibility List</h5>

                <!-- Department Filter -->
                <div class="filter-container my-3 text-center">
                    <label class="fw-bold me-2">Filter by Department:</label>
                    <select id="deptFilter" class="form-select d-inline-block" style="width:auto;">
                        <option value="All">All Departments</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tables Container -->
                <div id="eligibilityTables" class="d-flex flex-wrap justify-content-between gap-4">
                    <!-- Increment Eligible Table -->
                    <div class="flex-fill" style="min-width: 48%;">
                        <h5 class="text-center text-success fw-bold">INCREMENT ELIGIBLE</h5>
                        <table class="table table-bordered table-striped align-middle text-center" id="incrementTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Designation</th>
                                    <th>Department</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($faculties as $f): 
                                    $desig = $f['designation'];
                                    $dept = htmlspecialchars($f['department']);
                                    if (isset($thresholds[$desig]) && $f['final_score'] >= $thresholds[$desig]['increment']): ?>
                                        <tr data-dept="<?= $dept ?>">
                                            <td><?= htmlspecialchars($f['faculty_name']) ?></td>
                                            <td><?= htmlspecialchars($f['designation']) ?></td>
                                            <td><?= $dept ?></td>
                                        </tr>
                                <?php endif; endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Incentive Eligible Table -->
                    <div class="flex-fill" style="min-width: 48%;">
                        <h5 class="text-center text-primary fw-bold">INCENTIVE ELIGIBLE</h5>
                        <table class="table table-bordered table-striped align-middle text-center" id="incentiveTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Designation</th>
                                    <th>Department</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($faculties as $f): 
                                    $desig = $f['designation'];
                                    $dept = htmlspecialchars($f['department']);
                                    if (isset($thresholds[$desig]) && $f['final_score'] >= $thresholds[$desig]['incentive']): ?>
                                        <tr data-dept="<?= $dept ?>">
                                            <td><?= htmlspecialchars($f['faculty_name']) ?></td>
                                            <td><?= htmlspecialchars($f['designation']) ?></td>
                                            <td><?= $dept ?></td>
                                        </tr>
                                <?php endif; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Verify and Download Buttons -->
                <div class="text-center mt-4">
                    <button id="verifyBtn" class="btn btn-warning px-4 fw-bold">VERIFY</button>
                    <button id="downloadBtn" class="btn btn-success px-4 fw-bold" disabled>DOWNLOAD PDF</button>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div id="chartsSection" class="content-section" style="display:none;">
            <div class="container text-center">
                <iframe id="chartsFrame" src="" width="100%" height="600" style="border:none;"></iframe>
            </div>
        </div>
    </div>

<script>
// Sidebar Charts Toggle
const chartsToggle = document.getElementById('chartsToggle');
const chartsSubmenu = document.getElementById('chartsSubmenu');
chartsSubmenu.style.display = 'none';

chartsToggle.addEventListener('click', (e) => {
    e.preventDefault();
    chartsSubmenu.style.display = (chartsSubmenu.style.display === 'none') ? 'flex' : 'none';
});

// Section Control
const dashboardLink = document.getElementById('dashboardLink');
const eligiblesLink = document.getElementById('eligiblesLink');
const dashboardSection = document.getElementById('dashboardSection');
const eligiblesSection = document.getElementById('eligiblesSection');
const chartsSection = document.getElementById('chartsSection');
const chartsFrame = document.getElementById('chartsFrame');

dashboardLink.addEventListener('click', () => {
    dashboardSection.style.display = 'block';
    eligiblesSection.style.display = 'none';
    chartsSection.style.display = 'none';
    dashboardLink.classList.add('active');
    eligiblesLink.classList.remove('active');
});

eligiblesLink.addEventListener('click', () => {
    dashboardSection.style.display = 'none';
    eligiblesSection.style.display = 'block';
    chartsSection.style.display = 'none';
    eligiblesLink.classList.add('active');
    dashboardLink.classList.remove('active');
});

// Charts submenu
document.getElementById('catChartLink').addEventListener('click', () => {
    dashboardSection.style.display = 'none';
    eligiblesSection.style.display = 'none';
    chartsSection.style.display = 'block';
    chartsFrame.src = 'charts_categorical.php';
});

document.getElementById('deptChartLink').addEventListener('click', () => {
    dashboardSection.style.display = 'none';
    eligiblesSection.style.display = 'none';
    chartsSection.style.display = 'block';
    chartsFrame.src = 'charts_department.php';
});

// Department filter
const deptFilter = document.getElementById("deptFilter");
deptFilter.addEventListener("change", function() {
    const selected = this.value;
    const incrementRows = document.querySelectorAll("#incrementTable tbody tr");
    const incentiveRows = document.querySelectorAll("#incentiveTable tbody tr");

    [incrementRows, incentiveRows].forEach(rows => {
        rows.forEach(r => {
            r.style.display = (selected === "All" || r.dataset.dept === selected) ? "" : "none";
        });
    });

    // Disable download when changing department until verified again
    document.getElementById("downloadBtn").disabled = true;
});

// Verify + Download logic
const verifyBtn = document.getElementById("verifyBtn");
const downloadBtn = document.getElementById("downloadBtn");

verifyBtn.addEventListener("click", () => {
    downloadBtn.disabled = false;
    alert("Eligibility list verified. You can now download the PDF for this department.");
});

// PDF generation
downloadBtn.addEventListener("click", () => {
    const dept = deptFilter.value;
    const element = document.getElementById("eligibilityTables");
    const fileName = (dept === "All")
        ? "Eligibility_List_All_Departments.pdf"
        : "Eligibility_List_" + dept.replace(/\s+/g, '_') + ".pdf";

    html2pdf().from(element).set({
        margin: 0.4,
        filename: fileName,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
    }).save();
});
</script>
</body>
</html>
