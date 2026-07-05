<?php
include_once('./zbpath.php');

// ===== 회원 로그인 확인 =====
if (!$is_member) {
    die('로그인 후 이용 가능합니다.');
}

$message = '';
$error = '';

// ===== 포인트 선물 처리 =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_gift') {
    $recipient_id = isset($_POST['recipient_id']) ? trim($_POST['recipient_id']) : '';
    $gift_amount = isset($_POST['gift_amount']) ? (int)$_POST['gift_amount'] : 0;
    $message_text = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // 입력값 검증
    if (empty($recipient_id)) {
        $error = '받는 회원을 선택해주세요.';
    } elseif ($gift_amount <= 0) {
        $error = '선물할 포인트는 1 이상이어야 합니다.';
    } elseif ($gift_amount > 100000) {
        $error = '한 번에 선물할 수 있는 최대 포인트는 100,000입니다.';
    } elseif ($member['mb_point'] < $gift_amount) {
        $error = '포인트가 부족합니다.';
    } else {
        // 받는 회원 확인
        $recipient = sql_fetch("SELECT mb_id, mb_nick, mb_point FROM {$g5['member_table']} WHERE mb_id = '{$recipient_id}'");
        
        if (!$recipient) {
            $error = '존재하지 않는 회원입니다.';
        } elseif ($recipient['mb_id'] === $member['mb_id']) {
            $error = '자신에게는 포인트를 선물할 수 없습니다.';
        } else {
            // 트랜잭션 시작
            sql_query("START TRANSACTION");
            
            try {
                // 보내는 회원 포인트 차감
                sql_query("UPDATE {$g5['member_table']} SET mb_point = mb_point - {$gift_amount} WHERE mb_id = '{$member['mb_id']}'");
                
                // 받는 회원 포인트 증가
                sql_query("UPDATE {$g5['member_table']} SET mb_point = mb_point + {$gift_amount} WHERE mb_id = '{$recipient_id}'");
                
                // 포인트 기록 저장
                $log_table = $g5['table_prefix'] . 'slot_point_log';
                sql_query("INSERT INTO `{$log_table}` (mb_id, point_change, reason, gift_from, gift_to) 
                          VALUES ('{$member['mb_id']}', -{$gift_amount}, '포인트 선물', '{$member['mb_id']}', '{$recipient_id}')");
                
                sql_query("INSERT INTO `{$log_table}` (mb_id, point_change, reason, gift_from, gift_to) 
                          VALUES ('{$recipient_id}', {$gift_amount}, '포인트 선물 수령', '{$member['mb_id']}', '{$recipient_id}')");
                
                sql_query("COMMIT");
                
                $message = "✅ {$recipient['mb_nick']} 회원에게 {$gift_amount} 포인트를 선물했습니다.";
                error_log("[Slot Module] 포인트 선물 - 보낸이: {$member['mb_id']}, 받는이: {$recipient_id}, 금액: {$gift_amount}");
                
                // 페이지 새로고침 후 메시지 표시
                header("Location: gift.php?success=1");
                exit;
                
            } catch (Exception $e) {
                sql_query("ROLLBACK");
                $error = '선물 처리 중 오류가 발생했습니다.';
                error_log("[Slot Module] 포인트 선물 실패 - 에러: " . $e->getMessage());
            }
        }
    }
}

// ===== 선물 기록 조회 =====
$log_table = $g5['table_prefix'] . 'slot_point_log';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 받은 선물
$received_gifts = sql_query("SELECT * FROM `{$log_table}` 
                            WHERE gift_to = '{$member['mb_id']}' AND point_change > 0
                            ORDER BY created_at DESC 
                            LIMIT {$offset}, {$limit}");

$received_count = sql_fetch("SELECT COUNT(*) as cnt FROM `{$log_table}` 
                            WHERE gift_to = '{$member['mb_id']}' AND point_change > 0");
$received_pages = ceil($received_count['cnt'] / $limit);

// 보낸 선물
$sent_gifts = sql_query("SELECT * FROM `{$log_table}` 
                        WHERE gift_from = '{$member['mb_id']}' AND point_change < 0
                        ORDER BY created_at DESC 
                        LIMIT {$offset}, {$limit}");

$sent_count = sql_fetch("SELECT COUNT(*) as cnt FROM `{$log_table}` 
                        WHERE gift_from = '{$member['mb_id']}' AND point_change < 0");
$sent_pages = ceil($sent_count['cnt'] / $limit);

// 회원 목록 조회 (선물 받을 회원)
$members_list = sql_query("SELECT mb_id, mb_nick FROM {$g5['member_table']} 
                          WHERE mb_id != '{$member['mb_id']}' AND mb_leave_date = ''
                          ORDER BY mb_id ASC LIMIT 100");

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>포인트 선물</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
    .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; }
    .header h1 { font-size: 28px; margin-bottom: 5px; }
    .content { padding: 30px; }
    
    .tabs { display: flex; border-bottom: 2px solid #e0e0e0; margin-bottom: 20px; }
    .tab { padding: 12px 20px; cursor: pointer; border: none; background: none; font-size: 14px; font-weight: 600; color: #999; transition: all 0.3s; }
    .tab.active { color: #667eea; border-bottom: 3px solid #667eea; margin-bottom: -2px; }
    
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    
    .form-section { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e0e0e0; }
    .form-section h2 { color: #333; margin-bottom: 15px; font-size: 16px; }
    
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; color: #333; font-weight: 600; font-size: 13px; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
    .form-group textarea { resize: vertical; min-height: 60px; }
    
    .info-box { background: #e7f3ff; border-left: 4px solid #667eea; padding: 12px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; color: #004085; }
    
    .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s; }
    .btn-primary { background: #667eea; color: white; }
    .btn-primary:hover { background: #764ba2; }
    
    .message { padding: 12px; margin-bottom: 20px; border-radius: 4px; }
    .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table th { background: #f5f5f5; padding: 12px; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #ddd; font-size: 13px; }
    table td { padding: 12px; border-bottom: 1px solid #e0e0e0; font-size: 13px; }
    table tr:hover { background: #f9f9f9; }
    
    .tag { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; }
    .tag-receive { background: #d4edda; color: #155724; }
    .tag-send { background: #f8d7da; color: #721c24; }
    
    .pagination { text-align: center; margin-top: 20px; }
    .pagination a, .pagination span { padding: 8px 12px; margin: 0 3px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #667eea; }
    .pagination a:hover { background: #667eea; color: white; }
    .pagination .current { background: #667eea; color: white; border-color: #667eea; }
    
    .no-data { text-align: center; padding: 40px; color: #999; }
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🎁 포인트 선물</h1>
        <p>다른 회원에게 포인트를 선물하세요</p>
    </div>
    
    <div class="content">
        <?php if (isset($_GET['success'])): ?>
            <div class="message success">✅ 포인트 선물이 완료되었습니다.</div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- 탭 네비게이션 -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('send')">선물 보내기</button>
            <button class="tab" onclick="switchTab('received')">받은 선물</button>
            <button class="tab" onclick="switchTab('sent')">보낸 선물</button>
        </div>
        
        <!-- 선물 보내기 탭 -->
        <div id="send" class="tab-content active">
            <div class="form-section">
                <h2>포인트 선물 보내기</h2>
                
                <div class="info-box">
                    💡 현재 보유 포인트: <strong><?php echo number_format($member['mb_point']); ?></strong>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>받는 회원 선택</label>
                        <select name="recipient_id" required>
                            <option value="">-- 회원을 선택하세요 --</option>
                            <?php while ($m = sql_fetch_array($members_list)): ?>
                                <option value="<?php echo htmlspecialchars($m['mb_id']); ?>">
                                    <?php echo htmlspecialchars($m['mb_nick']); ?> (<?php echo htmlspecialchars($m['mb_id']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>선물할 포인트</label>
                        <input type="number" name="gift_amount" placeholder="1 ~ 100,000" min="1" max="100000" required>
                    </div>
                    
                    <div class="form-group">
                        <label>메시지 (선택사항)</label>
                        <textarea name="message" placeholder="선물과 함께 보낼 메시지를 입력하세요"></textarea>
                    </div>
                    
                    <button type="submit" name="action" value="send_gift" class="btn btn-primary">🎁 선물 보내기</button>
                </form>
            </div>
        </div>
        
        <!-- 받은 선물 탭 -->
        <div id="received" class="tab-content">
            <h2>받은 선물 기록</h2>
            
            <?php if (sql_num_rows($received_gifts) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>보낸 회원</th>
                            <th>포인트</th>
                            <th>받은 시간</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($gift = sql_fetch_array($received_gifts)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($gift['gift_from']); ?></td>
                                <td>
                                    <span class="tag tag-receive">
                                        +<?php echo number_format($gift['point_change']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($gift['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <?php if ($received_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $received_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-data">
                    <p>받은 선물이 없습니다.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 보낸 선물 탭 -->
        <div id="sent" class="tab-content">
            <h2>보낸 선물 기록</h2>
            
            <?php if (sql_num_rows($sent_gifts) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>받은 회원</th>
                            <th>포인트</th>
                            <th>보낸 시간</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($gift = sql_fetch_array($sent_gifts)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($gift['gift_to']); ?></td>
                                <td>
                                    <span class="tag tag-send">
                                        <?php echo number_format($gift['point_change']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($gift['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <?php if ($sent_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $sent_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-data">
                    <p>보낸 선물이 없습니다.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // 모든 탭 컨텐츠 숨기기
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => content.classList.remove('active'));
    
    // 모든 탭 버튼 비활성화
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // 선택한 탭 활성화
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}
</script>

</body>
</html>
