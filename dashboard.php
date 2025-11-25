<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();

// Ambil data user
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Gunakan UDF untuk menghitung jumlah kategori user
$stmt = $conn->prepare("SELECT fn_count_user_categories(?) as total_categories");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$count_data = $result->fetch_assoc();
$total_categories = $count_data['total_categories'];
$stmt->close();

// Ambil SEMUA kategori yang ada di database (tidak filter by user_id)
// Untuk menampilkan semua nama kategori yang ada saat ini
$query = "
    SELECT 
        c.category_id,
        c.name,
        c.description,
        c.last_update,
        c.user_id,
        fn_get_username(c.user_id) as owner_username
    FROM category c 
    ORDER BY c.last_update DESC
";
$result = $conn->query($query);
$all_categories = $result->fetch_all(MYSQLI_ASSOC);

// Ambil kategori milik user yang login
$stmt = $conn->prepare("
    SELECT 
        c.category_id,
        c.name,
        c.description,
        c.last_update,
        c.user_id,
        fn_get_username(c.user_id) as owner_username
    FROM category c 
    WHERE c.user_id = ? 
    ORDER BY c.last_update DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$my_categories = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle tambah kategori menggunakan stored procedure
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);
    
    if (empty($category_name)) {
        $error = "Nama kategori harus diisi!";
    } else {
        // Panggil stored procedure
        $stmt = $conn->prepare("CALL sp_insert_category(?, ?, ?)");
        $stmt->bind_param("ssi", $category_name, $description, $user_id);
        
        if ($stmt->execute()) {
            $success = "Kategori berhasil ditambahkan menggunakan Stored Procedure!";
            $stmt->close();
            
            // Refresh halaman untuk menampilkan data terbaru
            header("Location: dashboard.php?success=1");
            exit();
        } else {
            $error = "Gagal menambahkan kategori: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $category_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM category WHERE category_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $category_id, $user_id);
    
    if ($stmt->execute()) {
        header("Location: dashboard.php?deleted=1");
        exit();
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Category Manager</title>
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .stats-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stats-card h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .stats-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stats-number {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
        }
        .stats-label {
            color: #666;
            font-size: 14px;
        }
        .udf-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 8px;
        }
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .form-card h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .sp-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #f3e5f5;
            color: #7b1fa2;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 10px;
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
            min-height: 80px;
        }
        button {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s;
        }
        button:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.3);
        }
        .category-grid {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .category-grid h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .category-item {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 10px;
            border-left: 5px solid #667eea;
            transition: all 0.3s;
        }
        .category-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .category-item h4 {
            color: #333;
            margin-bottom: 8px;
            font-size: 18px;
        }
        .category-item p {
            color: #666;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        .category-meta {
            font-size: 12px;
            color: #999;
            margin-bottom: 12px;
        }
        .category-actions {
            display: flex;
            gap: 10px;
        }
        .category-actions a {
            padding: 6px 14px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-edit {
            background: #4CAF50;
            color: white;
        }
        .btn-edit:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        .btn-delete {
            background: #f44336;
            color: white;
        }
        .btn-delete:hover {
            background: #da190b;
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
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state svg {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📂 Category Manager</h1>
        <div class="user-info">
            <span>👤 <strong><?php echo e($username); ?></strong></span>
            <a href="riwayat.php">📋 Riwayat</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">✅ Kategori berhasil ditambahkan menggunakan Stored Procedure!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">✅ Kategori berhasil dihapus! Trigger telah mencatat aktivitas ini.</div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo e($error); ?></div>
        <?php endif; ?>
        
        <div class="stats-card">
            <h2>📊 Statistik</h2>
            <div class="stats-content">
                <div class="stats-number"><?php echo $total_categories; ?></div>
                <div>
                    <div class="stats-label">Total Kategori Anda</div>
                    <span class="udf-badge">🔧 Calculated by UDF</span>
                </div>
            </div>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                <div style="color: #666; font-size: 14px;">
                    📁 Total semua kategori di database: <strong><?php echo count($all_categories); ?></strong>
                </div>
            </div>
        </div>
        
        <!-- Tampilkan SEMUA kategori yang ada di database -->
        <div class="category-grid" style="margin-bottom: 25px;">
            <h3>🌐 Semua Kategori di Database</h3>
            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                Menampilkan semua kategori dari kolom 'name' di tabel 'category'
            </p>
            
            <?php if (empty($all_categories)): ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p>Belum ada kategori di database</p>
                </div>
            <?php else: ?>
                <?php foreach ($all_categories as $category): ?>
                    <div class="category-item">
                        <h4><?php echo e($category['name']); ?></h4>
                        <p><?php echo e($category['description'] ?: 'Tidak ada deskripsi'); ?></p>
                        <div class="category-meta">
                            👤 Dibuat oleh: <strong><?php echo e($category['owner_username'] ?: 'Unknown'); ?></strong> <span class="udf-badge">UDF</span> | 
                            📅 Update terakhir: <?php echo date('d/m/Y H:i', strtotime($category['last_update'])); ?>
                        </div>
                        
                        <?php if ($category['user_id'] == $user_id): ?>
                        <div class="category-actions">
                            <a href="edit.php?id=<?php echo $category['category_id']; ?>" class="btn-edit">✏️ Edit</a>
                            <a href="dashboard.php?delete=<?php echo $category['category_id']; ?>" 
                               class="btn-delete" 
                               onclick="return confirm('⚠️ Yakin ingin menghapus kategori ini? Trigger akan mencatat aktivitas ini.')">🗑️ Hapus</a>
                        </div>
                        <?php else: ?>
                        <div style="padding: 8px 12px; background: #e3f2fd; color: #1976d2; border-radius: 5px; font-size: 12px; display: inline-block; margin-top: 10px;">
                            👁️ View Only - Bukan milik Anda
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="form-card">
            <h3>➕ Tambah Kategori Baru <span class="sp-badge">📦 Stored Procedure</span></h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="category_name">Nama Kategori *</label>
                    <input type="text" id="category_name" name="category_name" placeholder="Masukkan nama kategori" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" placeholder="Masukkan deskripsi kategori (opsional)"></textarea>
                </div>
                
                <button type="submit">💾 Simpan Kategori</button>
            </form>
        </div>
        
        <div class="category-grid">
            <h3>📁 Kategori Milik Saya</h3>
            
            <?php if (empty($my_categories)): ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p>Anda belum memiliki kategori. Tambahkan kategori pertama Anda!</p>
                </div>
            <?php else: ?>
                <?php foreach ($my_categories as $category): ?>
                    <div class="category-item">
                        <h4><?php echo e($category['name']); ?></h4>
                        <p><?php echo e($category['description'] ?: 'Tidak ada deskripsi'); ?></p>
                        <div class="category-meta">
                            👤 Dibuat oleh: <strong><?php echo e($category['owner_username']); ?></strong> <span class="udf-badge">UDF</span> | 
                            📅 Update terakhir: <?php echo date('d/m/Y H:i', strtotime($category['last_update'])); ?>
                        </div>
                        <div class="category-actions">
                            <a href="edit.php?id=<?php echo $category['category_id']; ?>" class="btn-edit">✏️ Edit</a>
                            <a href="dashboard.php?delete=<?php echo $category['category_id']; ?>" 
                               class="btn-delete" 
                               onclick="return confirm('⚠️ Yakin ingin menghapus kategori ini? Trigger akan mencatat aktivitas ini.')">🗑️ Hapus</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>