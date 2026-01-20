<?php
session_start();
include 'config.php';

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $roll_no = $_POST['roll_no'];
    $name = $_POST['name'];
    $department = $_POST['department'];
    $year = $_POST['year'];
    $section = $_POST['section'];
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO students (roll_no, name, department, year, section, password) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiss", $roll_no, $name, $department, $year, $section, $hash);

    if ($stmt->execute()) {
        $success = "Registration successful! You can now <a href='login.php'>login</a>.";
    } else {
        $error = "Registration failed! Roll number may already exist.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f2f4f8; }
        .card { box-shadow: 0 4px 16px #00000014; border: none; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4">
                <h2 class="mb-4 text-center">Student Registration</h2>
                <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>
                <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <form method="post" action="">
                    <input type="text" name="roll_no" placeholder="Roll Number" class="form-control mb-2" required>
                    <input type="text" name="name" placeholder="Name" class="form-control mb-2" required>
                    <input type="text" name="department" placeholder="Department" class="form-control mb-2" required>
                    <input type="number" name="year" placeholder="Year" class="form-control mb-2" required>
                    <input type="text" name="section" placeholder="Section" class="form-control mb-2" required>
                    <input type="password" name="password" placeholder="Password" class="form-control mb-3" required>
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
                <div class="mt-3 text-center"><a href="login.php">Already registered? Login</a></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>