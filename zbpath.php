<?php
/**
 * 슬롯머신 모듈 초기화 파일
 * 그누보드5의 _common.php를 포함하여 필요한 전역 변수 및 함수 로드
 */

// 그누보드5 상수 정의 확인
if (!defined('_GNUBOARD_')) {
    // 그누보드5의 _common.php 포함
    include_once('./_common.php');
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
?>
