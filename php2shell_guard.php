<?php
/**
 * PHP2Shell 방어적 런타임 점검.
 * 완전한 해결은 PHP 보안 패치와 워커 재시작이며, 이 파일은 추가 안전장치입니다.
 */

if (!defined('SLOT_PHP2SHELL_GUARD')) {
    define('SLOT_PHP2SHELL_GUARD', true);

    $phpVersionId = PHP_VERSION_ID;
    $fixedVersionId = array(
        80200 => 80235,
        80300 => 80336,
        80400 => 80427,
        80500 => 80512,
    );
    $branchId = (int) (floor($phpVersionId / 100) * 100);
    $isKnownVulnerable = isset($fixedVersionId[$branchId]) && $phpVersionId < $fixedVersionId[$branchId];
    $isUnsupportedBranch = $phpVersionId < 80200;

    // 배포판 백포트 패키지는 upstream 버전이 낮게 보일 수 있으므로
    // 운영자가 명시적으로 우회할 때만 애플리케이션 실행을 허용합니다.
    $allowUnpatched = getenv('SLOT_ALLOW_UNPATCHED_PHP') === '1';
    if (($isKnownVulnerable || $isUnsupportedBranch) && !$allowUnpatched) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('서비스를 시작할 수 없습니다. PHP2Shell 보안 수정 버전으로 PHP를 업데이트한 뒤 PHP-FPM/웹서버를 재시작하십시오.');
    }
}
