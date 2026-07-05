<?php
if (!defined('_GNUBOARD_')) exit;

/******* 확률 및 그림 번호 *****
0.수박 1.체리 2.오렌지 3.망고 4.1바 5.세븐 6.s종 7.백종 8.2바 9.3바

오렌지90  망고80  s종60  수박60  1바50  백종50  체리40  2바30  3바25  세븐15
*********************************/

// 항목 초기화
$temp=0;
$num=array(8);
$gamemsg="";
$allmode=100;		// 올그림 여부

// ===== 안전한 난수 생성 함수 사용 =====
function secure_random($min, $max) {
    if (function_exists('random_int')) {
        return random_int($min, $max);
    } else {
        return mt_rand($min, $max);
    }
}

// 잭팟점수 생성
$jackpot = secure_random(3000, 18000);

// 십자세븐 확률
$cross = secure_random(2000, 2004);

// 올그림 확률
$allrand = secure_random(3000, 3002);

// 더블배당 찬스
$double = secure_random(0, 80);

$doublepoint=1;

if($double<1) $doublepoint=5;
else if($double<2) $doublepoint=3;
else if($double<4) $doublepoint=2;
else $doublepoint=1;

/* 기본 번호 생성 - 바 모드 (바 확률 높음) */
for($i=1;$i<10;$i++)
{
	$temp = secure_random(1, 500);

	if($temp>0&&$temp<=150)
	{
		$num [$i] =4;
	}
	else if($temp>150&&$temp<=350)
	{
		$num [$i] =10;
	}
	else if($temp>350&&$temp<=430)
	{
		$num [$i] =8;
	}
	else if($temp>430&&$temp<=500)
	{
		$num [$i] =9;
	}
}

$total_points=0;
$seven=0;
$bars=0;
$fruits=0;
$allbell=0;
$allbar=0;
$bells=0;

// 바 모드 진입 시 바 포인트 1 차감 (SQL 인젝션 방지)
sql_query("update {$g5['member_table']} set bar=bar-1 where mb_id='{$member['mb_id']}'");
?>
