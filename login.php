<?php
session_start();
include 'config.php';

$error = "";
$role = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $role_tables = [
        "faculty"   => ["table"=>"faculty_roles", "id"=>"faculty_id", "name"=>"faculty_name", "login_field"=>"username", "dashboard"=>"faculty_dashboard.php"],
        "hod"       => ["table"=>"faculty_roles", "id"=>"faculty_id", "name"=>"faculty_name", "login_field"=>"username", "dashboard"=>"hod_dashboard.php"],
        "principal" => ["table"=>"principal", "id"=>"principal_id", "name"=>"principal_name", "login_field"=>"username", "dashboard"=>"principal_dashboard.php"],
        "hrm"       => ["table"=>"faculty_roles", "id"=>"faculty_id", "name"=>"faculty_name", "login_field"=>"username", "dashboard"=>"hrm_dashboard.php"],
        "iqac"      => ["table"=>"iqac_user", "id"=>"iqac_id", "name"=>"username", "login_field"=>"username", "dashboard"=>"iqac_dashboard.php"],
        "hr"        => ["table"=>"hr_user", "id"=>"hr_id", "name"=>"username", "login_field"=>"username", "dashboard"=>"hr_dashboard.php"]
    ];

    if (!array_key_exists($role, $role_tables)) {
        $error = "Invalid role selected!";
    } else {
        $dashboard = $role_tables[$role]['dashboard'];

        if ($role === 'faculty' || $role === 'hrm' || $role === 'hod') {
            $roleCheck = strtoupper($role);
            $query = ($role === 'faculty') ? 
                "SELECT f.faculty_id, f.faculty_name, fr.password 
                 FROM faculty f JOIN faculty_roles fr ON f.faculty_id = fr.faculty_id 
                 WHERE fr.username = ?" :
                "SELECT f.faculty_id, f.faculty_name, fr.password 
                 FROM faculty f JOIN faculty_roles fr ON f.faculty_id = fr.faculty_id 
                 WHERE fr.username = ? AND fr.admin_role='$roleCheck'";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->bind_result($fid, $fname, $fpass);

            if ($stmt->fetch() && $password === $fpass) {
                $_SESSION['role'] = $role;
                $_SESSION['user_id'] = $fid;
                $_SESSION['user_name'] = $fname;
                header("Location: $dashboard");
                exit();
            } else $error = "Invalid username or password!";
            $stmt->close();

        } elseif ($role === 'principal') {
            $stmt = $conn->prepare("SELECT principal_id, principal_name, password FROM principal WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->bind_result($pid, $pname, $ppass);

            if ($stmt->fetch() && $password === $ppass) {
                $_SESSION['role'] = 'principal';
                $_SESSION['user_id'] = $pid;
                $_SESSION['user_name'] = $pname;
                header("Location: principal_dashboard.php");
                exit();
            } else $error = "Invalid username or password!";
            $stmt->close();

        } elseif ($role === 'iqac') {
            $stmt = $conn->prepare("SELECT iqac_id, username, password FROM iqac_user WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->bind_result($iqac_id, $iqac_name, $iqac_pass);

            if ($stmt->fetch() && $password === $iqac_pass) {
                $_SESSION['role'] = 'iqac';
                $_SESSION['user_id'] = $iqac_id;
                $_SESSION['user_name'] = $iqac_name;
                header("Location: iqac_dashboard.php");
                exit();
            } else $error = "Invalid username or password!";
            $stmt->close();
        } else {
            $table = $role_tables[$role]['table'];
            $id_col = $role_tables[$role]['id'];
            $name_col = $role_tables[$role]['name'];
            $login_field = $role_tables[$role]['login_field'];

            $stmt = $conn->prepare("SELECT $id_col, $name_col, password FROM $table WHERE $login_field = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->bind_result($id, $name, $db_pass);

            if ($stmt->fetch() && $password === $db_pass) {
                $_SESSION['role'] = $role;
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $name;
                header("Location: $dashboard");
                exit();
            } else $error = "Invalid username or password!";
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Role-Based Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0077b6, #48cae4);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        header {
            width: 100%;
            text-align: center;
            padding: 1rem 0;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.2);
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }

        .sidebar {
            width: 240px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 2rem;
            border-right: 1px solid rgba(255,255,255,0.2);
            animation: slideInLeft 1.2s ease;
            margin-top: 70px; /* space for header */
        }

        .sidebar h4 {
            color: white;
            margin-bottom: 2rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        @keyframes slideInLeft {
            from { transform: translateX(-100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .role-item {
            width: 180px;
            color: white;
            padding: 0.8rem 1rem;
            margin: 0.5rem 0;
            text-align: center;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            background: rgba(255,255,255,0.15);
            transition: all 0.3s ease;
        }

        .role-item:hover {
            background: linear-gradient(90deg, #00b4d8, #0077b6);
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(0,180,216,0.6);
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            animation: fadeIn 1.5s ease;
            margin-top: 70px; /* space for header */
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .login-card {
            width: 400px;
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            display: none;
            animation: slideUp 1s ease forwards;
        }

        @keyframes slideUp {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .login-card h3 {
            background: linear-gradient(90deg, #0077b6, #00b4d8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid #ccc;
            transition: 0.3s;
        }

        .form-control:focus {
            box-shadow: 0 0 10px rgba(0,180,216,0.5);
            border-color: #00b4d8;
        }

        .btn-login {
            background: linear-gradient(90deg, #00b4d8, #0077b6);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 10px;
            padding: 10px;
            transition: 0.3s;
        }

        .btn-login:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(0,180,216,0.6);
        }
    </style>
</head>
<body>

    <header>Faculty Appraisal System</header>

    <div class="sidebar">
        <h4>Login Roles</h4>
        <div class="role-item" onclick="showLogin('faculty')"><i class="bi bi-person-badge-fill"></i> Faculty</div>
        <div class="role-item" onclick="showLogin('hod')"><i class="bi bi-person-lines-fill"></i> HOD</div>
        <div class="role-item" onclick="showLogin('hrm')"><i class="bi bi-people-fill"></i> HRM</div>
        <div class="role-item" onclick="showLogin('principal')"><i class="bi bi-person-bounding-box"></i> Principal</div>
        <div class="role-item" onclick="showLogin('iqac')"><i class="bi bi-building"></i> IQAC</div>
        <div class="role-item" onclick="showLogin('hr')"><i class="bi bi-person-gear"></i> HR</div>
    </div>

    <div class="main-content">
        <div class="login-card" id="loginForm">
            <h3 id="loginTitle">Login</h3>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="role" id="roleInput" value="">
                <div class="mb-3 text-start">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-login w-100 mt-3">Login</button>
            </form>
        </div>
    </div>

<script>
function showLogin(role) {
    const card = document.getElementById('loginForm');
    const title = document.getElementById('loginTitle');
    const roleInput = document.getElementById('roleInput');
    title.innerText = role.charAt(0).toUpperCase() + role.slice(1) + ' Login';
    roleInput.value = role;
    card.style.display = 'block';
    card.style.animation = 'slideUp 1s ease forwards';
}
</script>

</body>
</html>