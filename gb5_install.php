<?php
include_once('./zbpath.php');

if (!$is_admin) {
    die('관리자만 실행 가능합니다.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['slot_install_token'])) {
    $_SESSION['slot_install_token'] = bin2hex(random_bytes(32));
}

$token = $_SESSION['slot_install_token'];
$result_message = '';
$error_message = '';

/**
 * SQL 파일의 치환 토큰을 영카트 설정값으로 바꾸고 문장별로 실행합니다.
 * DDL은 SQL 파일에만 두어 설치 화면과 스키마를 분리합니다.
 */
function slot_run_sql_file($filename, $member_table, $table_prefix) {
    $path = __DIR__ . '/' . $filename;
    if (!is_readable($path)) {
        throw new RuntimeException('SQL 파일을 찾을 수 없습니다: ' . $filename);
    }

    $sql = file_get_contents($path);
    $sql = str_replace(
        array('{{MEMBER_TABLE}}', '{{TABLE_PREFIX}}'),
        array($member_table, $table_prefix),
        $sql
    );

    // SQL 주석은 문장 분리 전에 제거해야 첫 문장이 주석과 함께 버려지지 않습니다.
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        sql_query($statement);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_token = isset($_POST['token']) ? (string)$_POST['token'] : '';
    if (!hash_equals($token, $posted_token)) {
        http_response_code(403);
        die('보안 토큰이 유효하지 않습니다.');
    }

    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    $sql_file = $action === 'install' ? 'youngcart_install.sql' : ($action === 'uninstall' ? 'youngcart_uninstall.sql' : '');

    if ($sql_file === '') {
        $error_message = '올바르지 않은 작업입니다.';
    } else {
        try {
            slot_run_sql_file($sql_file, $g5['member_table'], $g5['table_prefix']);
            $result_message = $action === 'install'
                ? '영카트5 슬롯머신 모듈 설치가 완료되었습니다.'
                : '영카트5 슬롯머신 모듈 제거가 완료되었습니다.';
            error_log('[Slot Module] ' . $result_message . ' - 관리자: ' . $member['mb_id']);
        } catch (Throwable $e) {
            error_log('[Slot Module] SQL 실행 실패 - ' . $e->getMessage());
            $error_message = 'SQL 실행 중 오류가 발생했습니다. 서버 오류 로그를 확인하세요.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>영카트5 슬롯머신 설치</title>
<style>
body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5}.container{max-width:680px;margin:0 auto;background:#fff;padding:24px;border-radius:8px;box-shadow:0 2px 8px #0001}h1{color:#222}.message{padding:12px;margin:12px 0;border-radius:4px}.success{background:#d4edda;color:#155724}.error{background:#f8d7da;color:#721c24}.info{background:#e7f3ff;color:#004085;border:1px solid #b3d9ff;padding:12px;border-radius:4px;margin:16px 0}button{padding:10px 20px;margin:5px 5px 5px 0;border:0;border-radius:4px;cursor:pointer;font-size:14px}.install{background:#198754;color:#fff}.uninstall{background:#dc3545;color:#fff}</style>
</head>
<body>
<div class="container">
    <h1>영카트5 슬롯머신 설치 관리</h1>
    <?php if ($result_message): ?><div class="message success"><?php echo htmlspecialchars($result_message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="message error"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <div class="info">
        <strong>현재 설정</strong><br>
        회원 테이블: <code><?php echo htmlspecialchars($g5['member_table'], ENT_QUOTES, 'UTF-8'); ?></code><br>
        테이블 접두사: <code><?php echo htmlspecialchars($g5['table_prefix'], ENT_QUOTES, 'UTF-8'); ?></code><br>
        설치/제거 스키마는 <code>youngcart_install.sql</code>, <code>youngcart_uninstall.sql</code>에서 관리합니다.
    </div>
    <form method="post">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" name="action" value="install" class="install">모듈 설치</button>
        <button type="submit" name="action" value="uninstall" class="uninstall" onclick="return confirm('슬롯 관련 컬럼과 포인트 기록이 삭제됩니다. 계속하시겠습니까?');">모듈 제거</button>
    </form>
    <div class="info">영카트는 그누보드5의 회원·포인트 구조를 사용하므로 별도 gamepoint 컬럼을 만들지 않고 기본 <code>mb_point</code>를 사용합니다.</div>
</div>
</body>
</html>
