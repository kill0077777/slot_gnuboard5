<?php
include_once('./zbpath.php');

// ===== 회원 로그인 확인 =====
if (!$is_member) {
    die(json_encode(['success' => false, 'message' => '로그인 후 이용 가능합니다.']));
}

// ===== AJAX 요청 처리 =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];
    
    if ($action === 'check_charge') {
        // 일일 충전 가능 여부 확인
        $member_data = sql_fetch("SELECT mb_point, last_daily_charge FROM {$g5['member_table']} WHERE mb_id = '{$member['mb_id']}'");
        
        $charge_amount = 50000;
        $can_charge = true;
        $message = '';
        $next_charge_time = '';
        
        if ($member_data['last_daily_charge']) {
            $last_charge = strtotime($member_data['last_daily_charge']);
            $today_start = strtotime(date('Y-m-d 00:00:00'));
            
            if ($last_charge >= $today_start) {
                // 오늘 이미 충전했음
                $can_charge = false;
                $next_charge_time = date('Y-m-d 00:00:00', strtotime('+1 day', $today_start));
                $message = '오늘 이미 충전했습니다. 내일 다시 충전할 수 있습니다.';
            }
        }
        
        $response['success'] = true;
        $response['can_charge'] = $can_charge;
        $response['charge_amount'] = $charge_amount;
        $response['current_point'] = (int)$member_data['mb_point'];
        $response['message'] = $message;
        $response['next_charge_time'] = $next_charge_time;
        
    } elseif ($action === 'do_charge') {
        // 실제 충전 처리
        $member_data = sql_fetch("SELECT mb_point, last_daily_charge FROM {$g5['member_table']} WHERE mb_id = '{$member['mb_id']}'");
        
        $charge_amount = 50000;
        $can_charge = true;
        
        // 중복 충전 방지
        if ($member_data['last_daily_charge']) {
            $last_charge = strtotime($member_data['last_daily_charge']);
            $today_start = strtotime(date('Y-m-d 00:00:00'));
            
            if ($last_charge >= $today_start) {
                $can_charge = false;
            }
        }
        
        if (!$can_charge) {
            $response['success'] = false;
            $response['message'] = '오늘 이미 충전했습니다.';
        } else {
            // 트랜잭션 시작
            sql_query("START TRANSACTION");
            
            try {
                // 포인트 충전
                sql_query("UPDATE {$g5['member_table']} 
                          SET mb_point = mb_point + {$charge_amount},
                              last_daily_charge = NOW()
                          WHERE mb_id = '{$member['mb_id']}'");
                
                // 포인트 기록 저장
                $log_table = $g5['table_prefix'] . 'slot_point_log';
                sql_query("INSERT INTO `{$log_table}` (mb_id, point_change, reason) 
                          VALUES ('{$member['mb_id']}', {$charge_amount}, '일일 무료 충전')");
                
                sql_query("COMMIT");
                
                // 업데이트된 포인트 조회
                $updated_member = sql_fetch("SELECT mb_point FROM {$g5['member_table']} WHERE mb_id = '{$member['mb_id']}'");
                
                $response['success'] = true;
                $response['message'] = '✅ 50,000 포인트가 충전되었습니다!';
                $response['new_point'] = (int)$updated_member['mb_point'];
                $response['charge_amount'] = $charge_amount;
                
                error_log("[Slot Module] 일일 무료 충전 - 회원: {$member['mb_id']}, 금액: {$charge_amount}");
                
            } catch (Exception $e) {
                sql_query("ROLLBACK");
                $response['success'] = false;
                $response['message'] = '충전 처리 중 오류가 발생했습니다.';
                error_log("[Slot Module] 일일 무료 충전 실패 - 에러: " . $e->getMessage());
            }
        }
    }
    
    echo json_encode($response);
    exit;
}

// ===== 페이지 로드 시 상태 확인 =====
$member_data = sql_fetch("SELECT mb_point, last_daily_charge FROM {$g5['member_table']} WHERE mb_id = '{$member['mb_id']}'");

$charge_amount = 50000;
$can_charge = true;
$message = '';
$next_charge_time = '';

if ($member_data['last_daily_charge']) {
    $last_charge = strtotime($member_data['last_daily_charge']);
    $today_start = strtotime(date('Y-m-d 00:00:00'));
    
    if ($last_charge >= $today_start) {
        $can_charge = false;
        $next_charge_time = date('Y-m-d 00:00:00', strtotime('+1 day', $today_start));
        $message = '오늘 이미 충전했습니다. 내일 다시 충전할 수 있습니다.';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>일일 무료 충전</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
    .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 30px; text-align: center; }
    .header h1 { font-size: 32px; margin-bottom: 10px; }
    .header p { font-size: 14px; opacity: 0.9; }
    .content { padding: 40px 30px; }
    
    .charge-box { 
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border: 2px solid #667eea;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .charge-amount { 
        font-size: 48px; 
        font-weight: bold; 
        color: #667eea;
        margin-bottom: 10px;
    }
    
    .charge-label { 
        font-size: 14px; 
        color: #666;
        margin-bottom: 20px;
    }
    
    .current-point {
        background: white;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        text-align: center;
    }
    
    .current-point label {
        display: block;
        font-size: 12px;
        color: #999;
        margin-bottom: 5px;
    }
    
    .current-point value {
        display: block;
        font-size: 24px;
        font-weight: bold;
        color: #667eea;
    }
    
    .btn {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 10px;
    }
    
    .btn-charge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-charge:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-charge:disabled {
        background: #ccc;
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    .message {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        text-align: center;
        font-size: 13px;
    }
    
    .message.info {
        background: #e7f3ff;
        color: #004085;
        border: 1px solid #b3d9ff;
    }
    
    .message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .message.warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .info-section {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 5px;
        margin-top: 20px;
        border-left: 4px solid #667eea;
    }
    
    .info-section h3 {
        color: #333;
        margin-bottom: 10px;
        font-size: 14px;
    }
    
    .info-section ul {
        list-style: none;
        font-size: 13px;
        color: #666;
        line-height: 1.8;
    }
    
    .info-section li:before {
        content: "✓ ";
        color: #667eea;
        font-weight: bold;
        margin-right: 5px;
    }
    
    .loading {
        display: none;
        text-align: center;
        padding: 20px;
    }
    
    .spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #667eea;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .back-link {
        display: inline-block;
        margin-top: 20px;
        color: #667eea;
        text-decoration: none;
        font-size: 13px;
    }
    
    .back-link:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>💰 일일 무료 충전</h1>
        <p>매일 50,000 포인트를 무료로 충전하세요!</p>
    </div>
    
    <div class="content">
        <!-- 현재 포인트 -->
        <div class="current-point">
            <label>현재 보유 포인트</label>
            <value id="current-point"><?php echo number_format($member_data['mb_point']); ?></value>
        </div>
        
        <!-- 상태 메시지 -->
        <?php if (!$can_charge): ?>
            <div class="message warning">
                ⏰ <?php echo htmlspecialchars($message); ?>
                <br>
                <small>다음 충전 가능 시간: <?php echo date('Y년 m월 d일 00:00', strtotime($next_charge_time)); ?></small>
            </div>
        <?php else: ?>
            <div class="message info">
                ✨ 오늘의 무료 충전 기회를 사용하세요!
            </div>
        <?php endif; ?>
        
        <!-- 충전 박스 -->
        <div class="charge-box">
            <div class="charge-amount">+50,000</div>
            <div class="charge-label">포인트</div>
        </div>
        
        <!-- 충전 버튼 -->
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p style="margin-top: 10px; color: #999; font-size: 13px;">처리 중...</p>
        </div>
        
        <button 
            class="btn btn-charge" 
            id="charge-btn"
            onclick="doCharge()"
            <?php echo !$can_charge ? 'disabled' : ''; ?>
        >
            <?php echo $can_charge ? '🎁 50,000 포인트 받기' : '⏰ 내일 다시 충전 가능'; ?>
        </button>
        
        <!-- 안내 정보 -->
        <div class="info-section">
            <h3>📋 충전 안내</h3>
            <ul>
                <li>매일 1회만 충전 가능합니다</li>
                <li>자정(00:00)을 기준으로 초기화됩니다</li>
                <li>충전된 포인트는 게임에서 사용할 수 있습니다</li>
                <li>포인트는 다른 회원에게 선물할 수 있습니다</li>
            </ul>
        </div>
        
        <a href="smain.php" class="back-link">← 게임으로 돌아가기</a>
    </div>
</div>

<script>
function doCharge() {
    const btn = document.getElementById('charge-btn');
    const loading = document.getElementById('loading');
    
    if (btn.disabled) return;
    
    btn.style.display = 'none';
    loading.style.display = 'block';
    
    const formData = new FormData();
    formData.append('action', 'do_charge');
    
    fetch('daily_charge.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        loading.style.display = 'none';
        
        if (data.success) {
            // 성공 메시지 표시
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message success';
            messageDiv.textContent = data.message;
            document.querySelector('.content').insertBefore(messageDiv, document.querySelector('.current-point').nextSibling);
            
            // 포인트 업데이트
            document.getElementById('current-point').textContent = new Intl.NumberFormat('ko-KR').format(data.new_point);
            
            // 버튼 비활성화
            btn.textContent = '⏰ 내일 다시 충전 가능';
            btn.disabled = true;
            btn.style.display = 'block';
            
            // 3초 후 게임 페이지로 이동
            setTimeout(() => {
                window.location.href = 'smain.php';
            }, 3000);
        } else {
            // 실패 메시지 표시
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message warning';
            messageDiv.textContent = data.message;
            document.querySelector('.content').insertBefore(messageDiv, document.querySelector('.current-point').nextSibling);
            
            btn.style.display = 'block';
        }
    })
    .catch(error => {
        loading.style.display = 'none';
        btn.style.display = 'block';
        alert('오류가 발생했습니다: ' + error);
    });
}
</script>

</body>
</html>
