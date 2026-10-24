# 그누보드5·영카트5 슬롯머신 모듈

기존 제로보드 기반 슬롯머신을 그누보드5와 영카트5의 회원·포인트 구조에 맞춰 포팅한 모듈입니다. 두 플랫폼은 기본적으로 동일한 그누보드5 코어 구조를 사용하지만, 설치 실수를 방지하기 위해 설치 화면과 SQL 파일을 플랫폼별로 분리했습니다.

## 주요 기능

- 그누보드5·영카트5 회원 인증 및 `mb_id` 기반 회원 조회
- 기본 `mb_point` 통합 포인트 결제·당첨 처리
- 하루 1회 50,000포인트 무료 충전
- 회원 간 포인트 선물
- 전체 랭킹 및 시상 게시판
- 포인트 변동 이력 기록
- 관리자 포인트 충전·차감
- 서버 측 입력 검증, CSRF 토큰, 출력 이스케이프, 보안 난수 처리

## 설치 파일 구분

### 그누보드5

| 파일 | 용도 |
|---|---|
| `gnuboard5_install.php` | 그누보드5 전용 관리자 설치 화면 |
| `gnuboard5_install.sql` | 그누보드5 전용 설치 SQL |
| `gnuboard5_uninstall.sql` | 그누보드5 전용 제거 SQL |

### 영카트5

| 파일 | 용도 |
|---|---|
| `youngcart5_install.php` | 영카트5 전용 관리자 설치 화면 |
| `youngcart5_install.sql` | 영카트5 전용 설치 SQL |
| `youngcart5_uninstall.sql` | 영카트5 전용 제거 SQL |

`slot_install_common.php`는 두 설치 화면이 공유하는 관리자 권한·CSRF·SQL 실행 코드입니다. 기존 경로 호환을 위해 `gb5_install.php`, `youngcart_install.sql`, `install.sql`도 남겨 두었지만 신규 설치에는 플랫폼별 파일을 사용하십시오.

## 설치 방법

### 그누보드5 설치

1. 모듈 전체를 그누보드5 설치 경로에 업로드합니다.
2. 관리자 계정으로 `gnuboard5_install.php`에 접속합니다.
3. **모듈 설치**를 실행합니다.
4. 직접 SQL을 실행할 경우 `gnuboard5_install.sql`을 사용합니다.

```text
/그누보드설치경로/modules/slot/gnuboard5_install.php
```

### 영카트5 설치

1. 모듈 전체를 영카트5 설치 경로에 업로드합니다.
2. 관리자 계정으로 `youngcart5_install.php`에 접속합니다.
3. **모듈 설치**를 실행합니다.
4. 직접 SQL을 실행할 경우 `youngcart5_install.sql`을 사용합니다.

```text
/영카트설치경로/modules/slot/youngcart5_install.php
```

영카트5는 그누보드5 회원 테이블과 `mb_point` 필드를 사용하므로 게임 포인트용 `gamepoint` 컬럼을 별도로 생성하지 않습니다.

## 직접 SQL 실행

SQL 파일을 데이터베이스 관리 도구에서 직접 실행하는 경우 다음 토큰을 실제 설정으로 치환하십시오.

```text
{{MEMBER_TABLE}}  → g5_member
{{TABLE_PREFIX}}  → g5_
```

사이트에서 다른 접두사를 사용한다면 해당 값으로 바꿉니다. 설치 SQL은 회원 테이블에 다음 컬럼을 추가하고 포인트 이력 테이블을 생성합니다.

```text
bar, bell, bellbonus, allseven, allitemh, alliteml,
crossseven, rankpoint, box1~box9, last_daily_charge
```

포인트 이력 테이블 이름은 `${TABLE_PREFIX}slot_point_log`입니다.

## 제거 방법

설치한 플랫폼에 맞는 제거 SQL을 사용하십시오.

- 그누보드5: `gnuboard5_uninstall.sql`
- 영카트5: `youngcart5_uninstall.sql`

또는 해당 플랫폼의 설치 화면에서 **모듈 제거**를 실행할 수 있습니다. 제거 전 데이터베이스 백업을 권장하며, 제거하면 슬롯 관련 회원 컬럼과 포인트 기록 테이블이 삭제됩니다.

## 공통 파일

`smain.php`, `sub.php`, `normal.php`, `bar.php`, `bell.php`, `rank.php`, `prize_board.php`, `daily_charge.php`, `gift.php`, `admin_point.php`, `zbpath.php`는 두 플랫폼에서 공통으로 사용합니다.

`zbpath.php`의 `_common.php` 상대 경로가 실제 설치 위치와 맞는지 확인하십시오. 모듈을 별도 하위 디렉터리에 설치하면 해당 경로를 조정해야 합니다.

## 포인트 동작

- 배팅 시 회원의 `mb_point`에서 차감합니다.
- 당첨 시 회원의 `mb_point`에 지급합니다.
- 일일 무료 충전은 `last_daily_charge`를 기준으로 하루 한 번 처리합니다.
- 관리자 충전·차감과 선물 내역은 `slot_point_log`에 기록합니다.
- 테이블명은 `$g5['table_prefix'] . 'slot_point_log'` 기준으로 계산합니다.

## 보안 주의사항

- 설치 화면은 관리자만 접근할 수 있습니다.
- 설치·제거 POST 요청에는 세션 기반 CSRF 토큰이 필요합니다.
- SQL 스키마는 설치 화면과 분리되어 있습니다.
- 게임 입력값은 서버에서 타입과 범위를 재검증합니다.
- HTML 출력값은 `htmlspecialchars()`로 이스케이프합니다.
- 설치 완료 후 운영 서버에서 설치 파일 접근을 제한하거나 제거하십시오.

## 라이선스

이 모듈은 기존 인디슬롯 v3.0을 그누보드5·영카트5 구조에 맞춰 수정한 프로젝트입니다. 원본 라이선스와 설치 환경의 라이선스 조건을 함께 확인하십시오.
