-- 영카트5 슬롯머신 모듈 제거 SQL
-- 실행 전 {{MEMBER_TABLE}} 을 영카트 회원 테이블명으로,
-- {{TABLE_PREFIX}} 를 영카트 테이블 접두사로 치환하세요.
-- 아래 명령은 슬롯 관련 회원 컬럼과 포인트 기록을 삭제하므로 백업 후 실행하세요.

ALTER TABLE `{{MEMBER_TABLE}}`
    DROP COLUMN IF EXISTS `bar`,
    DROP COLUMN IF EXISTS `bell`,
    DROP COLUMN IF EXISTS `bellbonus`,
    DROP COLUMN IF EXISTS `allseven`,
    DROP COLUMN IF EXISTS `allitemh`,
    DROP COLUMN IF EXISTS `alliteml`,
    DROP COLUMN IF EXISTS `crossseven`,
    DROP COLUMN IF EXISTS `box1`,
    DROP COLUMN IF EXISTS `box2`,
    DROP COLUMN IF EXISTS `box3`,
    DROP COLUMN IF EXISTS `box4`,
    DROP COLUMN IF EXISTS `box5`,
    DROP COLUMN IF EXISTS `box6`,
    DROP COLUMN IF EXISTS `box7`,
    DROP COLUMN IF EXISTS `box8`,
    DROP COLUMN IF EXISTS `box9`,
    DROP COLUMN IF EXISTS `last_daily_charge`;

DROP TABLE IF EXISTS `{{TABLE_PREFIX}}slot_point_log`;
