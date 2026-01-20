<?php
session_start();
include 'config.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['student_id'];
$faculty_id = $_GET['faculty_id'];
$subject_id = $_GET['subject_id'];

// If already approved, redirect back
$locked = false;
$q = $conn->prepare("SELECT locked FROM feedback WHERE student_id = ? LIMIT 1");
$q->bind_param("i", $student_id);
$q->execute();
$q->bind_result($locked_status);
if ($q->fetch() && $locked_status == 1) $locked = true;
$q->close();
if ($locked) {
    header('Location: dashboard.php');
    exit();
}

// Fetch faculty and subject names
$stmt = $conn->prepare("SELECT f.faculty_name, s.subject_name FROM faculty f, subjects s WHERE f.faculty_id = ? AND s.subject_id = ?");
$stmt->bind_param("ii", $faculty_id, $subject_id);
$stmt->execute();
$stmt->bind_result($faculty_name, $subject_name);
$stmt->fetch();
$stmt->close();

// Fetch feedback questions
$questions = [];
$q = $conn->query("SELECT question_id, question_text FROM feedback_questions");
while ($row = $q->fetch_assoc()) {
    $questions[] = $row;
}

// Handle feedback entry (store in session only)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($questions as $q) {
        $qid = $q['question_id'];
        $rating = isset($_POST["q_$qid"]) ? intval($_POST["q_$qid"]) : 0;
        $_SESSION['pending_feedback'][$faculty_id . '_' . $subject_id][$qid] = $rating;
    }
    header('Location: dashboard.php');
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Enter Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f2f4f8; }
        .card { box-shadow: 0 4px 16px #00000014; border: none; }
        .rating-group { display: flex; gap: 1rem; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <h3 class="mb-2">Feedback Form</h3>
                <div class="mb-3">
                    <strong>Faculty:</strong> <?php echo htmlspecialchars($faculty_name); ?><br>
                    <strong>Subject:</strong> <?php echo htmlspecialchars($subject_name); ?>
                </div>
                <form method="post">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Question</th>
                                <th>Rating (1=Poor, 5=Excellent)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions as $index => $q) { ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($q['question_text']); ?></td>
                                <td>
                                    <div class="rating-group">
                                        <?php for ($r = 1; $r <= 5; $r++) { ?>
                                            <label>
                                                <input type="radio" name="q_<?php echo $q['question_id']; ?>" value="<?php echo $r; ?>" required>
                                                <?php echo $r; ?>
                                            </label>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <button type="submit" class="btn btn-success w-100">Save Feedback</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>