<?php
include_once('./_common.php');

// ===== 관리자 권한 검증 =====
if (!$is_admin) {
    die('관리자만 실행 가능합니다.');
}

// ===== CSRF 토큰 검증 =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['slot_install_token']) {
        die('보안 토큰이 유효하지 않습니다.');
    }
}

// CSRF 토큰 생성
if (!isset($_SESSION['slot_install_token'])) {
    $_SESSION['slot_install_token'] = bin2hex(random_bytes(32));
}

$token = $_SESSION['slot_install_token'];
$result_message = '';
$error_message = '';

// ===== 설치 처리 =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    
    // 1. g5_member 테이블에 필요한 컬럼 추가
    // gamepoint는 mb_point로 통합되므로 제거
    $sql = " ALTER TABLE {$g5['member_table']} 
                ADD `bar` INT( 2 ) DEFAULT '0' NOT NULL , 
                ADD `bell` INT( 2 ) DEFAULT '5' NOT NULL , 
                ADD `bellbonus` INT( 2 ) DEFAULT '0' NOT NULL , 
                ADD `allseven` INT( 4 ) DEFAULT '0' NOT NULL , 
                ADD `allitemh` INT( 4 ) DEFAULT '0' NOT NULL , 
                ADD `alliteml` INT( 4 ) DEFAULT '0' NOT NULL , 
                ADD `crossseven` INT( 4 ) DEFAULT '0' NOT NULL , 
                ADD `rankpoint` INT( 6 ) DEFAULT '0' NOT NULL , 
                ADD `box1` INT( 1 ) NOT NULL , 
                ADD `box2` INT( 1 ) NOT NULL , 
                ADD `box3` INT( 1 ) NOT NULL , 
                ADD `box4` INT( 1 ) NOT NULL , 
                ADD `box5` INT( 1 ) NOT NULL , 
                ADD `box6` INT( 1 ) NOT NULL , 
                ADD `box7` INT( 1 ) NOT NULL , 
                ADD `box8` INT( 1 ) NOT NULL , 
                ADD `box9` INT( 1 ) NOT NULL , 
                ADD `last_daily_charge` DATETIME DEFAULT NULL COMMENT '마지막 일일 충전 시간' ";

    $result = sql_query($sql, false);

    if ($result) {
        // 2. 포인트 충전 기록 테이블 생성
        $table_name = $g5['table_prefix'] . 'slot_point_log';
        
        $create_table_sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '기록 ID',
            `mb_id` VARCHAR(20) NOT NULL COMMENT '회원 ID',
            `point_change` INT NOT NULL COMMENT '포인트 변화량 (양수: 증가, 음수: 감소)',
            `reason` VARCHAR(100) NOT NULL COMMENT '포인트 변화 사유',
            `admin_id` VARCHAR(20) COMMENT '관리자 ID (관리자 작업시만)',
            `gift_from` VARCHAR(20) COMMENT '선물 보낸 회원 ID',
            `gift_to` VARCHAR(20) COMMENT '선물 받은 회원 ID',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
            INDEX `idx_mb_id` (`mb_id`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='슬롯머신 포인트 변화 기록'";
        
        sql_query($create_table_sql, false);
        
        $result_message = "테이블 수정 완료. 슬롯머신 모듈이 성공적으로 설치되었습니다.";
        
        // 설치 로그 기록
        error_log("[Slot Module] 설치 완료 - 관리자: {$member['mb_id']}, 시간: " . date('Y-m-d H:i:s'));
    } else {
        // 상세 에러는 로그에만 기록, 사용자에게는 일반 메시지 표시
        error_log("[Slot Module] 설치 실패 - 관리자: {$member['mb_id']}, 에러: " . mysqli_error($sql_conn));
        $error_message = "테이블 수정 중 오류가 발생했습니다. 이미 설치되었거나 데이터베이스 오류가 있을 수 있습니다.";
    }
}

