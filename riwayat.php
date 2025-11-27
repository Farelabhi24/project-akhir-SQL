<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Ambil riwayat aktivitas user dengan menggunakan UDF untuk mendapatkan username
$stmt = $conn->prepare("
    SELECT 
        h.history_id,
        h.table_name,
        h.operation,
        h.record_id,
        h.old_data,
        h.new_data,
        h.created_at,
        fn_get_username(h.user_id) as username
    FROM activity_history h
    WHERE h.user_id = ?
    ORDER BY h.created_at DESC
    LIMIT 100
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$history_list = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Aktivitas - Category Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4a4c54ff 0%, #d9d9d9ff 100%);
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
            color: #34384bff;
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
            color: #34384bff;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(102,126,234,0.1);
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: 500;
        }
        .navbar a:hover {
            background: #34384bff;
            color: white;
        }
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .history-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .history-card h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .trigger-badge {
            display: inline-block;
            padding: 6px 12px;
            background: linear-gradient(135deg, #4a4c54ff 0%, #34384bff 100%);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .info-text {
            color: #666;
            margin-bottom: 25px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        thead {
            background: linear-gradient(135deg, #34384bff 0%, #4a4c54ff 100%);
            color: white;
        }
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        tbody tr {
            transition: background 0.3s;
        }
        tbody tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-insert {
            background: #d4edda;
            color: #155724;
        }
        .badge-update {
            background: #fff3cd;
            color: #856404;
        }
        .badge-delete {
            background: #f8d7da;
            color: #721c24;
        }
        .data-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #666;
            font-size: 13px;
        }
        .data-cell:hover {
            white-space: normal;
            word-wrap: break-word;
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
        .username-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
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
        <div class="history-card">
            <h2>📋 Riwayat Aktivitas</h2>
            <span class="trigger-badge">Automatic Logging by Database Triggers</span>
            
            <div class="info-text">
                <strong>Info:</strong> Semua aktivitas INSERT, UPDATE, dan DELETE pada tabel category dicatat secara otomatis menggunakan Database Triggers. Username ditampilkan menggunakan User Defined Function (UDF).
            </div>
            
            <?php if (empty($history_list)): ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p>Belum ada riwayat aktivitas</p>
                    <small>Aktivitas akan muncul di sini setelah Anda melakukan perubahan data</small>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th style="width: 100px;">Tabel</th>
                                <th style="width: 100px;">Operasi</th>
                                <th style="width: 80px;">Record ID</th>
                                <th style="width: 120px;">User</th>
                                <th>Data Lama</th>
                                <th>Data Baru</th>
                                <th style="width: 140px;">Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history_list as $history): ?>
                                <tr>
                                    <td><?php echo $history['history_id']; ?></td>
                                    <td><strong><?php echo e($history['table_name']); ?></strong></td>
                                    <td>
                                        <?php 
                                        $operation = $history['operation'];
                                        $badge_class = 'badge-insert';
                                        $icon = '';
                                        if ($operation == 'UPDATE') {
                                            $badge_class = 'badge-update';
                                            $icon = '';
                                        } elseif ($operation == 'DELETE') {
                                            $badge_class = 'badge-delete';
                                            $icon = '';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $icon . ' ' . $operation; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $history['record_id']; ?></td>
                                    <td>
                                        <span class="username-badge">
                                             <?php echo e($history['username']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="data-cell" title="<?php echo e($history['old_data']); ?>">
                                            <?php echo $history['old_data'] ? e($history['old_data']) : '-'; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="data-cell" title="<?php echo e($history['new_data']); ?>">
                                            <?php echo $history['new_data'] ? e($history['new_data']) : '-'; ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($history['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 13px; color: #666;">
                    <strong>📊 Statistik:</strong> Menampilkan <?php echo count($history_list); ?> aktivitas terakhir
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
