# 영카트5 슬롯머신 모듈

기존 제로보드 기반 슬롯머신을 **그누보드5/영카트5** 회원·포인트 구조에 맞춰 포팅한 모듈입니다. 영카트5는 그누보드5의 회원 테이블과 포인트 필드를 사용하므로, 게임 포인트를 별도 `gamepoint` 컬럼으로 만들지 않고 기본 `mb_point`에 통합합니다.

## 주요 기능

- 영카트5 회원 인증 및 `mb_id` 기반 회원 조회
- `mb_point` 통합 포인트 결제·당첨 처리
- 하루 1회 50,000포인트 무료 충전
- 회원 간 포인트 선물
- 전체 랭킹 및 시상 게시판
- 포인트 변동 이력 기록
- 관리자 포인트 충전·차감
- 서버 측 입력 검증, CSRF 토큰, 출력 이스케이프, `random_int()` 기반 난수 처리

## 파일 구성

| 파일 | 설명 |
|---|---|
| `zbpath.php` | 영카트/그누보드 `_common.php` 로더 및 모듈 상수 |
| `smain.php` | 슬롯머신 화면 |
| `sub.php` | 게임 결과 계산 및 포인트 반영 |
| `normal.php`, `bar.php`, `bell.php` | 게임 모드별 결과 생성 |
| `rank.php` | 전체 랭킹 |
| `prize_board.php` | 시상·명예의 전당 |
| `daily_charge.php` | 일일 무료 충전 |
| `gift.php` | 회원 간 포인트 선물 |
| `admin_point.php` | 관리자 포인트 관리 |
| `gb5_install.php` | 관리자용 SQL 실행 화면만 담당 |
| `youngcart_install.sql` | 영카트5 설치 스키마 |
| `youngcart_uninstall.sql` | 영카트5 제거 스키마 |
| `install.sql` | 설치 SQL 호환 파일 |

## 설치 방법

### 1. 업로드

영카트5 설치 경로 아래 모듈 디렉터리에 파일을 업로드합니다.

```text
/영카트설치경로/modules/slot/
```

`zbpath.php`의 `_common.php` 상대 경로가 실제 디렉터리 구조와 맞는지 확인합니다. 모듈을 별도 하위 디렉터리에 설치하면 `include_once()` 경로를 그 위치에 맞게 조정해야 합니다.

### 2. 데이터베이스 설치

방법은 두 가지입니다.

1. 관리자 계정으로 `gb5_install.php`에 접속해 **모듈 설치**를 실행합니다. 이 화면은 `$g5['member_table']`과 `$g5['table_prefix']`를 자동으로 사용합니다.
2. SQL 관리 도구에서 `youngcart_install.sql`을 실행합니다. 이 경우 파일의 `{{MEMBER_TABLE}}`을 예를 들어 `g5_member`로, `{{TABLE_PREFIX}}`를 `g5_`로 치환한 뒤 실행합니다.

설치 SQL은 다음 회원 컬럼을 추가합니다.

```text
bar, bell, bellbonus, allseven, allitemh, alliteml,
crossseven, rankpoint, box1~box9, last_daily_charge
```

또한 `${table_prefix}slot_point_log` 포인트 이력 테이블을 생성합니다. 기존 영카트의 `mb_point` 컬럼은 그대로 사용하며, 별도 `gamepoint` 컬럼은 만들지 않습니다.

### 3. 제거

제거 전 데이터베이스 백업을 권장합니다. 관리자 화면의 **모듈 제거**를 사용하거나 `youngcart_uninstall.sql`의 토큰을 치환해 실행합니다. 제거 SQL은 슬롯 관련 회원 컬럼과 포인트 기록 테이블을 삭제합니다.

## 접근 주소 예시

```text
/modules/slot/smain.php
/modules/slot/rank.php
/modules/slot/gift.php
/modules/slot/daily_charge.php
/modules/slot/prize_board.php
```

실제 URL은 영카트 설치 경로와 모듈 디렉터리에 따라 달라집니다.

## 포인트 동작

- 배팅 시 `g5_member.mb_point`에서 차감합니다.
- 당첨 시 `g5_member.mb_point`에 지급합니다.
- 일일 무료 충전은 회원별 `last_daily_charge`를 기준으로 하루 한 번만 처리합니다.
- 관리자 충전·차감 및 선물 내역은 `slot_point_log`에 기록합니다.
- 포인트 기록 테이블명은 하드코딩하지 않고 `$g5['table_prefix'] . 'slot_point_log'`로 계산합니다.

## 영카트 호환 주의사항

- 영카트5/그누보드5의 `$g5`, `$member`, `$is_member`, `$is_admin`, `sql_query()`가 로드되는 환경에서 실행해야 합니다.
- 회원 테이블 접두사를 `g5_`로 가정하지 않습니다. 설치 SQL을 직접 실행할 때만 토큰을 실제 값으로 바꿉니다.
- 영카트 기본 포인트 정책과 충돌할 수 있으므로, 운영 전 포인트 한도·환불·선물 정책을 확인하십시오.
- `youngcart_uninstall.sql`은 데이터 손실을 일으킬 수 있으므로 운영 DB에서 신중히 실행하십시오.

## 보안 점검 항목

- 설치 화면은 관리자만 접근할 수 있습니다.
- 설치·제거 POST에는 세션 기반 CSRF 토큰이 필요합니다.
- SQL 스키마는 PHP 화면과 분리되어 변경 이력을 관리할 수 있습니다.
- 게임 입력값은 서버에서 정수·범위를 재검증합니다.
- HTML 출력값은 `htmlspecialchars()`로 이스케이프합니다.
- 운영 서버에서는 설치가 끝난 뒤 `gb5_install.php` 접근을 제한하거나 제거하는 것을 권장합니다.

## 라이선스

이 모듈은 기존 인디슬롯 v3.0을 그누보드5/영카트5 구조에 맞춰 수정한 프로젝트입니다. 원본 라이선스와 영카트 설치 환경의 라이선스 조건을 함께 확인한 뒤 배포하십시오.
