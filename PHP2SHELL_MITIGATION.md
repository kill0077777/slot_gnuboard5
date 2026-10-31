# PHP2Shell(CVE-2026-109618·CVE-2026-109619) 대응

## 핵심 결론

PHP2Shell은 슬롯머신 코드의 `exec()` 호출 때문에 발생하는 취약점이 아니라 PHP 코어의 메모리 처리 문제입니다. 애플리케이션 파일만 수정해서 완전히 해결할 수 없으며, PHP 실행 환경을 보안 수정 버전으로 업데이트하고 PHP-FPM/Apache 워커를 재시작해야 합니다.

PHP 공식 보안 공지 기준 최소 수정 버전은 다음과 같습니다.

- PHP 8.5: 8.5.12 이상
- PHP 8.4: 8.4.27 이상
- PHP 8.3: 8.3.36 이상
- PHP 8.2: 8.2.35 이상

PHP 8.1 이하 및 PHP 7.x는 공식 지원 대상이 아니므로 호스팅 업체의 백포트 패치 여부를 확인하거나 지원되는 PHP 버전으로 업그레이드해야 합니다. 배포판 패키지는 upstream 버전 문자열이 오래된 채 보안 패치만 백포트할 수 있으므로 패키지 보안 공지도 함께 확인하십시오.

## 이 모듈에 적용한 방어 조치

- `zbpath.php`에서 PHP2Shell 수정 버전 미만의 런타임을 기본 차단합니다.
- `session.upload_progress.enabled=0`을 애플리케이션 시작 단계에서도 방어적으로 적용합니다. 단, PHP 요청 파싱 이전 설정이 아니므로 서버 설정을 대체하지 않습니다.
- SQL·INI·설정 예시·문서 파일의 직접 노출을 막는 `.htaccess`를 추가했습니다.
- PHP-FPM 풀 설정 예시를 추가했습니다.
- 모듈 코드 전체에서 `exec`, `system`, `shell_exec`, `passthru`, `proc_open`, `popen`, `eval` 및 업로드 처리 사용이 없음을 점검했습니다.

## 서버에서 반드시 할 일

1. 웹 요청을 처리하는 실제 PHP-FPM/Apache/CGI 런타임 버전을 확인합니다. CLI의 `php -v`가 웹서버 PHP와 다를 수 있습니다.
2. PHP 보안 수정 버전 또는 배포판의 백포트 패키지로 업데이트합니다.
3. PHP-FPM 또는 Apache 워커를 재시작합니다. 기존 워커가 남아 있으면 이전 취약 런타임이 계속 처리할 수 있습니다.
4. `php2shell-fpm-pool.conf.example`의 `session.upload_progress.enabled = no`를 FPM 풀에 적용합니다.
5. 업로드가 필요하지 않은 사이트는 `post_max_size`를 8M 수준으로 제한하고, 업로드가 필요하면 허용 범위에 맞춰 낮게 유지합니다. `upload_max_filesize`는 `post_max_size`보다 작게 설정합니다.
6. 패치 전 임시 완화책만 적용한 상태로 운영하지 않습니다. PHP 공식 공지도 임시 완화는 완전한 해결이 아니라고 명시합니다.

## 적용 확인 예시

```bash
php -v
php --ini
sudo systemctl restart php8.3-fpm
sudo systemctl reload nginx
```

실제 웹 요청이 사용하는 PHP 버전은 임시 관리자 전용 점검 페이지에서 `PHP_VERSION`을 확인한 뒤 즉시 삭제하십시오. 공개 상태로 PHP 정보 페이지를 남기지 마십시오.

## 침해 흔적 점검

패치 전 인터넷에 노출된 시스템은 웹 접근 로그와 PHP-FPM 로그에서 비정상적으로 큰 multipart 요청, 반복적인 HTTP 500, 메모리 제한 오류, PHP 워커의 비정상 재시작을 확인하십시오. 의심되는 경우 자격 증명·세션·API 키를 교체하고, 서버 파일 변경 이력과 예약 작업도 함께 조사하십시오.

## 참고 자료

[1]: https://www.php.net/archive/2026.php "PHP 공식 2026 보안 공지"
[2]: https://www.php.net/supported-versions.php "PHP 공식 지원 버전"
[3]: https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w "CVE-2026-109618 PHP 보안 권고"
[4]: https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp "CVE-2026-109619 PHP 보안 권고"
