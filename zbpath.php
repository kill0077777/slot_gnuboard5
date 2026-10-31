<?php
/**
 * 슬롯머신 모듈 초기화 파일
 * 그누보드5의 _common.php를 포함하여 필요한 전역 변수 및 함수 로드
 */

// PHP2Shell 취약 런타임의 애플리케이션 진입을 방어적으로 차단합니다.
include_once(__DIR__ . '/php2shell_guard.php');

// 그누보드5 상수 정의 확인
if (!defined('_GNUBOARD_')) {
    // 그누보드5의 _common.php 포함
    include_once(__DIR__ . '/_common.php');
}

// 추가 보안 설정
if (!defined('_SLOT_SECURITY_')) {
    define('_SLOT_SECURITY_', true);
    
    // 슬롯 모듈 기본 설정
    define('SLOT_MIN_BET', 20);           // 최소 배팅 금액
    define('SLOT_MAX_BET', 300);          // 최대 배팅 금액
    define('SLOT_MAX_HOLDS', 3);          // 최대 홀드 개수
    define('SLOT_INITIAL_POINTS', 10000); // 초기 게임 포인트
}

// 서버 설정이 우선이며, 이 설정은 애플리케이션 수준의 추가 방어입니다.
// PHP 요청 파싱 이전 설정을 대체하지 않으므로 PHP-FPM 풀 설정도 적용해야 합니다.
if (function_exists('ini_set')) {
    @ini_set('session.upload_progress.enabled', '0');
}
?>
