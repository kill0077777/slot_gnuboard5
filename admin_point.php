<?php
include_once('./zbpath.php');

// ===== 관리자 권한 검증 =====
if (!$is_admin) {
    die('관리자만 접근 가능합니다.');
}

// ===== 포인트 충전/차감 처리 =====
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $target_id = isset($_POST['target_id']) ? trim($_POST['target_id']) : '';
    $point_amount = isset($_POST['point_amount']) ? (int)$_POST['point_amount'] : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    
    // 입력값 검증
    if (empty($target_id) || $point_amount <= 0 || empty($reason)) {
        $error = '모든 필드를 올바르게 입력해주세요.';
    } else {
        // 회원 존재 여부 확인
        $member_check = sql_fetch("SELECT mb_id, mb_nick, mb_point FROM {$g5['member_table']} WHERE mb_id = '{$target_id}'");
        
        if (!$member_check) {
            $error = '존재하지 않는 회원입니다.';
        } else {
            if ($action === 'charge') {
                // 포인트 충전
                sql_query("UPDATE {$g5['member_table']} SET mb_point = mb_point + {$point_amount} WHERE mb_id = '{$target_id}'");
                
                // 포인트 기록 저장
                $log_table = $g5['table_prefix'] . 'slot_point_log';
                sql_query("INSERT INTO `{$log_table}` (mb_id, point_change, reason, admin_id) VALUES ('{$target_id}', {$point_amount}, '{$reason}', '{$member['mb_id']}')");
                
                $message = "✅ {$member_check['mb_nick']} 회원에게 {$point_amount} 포인트를 충전했습니다.";
                error_log("[Slot Module] 포인트 충전 - 대상: {$target_id}, 금액: {$point_amount}, 관리자: {$member['mb_id']}");
                
            } elseif ($action === 'deduct') {
                // 포인트 차감
                if ($member_check['mb_point'] < $point_amount) {
                    $error = '회원의 포인트가 부족합니다.';
                } else {
                    sql_query("UPDATE {$g5['member_table']} SET mb_point = mb_point - {$point_amount} WHERE mb_id = '{$target_id}'");
                    
                    // 포인트 기록 저장
                    $log_table = $g5['table_prefix'] . 'slot_point_log';
                    sql_query("INSERT INTO `{$log_table}` (mb_id, point_change, reason, admin_id) VALUES ('{$target_id}', -{$point_amount}, '{$reason}', '{$member['mb_id']}')");
                    
                    $message = "✅ {$member_check['mb_nick']} 회원에게서 {$point_amount} 포인트를 차감했습니다.";
                    error_log("[Slot Module] 포인트 차감 - 대상: {$target_id}, 금액: {$point_amount}, 관리자: {$member['mb_id']}");
                }
            }
        }
    }
}

// ===== 포인트 기록 조회 =====
$log_table = $g5['table_prefix'] . 'slot_point_log';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$log_result = sql_query("SELECT * FROM `{$log_table}` ORDER BY created_at DESC LIMIT {$offset}, {$limit}");
$log_count = sql_fetch("SELECT COUNT(*) as cnt FROM `{$log_table}`");
$total_pages = ceil($log_count['cnt'] / $limit);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>포인트 관리 (관리자)</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
    .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; }
    .header h1 { font-size: 28px; margin-bottom: 5px; }
    .content { padding: 30px; }
    .form-section { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #e0e0e0; }
    .form-section h2 { color: #333; margin-bottom: 20px; font-size: 18px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; color: #333; font-weight: 600; font-size: 13px; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
    .form-group textarea { resize: vertical; min-height: 60px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s; }
    .btn-charge { background: #28a745; color: white; }
    .btn-charge:hover { background: #218838; }
    .btn-deduct { background: #dc3545; color: white; }
    .btn-deduct:hover { background: #c82333; }
    .message { padding: 12px; margin-bottom: 20px; border-radius: 4px; }
    .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .table-section h2 { color: #333; margin-bottom: 15px; font-size: 18px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table th { background: #f5f5f5; padding: 12px; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #ddd; font-size: 13px; }
    table td { padding: 12px; border-bottom: 1px solid #e0e0e0; font-size: 13px; }
    table tr:hover { background: #f9f9f9; }
    .tag { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; }
    .tag-charge { background: #d4edda; color: #155724; }
    .tag-deduct { background: #f8d7da; color: #721c24; }
    .pagination { text-align: center; margin-top: 20px; }
    .pagination a, .pagination span { padding: 8px 12px; margin: 0 3px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #667eea; }
    .pagination a:hover { background: #667eea; color: white; }
    .pagination .current { background: #667eea; color: white; border-color: #667eea; }
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🔧 포인트 관리 (관리자 전용)</h1>
        <p>회원 포인트 충전 및 차감, 기록 조회</p>
    </div>
    
    <div class="content">
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- 포인트 충전/차감 폼 -->
        <div class="form-section">
            <h2>포인트 충전/차감</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>회원 ID</label>
                        <input type="text" name="target_id" placeholder="회원 ID를 입력하세요" required>
                    </div>
                    <div class="form-group">
                        <label>포인트 금액</label>
                        <input type="number" name="point_amount" placeholder="포인트 금액" min="1" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>사유</label>
                    <textarea name="reason" placeholder="포인트 변화 사유를 입력하세요" required></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="action" value="charge" class="btn btn-charge">✅ 포인트 충전</button>
                    <button type="submit" name="action" value="deduct" class="btn btn-deduct">❌ 포인트 차감</button>
                </div>
            </form>
        </div>
        
        <!-- 포인트 기록 조회 -->
        <div class="table-section">
            <h2>포인트 변화 기록</h2>
            <table>
                <thead>
                    <tr>
                        <th>회원 ID</th>
                        <th>포인트 변화</th>
                        <th>사유</th>
                        <th>관리자</th>
                        <th>생성 시간</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = sql_fetch_array($log_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['mb_id']); ?></td>
                            <td>
                                <span class="tag <?php echo $log['point_change'] > 0 ? 'tag-charge' : 'tag-deduct'; ?>">
                                    <?php echo ($log['point_change'] > 0 ? '+' : '') . number_format($log['point_change']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['reason']); ?></td>
                            <td><?php echo htmlspecialchars($log['admin_id'] ?? '-'); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
