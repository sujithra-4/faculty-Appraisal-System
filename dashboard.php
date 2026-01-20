<?php
session_start();
include 'config.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}
$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

$stmt = $conn->prepare("SELECT department, year, section FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($department, $year, $section);
$stmt->fetch();
$stmt->close();

$query = "
SELECT f.faculty_id, f.faculty_name, s.subject_id, s.subject_name
FROM faculty_subject_mapping m
JOIN faculty f ON m.faculty_id = f.faculty_id
JOIN subjects s ON m.subject_id = s.subject_id
WHERE m.department = ? AND m.year = ? AND m.section = ?
ORDER BY f.faculty_name, s.subject_name
";
$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $department, $year, $section);
$stmt->execute();
$result = $stmt->get_result();
$faculty_feedbacks = [];
while ($row = $result->fetch_assoc()) {
    $faculty_feedbacks[] = $row;
}
$stmt->close();

$locked = false;
$q = $conn->prepare("SELECT locked FROM feedback WHERE student_id = ? LIMIT 1");
$q->bind_param("i", $student_id);
$q->execute();
$q->bind_result($locked_status);
if ($q->fetch() && $locked_status == 1) $locked = true;
$q->close();

$entered_count = 0;
$total_count = count($faculty_feedbacks);
$entered_keys = isset($_SESSION['pending_feedback']) ? array_keys($_SESSION['pending_feedback']) : [];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard - Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f6f9ff, #eef3ff);
            font-family: 'Poppins', sans-serif;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* SIDEBAR */
        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, #1a1a2e, #16213e);
            color: white;
            display: flex;
            flex-direction: column;
            padding-top: 2rem;
            box-shadow: 3px 0 15px rgba(0,0,0,0.4);
            animation: slideIn 1s ease;
        }

        @keyframes slideIn {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .sidebar h3 {
            text-align: center;
            font-weight: 600;
            margin-bottom: 2rem;
            letter-spacing: 1px;
            color: #fff;
            text-shadow: 0 0 10px #00b4d8;
            animation: glowText 2s infinite alternate;
        }

        @keyframes glowText {
            from { text-shadow: 0 0 5px #00b4d8, 0 0 10px #00b4d8; }
            to { text-shadow: 0 0 20px #48cae4, 0 0 30px #48cae4; }
        }

        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 15px 25px;
            border-radius: 8px;
            margin: 8px 15px;
            font-weight: 500;
            background: linear-gradient(90deg, #0077b6, #00b4d8);
            text-align: center;
            transition: all 0.4s ease;
        }

        .sidebar a:hover {
            background: linear-gradient(90deg, #00b4d8, #90e0ef);
            transform: scale(1.05);
            box-shadow: 0 0 20px #00b4d8;
        }

        .sidebar a.active {
            background: linear-gradient(90deg, #00b4d8, #0077b6);
            box-shadow: 0 0 15px #00b4d8;
        }

        /* MAIN */
        .main-content {
            flex-grow: 1;
            overflow-y: auto;
            background: #f9fafc;
            animation: fadeUp 1s ease;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            background: linear-gradient(90deg, #00b4d8, #0077b6);
            color: white;
            padding: 1.5rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0, 119, 182, 0.3);
            border-radius: 0 0 20px 20px;
            background-size: 200% 100%;
            animation: shimmer 3s infinite linear;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .header h2 {
            margin: 0;
            font-weight: 600;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.4);
            transform: scale(1.05);
        }

        /* CARD & TABLE */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.1);
            animation: fadeIn 1.2s ease;
            background: white;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .table th {
            background: linear-gradient(90deg, #0077b6, #00b4d8);
            color: white;
            font-weight: 600;
            border: none;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: #eaf4ff;
            transform: scale(1.01);
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        .btn-primary {
            background: linear-gradient(90deg, #0077b6, #00b4d8);
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            background: linear-gradient(90deg, #00b4d8, #48cae4);
            box-shadow: 0 0 15px #00b4d8;
        }

        .badge.bg-success { animation: pulseGreen 1.5s infinite; }
        .badge.bg-danger { animation: pulseRed 1.5s infinite; }

        @keyframes pulseGreen {
            0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.5); }
            70% { box-shadow: 0 0 0 10px rgba(76, 175, 80, 0); }
            100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
        }
        @keyframes pulseRed {
            0% { box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.5); }
            70% { box-shadow: 0 0 0 10px rgba(244, 67, 54, 0); }
            100% { box-shadow: 0 0 0 0 rgba(244, 67, 54, 0); }
        }
    </style>
</head>

<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <h3>Student Panel</h3>
        <a href="#" class="active">💬 Feedback</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="header">
            <h2>Welcome, <?php echo htmlspecialchars($student_name); ?></h2>
            <form action="logout.php" method="post">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>

        <div class="container py-4">
            <div class="card p-4">
                <h4 class="mb-3 text-primary fw-bold">Faculty Feedback List</h4>
                <table class="table table-bordered align-middle text-center">
                    <thead>
                        <tr>
                            <th>Faculty Name</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($faculty_feedbacks as $row):
                        $key = $row['faculty_id'].'_'.$row['subject_id'];
                        $is_entered = in_array($key, $entered_keys);
                        if ($is_entered) $entered_count++;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td>
                                <?php
                                if ($locked) echo "<span class='badge bg-secondary'>Approved</span>";
                                elseif ($is_entered) echo "<span class='badge bg-success'>Entered</span>";
                                else echo "<span class='badge bg-danger'>Pending</span>";
                                ?>
                            </td>
                            <td>
                                <?php
                                if (!$locked && !$is_entered)
                                    echo "<a href='feedback_form.php?faculty_id={$row['faculty_id']}&subject_id={$row['subject_id']}' class='btn btn-sm btn-primary'>Enter</a>";
                                elseif (!$locked && $is_entered)
                                    echo "<span class='text-success fw-semibold'>Entered</span>";
                                else
                                    echo "<span class='text-muted'>--</span>";
                                ?>
                            </td>
                        </tr>
                    <?php endforeach;
                    if ($total_count == 0)
                        echo "<tr><td colspan='4' class='text-muted'>No faculties assigned for feedback.</td></tr>";
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>