// ===== 언인스톨 처리 =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'uninstall') {
    
    // 컬럼 삭제 (주의: 데이터 손실)
    $columns_to_drop = array('bar', 'bell', 'bellbonus', 'allseven', 'allitemh', 'alliteml', 'crossseven', 'rankpoint', 'box1', 'box2', 'box3', 'box4', 'box5', 'box6', 'box7', 'box8', 'box9', 'last_daily_charge');
    
    $all_success = true;
    foreach ($columns_to_drop as $column) {
        $sql = "ALTER TABLE {$g5['member_table']} DROP COLUMN IF EXISTS `{$column}`";
        if (!sql_query($sql, false)) {
            $all_success = false;
            error_log("[Slot Module] 언인스톨 실패 - 컬럼: {$column}, 에러: " . mysqli_error($sql_conn));
        }
    }
    
    // 포인트 기록 테이블 삭제
    $table_name = $g5['table_prefix'] . 'slot_point_log';
    sql_query("DROP TABLE IF EXISTS `{$table_name}`", false);
    
    if ($all_success) {
        $result_message = "슬롯머신 모듈이 성공적으로 제거되었습니다.";
        error_log("[Slot Module] 언인스톨 완료 - 관리자: {$member['mb_id']}, 시간: " . date('Y-m-d H:i:s'));
    } else {
        $error_message = "모듈 제거 중 일부 오류가 발생했습니다.";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>슬롯머신 모듈 설치</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
    .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    h1 { color: #333; }
    .message { padding: 10px; margin: 10px 0; border-radius: 3px; }
    .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .form-group { margin: 15px 0; }
    button { padding: 10px 20px; margin: 5px; cursor: pointer; border: none; border-radius: 3px; font-size: 14px; }
    .btn-install { background-color: #28a745; color: white; }
    .btn-install:hover { background-color: #218838; }
    .btn-uninstall { background-color: #dc3545; color: white; }
    .btn-uninstall:hover { background-color: #c82333; }
    .info { background-color: #e7f3ff; color: #004085; border: 1px solid #b3d9ff; padding: 10px; border-radius: 3px; margin: 10px 0; }
</style>
</head>
<body>

<div class="container">
    <h1>슬롯머신 모듈 설치 관리</h1>
    
    <?php if ($result_message): ?>
        <div class="message success">
            <strong>성공:</strong> <?php echo htmlspecialchars($result_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="message error">
            <strong>오류:</strong> <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <div class="info">
        <strong>주의:</strong> 이 페이지는 관리자만 접근할 수 있습니다. 모듈 설치 및 제거 시 데이터베이스가 수정됩니다.
    </div>
    
    <form method="POST" action="">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        
        <div class="form-group">
            <h3>모듈 설치</h3>
            <p>슬롯머신 모듈에 필요한 데이터베이스 테이블 컬럼을 추가합니다.</p>
            <button type="submit" name="action" value="install" class="btn-install">설치</button>
        </div>
        
        <hr>
        
        <div class="form-group">
            <h3>모듈 제거</h3>
            <p><strong style="color: red;">주의:</strong> 모듈을 제거하면 슬롯머신 관련 모든 데이터가 삭제됩니다.</p>
            <button type="submit" name="action" value="uninstall" class="btn-uninstall" onclick="return confirm('정말로 모듈을 제거하시겠습니까? 모든 데이터가 삭제됩니다.');">제거</button>
        </div>
    </form>
    
    <hr>
    
    <div class="info">
        <h4>필요한 컬럼:</h4>
        <ul>
            <li>bar: 바 보너스 포인트</li>
            <li>bell: 벨 개수</li>
            <li>bellbonus: 벨 보너스 포인트</li>
            <li>allseven: 올 세븐 횟수</li>
            <li>allitemh: 올 과일(상) 횟수</li>
            <li>alliteml: 올 과일(하) 횟수</li>
            <li>crossseven: 십자 세븐 횟수</li>
            <li>rankpoint: 랭킹 포인트</li>
            <li>box1~box9: 마지막 게임 결과</li>
            <li>last_daily_charge: 마지막 일일 충전 시간</li>
        </ul>
        <h4>포인트 통합:</h4>
        <ul>
            <li><strong>게임 포인트 = 그누보드 포인트 (mb_point)</strong></li>
            <li>회원의 게임 포인트는 그누보드 기본 포인트 시스템과 통합됩니다.</li>
            <li>포인트 변화 기록은 slot_point_log 테이블에 저장됩니다.</li>
        </ul>
    </div>
</div>

</body>
</html>
