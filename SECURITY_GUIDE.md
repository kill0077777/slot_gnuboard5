# 슬롯머신 모듈 보안 설정 가이드

## 개요

이 문서는 그누보드5 슬롯머신 모듈의 보안 강화 사항 및 운영 환경에서의 보안 설정 방법을 설명합니다.

## 1. 적용된 보안 개선사항

### 1.1 SQL 인젝션 방지

**변경 사항:**
- 모든 사용자 입력값을 명시적으로 타입 캐스팅하여 SQL 인젝션 방지
- `$_GET` 파라미터에서 받은 값을 정수(`(int)`) 또는 불린(`bool`) 타입으로 변환
- 배팅 금액 범위 검증 (최소 20, 최대 300)
- `holds` 값 범위 검증 (0~3)

**예시 (sub.php):**
```php
// 입력값 안전 처리
$betmoney = isset($_GET['betmoney']) ? (int)$_GET['betmoney'] : 0;
$h1 = isset($_GET['h1']) && $_GET['h1'] === 'true' ? true : false;

// 범위 검증
if ($betmoney < 20 || $betmoney > 300) {
    $betmoney = 0;
}
```

### 1.2 XSS (Cross-Site Scripting) 방지

**변경 사항:**
- 모든 HTML 출력값에 `htmlspecialchars()` 함수 적용
- JavaScript 코드 내부의 문자열에 `addslashes()` 및 `htmlspecialchars()` 적용
- 데이터베이스에서 가져온 값도 출력 시 이스케이프 처리

**예시 (smain.php):**
```php
// 출력할 데이터 안전 처리
$mb_point_display = htmlspecialchars(number_format($datadb['mb_point']));
?>
<!-- HTML에 출력 -->
Point: <input type="text" id="moneytxt" value="<?php echo $mb_point_display; ?>" readonly>
```

**예시 (sub.php):**
```php
// JavaScript 내부 문자열 처리
$game_point_display = htmlspecialchars(number_format($member['mb_point'] + $total_points));
$gamemsg_display = addslashes(htmlspecialchars($gamemsg));

echo "top.document.getElementById('moneytxt').value = '" . $game_point_display . "';";
```

### 1.3 안전한 난수 생성

**변경 사항:**
- `MT_RAND()` 함수 대신 `random_int()` 함수 사용 (PHP 7.0+)
- 암호학적으로 안전한 난수 생성으로 게임 공정성 보장
- PHP 7.0 미만 환경을 위한 폴백 제공

**예시 (normal.php):**
```php
// 안전한 난수 생성 함수
function secure_random($min, $max) {
    if (function_exists('random_int')) {
        return random_int($min, $max);
    } else {
        return mt_rand($min, $max);
    }
}

// 사용
$jackpot = secure_random(3000, 20000);
```

### 1.4 에러 메시지 보안

**변경 사항:**
- 상세한 데이터베이스 에러 메시지를 사용자에게 직접 노출하지 않음
- 에러 정보는 서버 로그에만 기록
- 사용자에게는 일반적인 에러 메시지만 표시

**예시 (gb5_install.php):**
```php
if ($result) {
    $result_message = "테이블 수정 완료.";
} else {
    // 상세 에러는 로그에만 기록
    error_log("[Slot Module] 설치 실패 - 에러: " . mysqli_error($sql_conn));
    // 사용자에게는 일반 메시지 표시
    $error_message = "테이블 수정 중 오류가 발생했습니다.";
}
```

### 1.5 CSRF (Cross-Site Request Forgery) 방지

**변경 사항:**
- `gb5_install.php`에 CSRF 토큰 검증 추가
- 설치/제거 작업 시 토큰 확인

**예시 (gb5_install.php):**
```php
// CSRF 토큰 생성
if (!isset($_SESSION['slot_install_token'])) {
    $_SESSION['slot_install_token'] = bin2hex(random_bytes(32));
}

// CSRF 토큰 검증
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['slot_install_token']) {
        die('보안 토큰이 유효하지 않습니다.');
    }
}
```

## 2. 운영 환경 보안 설정

### 2.1 파일 권한 설정

