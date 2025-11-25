<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$error = '';
$success = '';

// Ambil data kategori
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$category_id = intval($_GET['id']);

// Ambil data kategori yang akan diedit
$stmt = $conn->prepare("SELECT * FROM category WHERE category_id = ? AND user_id = ?");
$stmt->bind_param("ii", $category_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$category = $result->fetch_assoc();
$stmt->close();

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);
    
    if (empty($category_name)) {
        $error = "Nama kategori harus diisi!";
    } else {
        // Update dengan last_update otomatis
        $stmt = $conn->prepare("UPDATE category SET name = ?, description = ?, last_update = CURRENT_TIMESTAMP WHERE category_id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $category_name, $description, $category_id, $user_id);
        
        if ($stmt->execute()) {
            $success = "Kategori berhasil diupdate! Trigger telah mencatat perubahan ini.";
            
            // Refresh data kategori
            $stmt = $conn->prepare("SELECT * FROM category WHERE category_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $category_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $category = $result->fetch_assoc();
            $stmt->close();
        } else {
            $error = "Gagal mengupdate kategori: " . $conn->error;
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kategori - Category Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .navbar {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar h1 {
            font-size: 24px;
            color: #667eea;
        }
        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .navbar .user-info span {
            color: #333;
        }
        .navbar a {
            color: #667eea;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(102,126,234,0.1);
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: 500;
        }
        .navbar a:hover {
            background: #667eea;
            color: white;
        }
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .form-card {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .form-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .trigger-info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin-bottom: 25px;
            border-radius: 5px;
            font-size: 13px;
            color: #856404;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border 0.3s;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        button, .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        button[type="submit"] {
            background: #667eea;
            color: white;
        }
        button[type="submit"]:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.3);
        }
        .btn-back {
            background: #6c757d;
            color: white;
        }
        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📂 Category Manager</h1>
        <div class="user-info">
            <span>👤 <strong><?php echo e($username); ?></strong></span>
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo e($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo e($error); ?></div>
        <?php endif; ?>
        
        <div class="form-card">
            <h3>✏️ Edit Kategori</h3>
            <div class="trigger-info">
                ⚡ <strong>Trigger Active:</strong> Setiap perubahan akan dicatat secara otomatis di riwayat aktivitas
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="category_name">Nama Kategori *</label>
                    <input type="text" id="category_name" name="category_name" 
                           value="<?php echo e($category['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description"><?php echo e($category['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Last Update</label>
                    <input type="text" value="<?php echo date('d/m/Y H:i:s', strtotime($category['last_update'])); ?>" disabled>
                    <small style="color: #999; display: block; margin-top: 5px;">
                        ℹ️ Akan diupdate otomatis saat menyimpan perubahan
                    </small>
                </div>
                
                <div class="button-group">
                    <button type="submit">💾 Update Kategori</button>
                    <a href="dashboard.php" class="btn btn-back">◀️ Kembali</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>