<?php
include 'config.php';

/*
  Usage:
    fetch_chart_data.php?mode=categorical&type=increment
    fetch_chart_data.php?mode=department&type=incentive
    Optional for department:
      &view=share      -> returns each department's share of total eligible (eligible_in_dept / total_eligible * 100)
      (default view for department = "within" -> eligible_in_dept / total_in_dept * 100)
    Note: for categorical we default to distribution among eligible (share).
*/

$mode = $_GET['mode'] ?? '';    // 'categorical' or 'department'
$type = $_GET['type'] ?? '';    // 'increment' or 'incentive'
$view = $_GET['view'] ?? '';    // optional: 'share' for department

// Thresholds (same as HR dashboard)
$thresholds = [
    'Professor'           => ['increment' => 70, 'incentive' => 75],
    'Associate Professor' => ['increment' => 65, 'incentive' => 70],
    'Assistant Professor' => ['increment' => 55, 'incentive' => 60],
];

// Fetch all finalized appraisal rows with role info
$q = "
SELECT fr.department, fr.designation, fa.final_score
FROM faculty_appraisal fa
JOIN faculty_roles fr ON fa.faculty_id = fr.faculty_id
WHERE fa.status = 'finalized';
";
$res = $conn->query($q);

// We'll collect totals and eligible counts
$total_per_dept = [];
$total_per_desig = [];
$eligible_per_dept = [];
$eligible_per_desig = [];
$total_eligible_overall = 0;

while ($row = $res->fetch_assoc()) {
    $dept = $row['department'];
    $desig = $row['designation'];
    $score = (float)$row['final_score'];

    // init counters
    if (!isset($total_per_dept[$dept])) $total_per_dept[$dept] = 0;
    if (!isset($eligible_per_dept[$dept])) $eligible_per_dept[$dept] = 0;
    if (!isset($total_per_desig[$desig])) $total_per_desig[$desig] = 0;
    if (!isset($eligible_per_desig[$desig])) $eligible_per_desig[$desig] = 0;

    $total_per_dept[$dept]++;
    $total_per_desig[$desig]++;

    // check eligibility
    if (isset($thresholds[$desig]) && isset($thresholds[$desig][$type])) {
        $th = (float)$thresholds[$desig][$type];
        if ($score >= $th) {
            $eligible_per_dept[$dept]++;
            $eligible_per_desig[$desig]++;
            $total_eligible_overall++;
        }
    }
}

// Prepare output
$data = [];

if ($mode === 'categorical') {
    // For a categorical PIE we want distribution among eligible (share of eligible)
    // If no eligible at all, we still output 0s for each category so chart can render.
    $categories = ['Professor', 'Associate Professor', 'Assistant Professor'];
    foreach ($categories as $cat) {
        $eligible = $eligible_per_desig[$cat] ?? 0;
        $percent = ($total_eligible_overall > 0) ? round(($eligible / $total_eligible_overall) * 100, 2) : 0;
        $data[] = ['label' => $cat, 'value' => $percent, 'raw' => $eligible];
    }
}
elseif ($mode === 'department') {
    // Two possible interpretations: within-department % (eligible/total_in_dept) OR share among eligible (eligible/total_eligible)
    // Default: within-department percent (this matches your earlier spec).
    // Optional: pass view=share to get each dept's share of overall eligible.
    // We will return departments in alphabetical order (stable).
    $deptQuery = "SELECT DISTINCT department FROM faculty_roles ORDER BY department";
    $deptResult = $conn->query($deptQuery);
    $total_eligible = $total_eligible_overall; // alias

    while ($d = $deptResult->fetch_assoc()) {
        $dept = $d['department'];
        $total = $total_per_dept[$dept] ?? 0;
        $eligible = $eligible_per_dept[$dept] ?? 0;

        if ($view === 'share') {
            // share of overall eligible (useful if you want bars proportional to counts of eligible across departments)
            $percent = ($total_eligible > 0) ? round(($eligible / $total_eligible) * 100, 2) : 0;
        } else {
            // within-department percentage: (eligible / total_in_dept) * 100
            $percent = ($total > 0) ? round(($eligible / $total) * 100, 2) : 0;
        }

        $data[] = ['label' => $dept, 'value' => $percent, 'raw_total' => $total, 'raw_eligible' => $eligible];
    }
} else {
    // invalid mode - return empty array
    $data = [];
}

header('Content-Type: application/json');
echo json_encode($data);
exit();