```bash
# 스크립트 파일 권한 (644)
chmod 644 /path/to/slot/smain.php
chmod 644 /path/to/slot/sub.php
chmod 644 /path/to/slot/normal.php
chmod 644 /path/to/slot/bar.php
chmod 644 /path/to/slot/bell.php
chmod 644 /path/to/slot/zbpath.php

# 이미지 디렉토리 권한 (755)
chmod 755 /path/to/slot/images
chmod 644 /path/to/slot/images/*

# SWF 디렉토리 권한 (755)
chmod 755 /path/to/slot/swf
chmod 644 /path/to/slot/swf/*
```

### 2.2 PHP 설정 (php.ini)

```ini
# 에러 표시 비활성화 (프로덕션 환경)
display_errors = Off
log_errors = On
error_log = /var/log/php-errors.log

# 세션 보안
session.cookie_httponly = On
session.cookie_secure = On
session.use_only_cookies = On

# 파일 업로드 제한
upload_max_filesize = 2M
post_max_size = 2M
```

### 2.3 웹 서버 설정 (Apache)

```apache
# .htaccess 파일 (슬롯 모듈 디렉토리)
<FilesMatch "\.php$">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# 직접 접근 방지
<FilesMatch "\.sql$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# 캐시 비활성화 (보안 민감 페이지)
<FilesMatch "^(smain|sub)\.php$">
    Header set Cache-Control "no-cache, no-store, must-revalidate"
    Header set Pragma "no-cache"
    Header set Expires "0"
</FilesMatch>
```

### 2.4 웹 서버 설정 (Nginx)

```nginx
# nginx.conf 또는 site 설정
location /modules/slot/ {
    # 직접 접근 방지
    location ~ \.sql$ {
        deny all;
    }
    
    # PHP 파일 처리
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        
        # 캐시 비활성화
        add_header Cache-Control "no-cache, no-store, must-revalidate";
        add_header Pragma "no-cache";
        add_header Expires "0";
    }
}
```

## 3. 정기적인 보안 점검

### 3.1 로그 모니터링

```bash
# PHP 에러 로그 확인
tail -f /var/log/php-errors.log

# 웹 서버 접근 로그 확인 (비정상 패턴)
tail -f /var/log/apache2/access.log | grep "slot"
```

### 3.2 데이터베이스 백업

```bash
# 정기적인 데이터베이스 백업
mysqldump -u root -p gnuboard5 > /backup/gnuboard5_$(date +%Y%m%d).sql
```

### 3.3 보안 업데이트

- PHP 최신 버전 유지
- 그누보드5 보안 패치 적용
- 운영체제 보안 업데이트 설치

## 4. 추가 보안 권장사항

### 4.1 접근 제어

- 슬롯 모듈 접근을 특정 회원 그룹으로 제한
- 관리자 페이지(`gb5_install.php`)에 IP 화이트리스트 적용

### 4.2 감사 로깅

- 모든 게임 결과를 데이터베이스에 기록
- 비정상적인 포인트 변화 감지 시스템 구축

### 4.3 레이트 제한

- 과도한 게임 요청 방지 (초당 최대 요청 수 제한)
- 배팅 금액 급격한 변화 감지

### 4.4 정기 코드 리뷰

- 분기별 보안 감사 수행
- 새로운 취약점 발견 시 즉시 패치

## 5. 문제 해결

### 5.1 "보안 토큰이 유효하지 않습니다" 오류

**원인:** 세션 문제 또는 CSRF 토큰 만료
**해결:** 브라우저 캐시 삭제 후 다시 시도

### 5.2 "게임을 진행할 수 없습니다" 오류

**원인:** 포인트 부족 또는 배팅 금액 범위 초과
**해결:** 배팅 금액을 20~300 범위 내에서 설정

### 5.3 XSS 필터링으로 인한 특수 문자 표시 오류

**원인:** 특수 문자가 HTML 엔티티로 변환됨
**해결:** 정상 동작이며, 보안을 위해 필요한 처리

## 6. 참고 자료

- [OWASP Top 10 - 2021](https://owasp.org/www-project-top-ten/)
- [PHP 보안 가이드](https://www.php.net/manual/en/security.php)
- [그누보드5 보안 가이드](https://sir.kr/g5_developer)

---

**작성일:** 2026년 7월 4일
**버전:** 1.0
