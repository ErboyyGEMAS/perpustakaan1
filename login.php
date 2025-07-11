<?php
session_start();
include 'includes/db_connect.php'; // Tetap butuh ini untuk koneksi database

// Cek jika user sudah login, arahkan ke dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error_message = '';
$success_message = '';

// Cek jika ada pesan dari halaman lain
if (isset($_GET['message'])) {
    if ($_GET['message'] == 'registration_success') {
        $success_message = "Akun admin berhasil dibuat! Silakan login.";
    } elseif ($_GET['message'] == 'admin_exists') {
        $error_message = "Admin sudah terdaftar. Silakan login.";
    }
}

// Proses form jika ada data POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: dashboard.php");
            exit;
        } else {
            $error_message = "Username atau password salah.";
        }
    } else {
        $error_message = "Username atau password salah.";
    }
    $stmt->close();
}
// Pastikan koneksi ditutup hanya jika $conn berhasil dibuat
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Sistem Perpustakaan Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlrZj/k7ujTnHg4C+0RkL1oQ5wS2t9i92t5J1n/w6L0/BfR0E01I+7j8f/2pW7d0w2b0pW2w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Base Styles - Professional Edition */
        body {
            background-color: #f4f6f8;
            /* Light gray background */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            color: #333;
            /* Dark gray text */
            overflow-x: hidden;
            position: relative;
        }

        /* Application Title - Professional */
        .app-title {
            color: #007bff;
            /* Primary blue */
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            text-align: center;
            letter-spacing: 0.5px;
            animation: fadeIn 0.5s ease-out;
        }

        .app-title i {
            font-size: 1em;
            color: #007bff;
            margin-right: 10px;
        }

        /* Login Card Container - Clean */
        .login-container {
            background-color: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            /* Soft shadow */
            padding: 2.5rem;
            max-width: 400px;
            width: 90%;
            animation: slideUp 0.5s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card-title {
            font-size: 2rem;
            font-weight: 500;
            color: #333;
            margin-bottom: 2rem;
            text-align: center;
        }

        .login-card-title i {
            font-size: 1.1em;
            color: #6c757d;
            /* Muted gray for icon */
            margin-right: 10px;
        }

        /* Form Control Styling - Simple & Effective */
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .form-control {
            border-radius: 0.5rem;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            border: 1px solid #ccc;
            /* Light gray border */
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus {
            border-color: #007bff;
            /* Primary blue on focus */
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            /* Subtle blue shadow on focus */
            outline: 0;
        }

        .mb-5 {
            margin-bottom: 1.5rem !important;
            /* Standard spacing for form groups */
        }

        /* Button Styling - Professional & Functional */
        .btn-primary {
            background-color: #007bff;
            /* Primary blue solid */
            border-color: #007bff;
            color: #fff;
            border-radius: 0.5rem;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            /* Darker blue on hover */
            border-color: #0056b3;
        }

        .btn-outline-secondary {
            color: #6c757d;
            /* Muted gray text */
            border-color: #6c757d;
            /* Muted gray border */
            border-radius: 0.5rem;
            padding: 0.7rem 1.4rem;
            font-size: 1rem;
            font-weight: 400;
            transition: color 0.15s ease-in-out, border-color 0.15s ease-in-out, background-color 0.15s ease-in-out;
        }

        .btn-outline-secondary:hover {
            color: #fff;
            background-color: #6c757d;
            /* Solid gray background on hover */
            border-color: #6c757d;
        }

        /* Alert Messages - Clear Feedback */
        .alert {
            border-radius: 0.5rem;
            font-size: 0.9rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .alert i {
            margin-right: 0.5rem;
        }

        /* Separator */
        hr {
            border-top: 1px solid #eee;
            /* Light solid line */
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        /* Registration text */
        .text-center p {
            color: #6c757d;
            /* Muted gray text */
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>

    <h1 class="app-title">
        <i class="fas fa-star"></i> Sistem Perpustakaan Kita
    </h1>

    <div class="login-container">
        <h2 class="login-card-title text-center">
            <i class="fas fa-lock"></i> Login Admin
        </h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-5">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required autocomplete="username" placeholder="Masukkan username Anda">
            </div>
            <div class="mb-5">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" placeholder="Masukkan password Anda">
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i> Masuk Dashboard
                </button>
            </div>
        </form>

        <hr>

        <div class="text-center">
            <p>Belum punya akun admin?</p>
            <a href="register_admin.php" class="btn btn-outline-secondary">
                <i class="fas fa-user-plus me-2"></i> Daftar Admin Baru
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>