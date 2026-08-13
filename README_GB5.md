

## 최신 변경 기록

- `prize_board.php`: 시상 내역 조회 게시판 추가
- `smain.php`: 게임 화면에서 시상 게시판으로 이동하는 링크 추가
- `README_GB5.md`: 변경 내역을 한글로 정리

**한글 커밋 제목:** 시상 게시판 및 게임 화면 링크 추가
**변경일:** 2026년 8월 13일
# 그누보드5 슬롯머신 모듈 설치 가이드

## 개요

이 모듈은 기존의 제로보드 기반 슬롯머신 게임을 **그누보드5(GnuBoard 5)** 환경에 맞게 포팅한 것입니다.

## 주요 변경사항

### 1. 데이터베이스 호환성
- **기존**: `zetyx_member_table` → **변경**: `g5_member` 테이블 사용
- **기존**: `user_id` 필드 → **변경**: `mb_id` 필드 사용
- 모든 SQL 쿼리가 그누보드5의 `sql_query()` 함수를 사용하도록 변경

### 2. 파일 구조
```
slot_project/
├── zbpath.php          # 그누보드5 _common.php 포함
├── smain.php           # 메인 UI (HTML5 기반으로 현대화)
├── sub.php             # 게임 로직 및 결과 처리
├── normal.php          # 일반 모드 난수 생성
├── bar.php             # 바 보너스 모드
├── bell.php            # 벨 보너스 모드
├── gb5_install.php     # 테이블 설치 스크립트
├── images/             # 게임 이미지 (GIF 파일)
└── swf/                # Flash 사운드 효과 (선택사항)
```

### 3. 주요 수정 사항

#### zbpath.php
```php
<?php
if (!defined('_GNUBOARD_')) {
    include_once('./_common.php');
}
?>
```
- 그누보드5의 `_common.php`를 포함하여 전역 변수 및 함수 사용 가능

#### smain.php
- **Flash 제거**: 레거시 Flash 객체 대신 HTML5 기반 UI로 변경
- **회원 정보**: `$member` 변수로 그누보드5 회원 정보 자동 로드
- **현대화**: 인라인 스타일 대신 CSS 클래스 사용
- **반응형**: 모바일 환경 고려한 레이아웃

#### sub.php
- **DB 쿼리**: 모든 `mysql_query()` → `sql_query()` 변경
- **필드명**: `user_id` → `mb_id` 변경
- **테이블명**: `zetyx_member_table` → `{$g5['member_table']}` 변경
- **에러 처리**: 그누보드5의 에러 핸들링 방식 적용

#### normal.php, bar.php, bell.php
- PHP 문법 현대화 (짧은 태그 `<?` → `<?php`)
- 그누보드5 상수 정의 확인 추가
- DB 쿼리 함수 통일

## 설치 방법

### 1단계: 파일 업로드
그누보드5 설치 디렉토리 내 모듈 디렉토리에 파일을 업로드합니다.
```
/그누보드5설치경로/modules/slot/
```

### 2단계: 데이터베이스 테이블 수정
관리자 권한으로 `gb5_install.php`에 접속하여 테이블 컬럼을 추가합니다.

**필요한 컬럼:**
- `bar` (INT, 기본값: 0)
- `bell` (INT, 기본값: 5)
- `bellbonus` (INT, 기본값: 0)
- `allseven` (INT, 기본값: 0)
- `allitemh` (INT, 기본값: 0)
- `alliteml` (INT, 기본값: 0)
- `crossseven` (INT, 기본값: 0)
- `rankpoint` (INT, 기본값: 0)
- `box1~box9` (INT, 기본값: 0)
- `gamepoint` (INT, 기본값: 10000)

또는 직접 SQL 실행:
```sql
ALTER TABLE g5_member 
ADD `bar` INT(2) DEFAULT '0' NOT NULL,
ADD `bell` INT(2) DEFAULT '5' NOT NULL,
ADD `bellbonus` INT(2) DEFAULT '0' NOT NULL,
ADD `allseven` INT(4) DEFAULT '0' NOT NULL,
ADD `allitemh` INT(4) DEFAULT '0' NOT NULL,
ADD `alliteml` INT(4) DEFAULT '0' NOT NULL,
ADD `crossseven` INT(4) DEFAULT '0' NOT NULL,
ADD `rankpoint` INT(6) DEFAULT '0' NOT NULL,
ADD `box1` INT(1) NOT NULL,
ADD `box2` INT(1) NOT NULL,
ADD `box3` INT(1) NOT NULL,
ADD `box4` INT(1) NOT NULL,
ADD `box5` INT(1) NOT NULL,
ADD `box6` INT(1) NOT NULL,
ADD `box7` INT(1) NOT NULL,
ADD `box8` INT(1) NOT NULL,
ADD `box9` INT(1) NOT NULL,
ADD `gamepoint` INT DEFAULT '10000' NOT NULL;
```

### 3단계: 접근 권한 설정
그누보드5 관리자 페이지에서 모듈 접근 권한을 설정합니다.

## 사용 방법

### 회원 접근
로그인한 회원만 슬롯 게임에 접근할 수 있습니다.

**접근 URL:**
```
/modules/slot/smain.php
```

### 게임 방법
1. **배팅**: UP/CLEAR 버튼으로 배팅 금액 설정 (최대 300)
2. **홀드**: HOLD 버튼으로 릴 고정 (최대 3개)
3. **스핀**: SPIN 버튼으로 게임 시작
4. **자동 모드**: AUTO 버튼으로 자동 반복 게임
5. **사운드**: SOUND 버튼으로 음성 효과 제어

## 게임 규칙

### 시상 등급
| 조합 | 배수 | 설명 |
|------|------|------|
| 체리 3개 | 3배 | 기본 시상 |
| 오렌지 3개 | 4배 | - |
| 망고 3개 | 5배 | - |
| 원바 3개 | 2.5배 | 바 보너스 +3 |
| 세븐 3개 | 20배 | 특수 시상 |
| 올과일 | 15배 | 9칸 모두 과일 |
| 올잡바 | 60배 | 9칸 모두 바 |
| 올잡종 | 200배 | 9칸 모두 종 |

### 보너스 모드
- **바 모드**: 바 서비스 포인트 소비 시 활성화
- **벨 모드**: 벨 보너스 포인트 소비 시 활성화
- **일반 모드**: 게임 포인트로 배팅

## 문제 해결

### 1. 테이블 컬럼 오류
**증상**: "Unknown column" 에러
**해결**: `gb5_install.php` 실행하여 테이블 컬럼 추가

### 2. 회원 정보 로드 실패
**증상**: 게임 시작 불가
**해결**: `zbpath.php`에서 `_common.php` 경로 확인

### 3. 이미지 또는 SWF 파일 미로드
**증상**: 게임 화면이 비어있음
**해결**: `images/` 및 `swf/` 디렉토리 권한 확인 (755)

## 보안 주의사항

1. **관리자 권한 체크**: 모든 설치 스크립트는 관리자 권한 확인
2. **SQL 인젝션 방지**: 모든 쿼리에서 `sql_query()` 함수 사용
3. **회원 인증**: 로그인하지 않은 사용자 접근 차단
4. **파일 권한**: 이미지 및 스크립트 파일 권한 적절히 설정

## 추가 기능 개발

### 랭킹 시스템
`rankpoint` 필드를 활용하여 상위 플레이어 랭킹 표시 가능

### 통계 페이지
`allseven`, `allitemh`, `alliteml`, `crossseven` 필드로 개인 통계 표시

### 포인트 환전
`gamepoint`를 이용한 포인트 환전 시스템 구축 가능

## 라이선스 및 지원

이 모듈은 기존 인디슬롯 v3.0을 그누보드5에 맞게 포팅한 것입니다.
기술 지원이 필요하신 경우 그누보드 공식 커뮤니티를 참고하세요.

---

**마지막 수정**: 2026년 7월 4일
**버전**: GnuBoard5 호환 v1.0
