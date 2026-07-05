<?php
include_once('./zbpath.php');

if (!$is_member) {
    alert('회원만 이용 가능합니다.');
}

// 그누보드5 회원 정보 가져오기
$datadb = $member;

// 출력할 데이터 안전 처리
$gamepoint_display = htmlspecialchars(number_format($datadb['gamepoint']));
?>

<html>
<head>
<meta charset="utf-8">
<title>인디슬롯 Beta v3.0</title>
<script>
<!--
//---------------- 인디 슬롯 베타 3.0 을 이용해 주셔서 감사합니다 --------------------
// 
//  문의 사항이나 기타 건의 사항은 http://www.enjoydivx.net 의 슬롯 QnA 게시판으로
//	말씀 해 주시거나 pslc@enjoydivx.net 으로 문의 주시면 되겠습니다.
//
//	이 메세지는 삭제 하시지 마시지 바랍니다. ^^ 
//------------------------------------------------------------------------------------


// 이미지 프리로딩
var pre = new Array();
var images = ["spin0.gif","spin1.gif","spin2.gif","spin3.gif","spin4.gif","spin5.gif","spin6.gif","spin7.gif","spin8.gif","spin9.gif","stop0.gif","stop1.gif","stop2.gif","stop3.gif","stop4.gif","stop5.gif","stop6.gif","stop7.gif","stop8.gif","stop9.gif"];
for(var i=0; i<images.length; i++) {
    pre[i] = new Image();
    pre[i].src = "images/" + images[i];
}

// 슬롯 스크립트
var bar=0;
var isPlay=false;	// 슬롯이 돌고 있는가
var isBet=true;		// 배팅 버튼을 누를 수 있는가

var holdable=false;	// 홀드가 가능한가
var hold1=false;	// 1번 홀드 플래그
var hold2=false;	// 2번 홀드 플래그
var hold3=false;	// 3번 홀드 플래그
var holds=0;		// 홀드 카운터 (3이면 클리어)
var betmoney=0;		// 배팅금액

var auto_on=false;	// 자동모드 초기화
var snd_on=true;	// 사운드 설정

function upmoney()	// 배팅 올리기
{
	if(isBet)
	{
		if(betmoney<300)
			betmoney=betmoney+20;
		document.getElementById('betmoney').value=betmoney;
	}
}

function clearmoney()	// 배팅 클리어
{
	if(isBet)
	{	
		betmoney=0;
		document.getElementById('betmoney').value=0;
	}
}

function setRecord(var1,var2,var3,var4)	// 기록표 갱신
{
	var num1=parseInt(var1);
	var num2=parseInt(var2);
	var num3=parseInt(var3);
	var num4=parseInt(var4);
	
	document.getElementById('record_all_seven').value=num1;
	document.getElementById('record_allitemh').value=num2;
	document.getElementById('record_alliteml').value=num3;
	document.getElementById('record_crossseven').value=num4;
	document.getElementById('record_total').value=(num1*5)+(num2*3)+num3+num4;
}

function setAuto()	// 자동모드 설정
{
	if(auto_on)
	{
		auto_on=false;
		document.getElementById('auto_img').src='images/auto_off.gif';
	}
	else
	{
		auto_on=true;
		document.getElementById('auto_img').src='images/auto_on.gif';
	}
}

function getAuto()	// 자동모드 변수 가져오기
{
	return auto_on;
}

function setSound()	// 사운드 설정
{
	if(snd_on)
	{
		snd_on=false;
		document.getElementById('snd_img').src='images/snd_off.gif';
	}
	else
	{
		snd_on=true;
		document.getElementById('snd_img').src='images/snd_on.gif';
	}
}

function getSound()	// 사운드 변수 가져오기
{
	return snd_on;
}

function setPlay()	// 새게임 가능
{
	isPlay=false;
}

function setBetTrue()	// 배팅 가능
{
	isBet=true;
}

function setBetFalse()	// 배팅 불가
{
	isBet=false;
}

function setHoldTrue()	// 홀드 가능
{
	holdable=true;
}

function setHoldFalse()	// 홀드 불가
{
	holdable=false;
}

function movePage()	// 단축키 설정
{
	var EventStatus = event.srcElement.tagName;
	if(EventStatus!='INPUT'&&EventStatus!='TEXTAREA')
	{
		if (event.keyCode=='44'||event.keyCode=='115')
			replay();
		if (event.keyCode=='49')
			holdchk(1);
		if (event.keyCode=='50')
			holdchk(2);
		if (event.keyCode=='51')
			holdchk(3);
	}
}

function replay()	// 새게임 시작
{
	if(isPlay==false)
	{
		isPlay=true;
		// URL 파라미터 안전 처리
		var params = "h1=" + (hold1 ? "true" : "false") + 
		             "&h2=" + (hold2 ? "true" : "false") + 
		             "&h3=" + (hold3 ? "true" : "false") + 
		             "&holds=" + holds + 
		             "&betmoney=" + betmoney;
		window.win.location.href="sub.php?" + params;
	}
	hold1=false;
	hold2=false;
	hold3=false;
	holds=0;
}

function noEvent()	// 뒤로가기, 새로고침 방지
{
    if (event.keyCode == 116) {
        event.keyCode= 2;
        return false;
    }
    else if(event.ctrlKey && (event.keyCode==78 || event.keyCode == 82))
    {
        return false;
    }
}

function holdClear()	// 홀드 해제
{
	hold1=false;
	hold2=false;
	hold3=false;
	holds=0;
	document.getElementById("hold1").style.backgroundColor="#FFDDDD";
	document.getElementById("hold2").style.backgroundColor="#FFDDDD";
	document.getElementById("hold3").style.backgroundColor="#FFDDDD";
}

function holdchk(arg)	// 홀드 검사
{
	if(!isPlay && holdable)
	{
		var num = parseInt(arg);
		
		switch(num)
		{
			case 1:
				if(!hold1)
				{
					document.getElementById("hold1").style.backgroundColor="#FFA1A1";
					hold1=true;
					holds++;
				}
				else
				{
					document.getElementById("hold1").style.backgroundColor="#FFF0F0";
					hold1=false;
					holds--;
				}
				break;
			case 2:
				if(!hold2)
				{
					document.getElementById("hold2").style.backgroundColor="#FFA1A1";
					hold2=true;
					holds++;
				}
				else
				{
					document.getElementById("hold2").style.backgroundColor="#FFF0F0";
					hold2=false;
					holds--;
				}
				break;
			case 3:
				if(!hold3)
				{
					document.getElementById("hold3").style.backgroundColor="#FFA1A1";
					hold3=true;
					holds++;
				}
				else
				{
					document.getElementById("hold3").style.backgroundColor="#FFF0F0";
					hold3=false;
					holds--;
				}
				break;
		}
		
		if(holds>2)
		{
			holdClear();
		}
	}
}

document.onkeypress= movePage;
document.onkeydown = noEvent;

function clear_screen(){
	clearInterval(screen_info);
}

function set_screen()
{
	document.getElementById('iscrolly').src='notice.php';
	screen_info = setInterval("scrollit()",10000);
}
//-->
</script>
<style>
    body { background-color: black; margin: 0; color: white; }
    .slot-container { width: 262px; background-image: url('images/slotmain.jpg'); background-repeat: no-repeat; margin-left: 220px; position: relative; height: 600px; }
    #layer3 { width: 210px; height: 481px; position: absolute; left: 6px; top: 16px; z-index: 1; visibility: visible; }
    .slot-table { border-collapse: collapse; width: 203px; background-color: #F1F1F1; margin: 0 auto; }
    .slot-box { width: 60px; height: 60px; border: 1px solid #ccc; background-color: #fff; text-align: center; }
    .slot-img { width: 50px; height: 50px; }
</style>
</head>

<body oncontextmenu="return false">

<div class="slot-container">
    <div id="layer3">
        <table class="slot-table">
            <tr align="center">
                <td colspan="2" height="30" style="border: 2px solid #FF7171; background-color: #FFF0F0;">
                    <font color="#FF4D4D" face="Comic Sans MS"><b>CHANCE</b></font>
                </td>
            </tr>
            <tr align="center">
                <td colspan="2" height="185" style="border: 1px solid #D8D8D8; background-color: #F9F9F9;">
                    <!-- 시상표 내용 (간략화) -->
                    <table width="185">
                        <tr><td><img src="images/1bar.gif" width="40"><img src="images/1bar.gif" width="40"><img src="images/1bar.gif" width="40"></td><td>x 3</td></tr>
                        <tr><td><img src="images/2bar.gif" width="40"><img src="images/2bar.gif" width="40"><img src="images/2bar.gif" width="40"></td><td>x 4</td></tr>
                        <tr><td><img src="images/3bar.gif" width="40"><img src="images/3bar.gif" width="40"><img src="images/3bar.gif" width="40"></td><td>x 5</td></tr>
                        <tr><td><img src="images/superbell.gif" width="40"><img src="images/superbell.gif" width="40"><img src="images/superbell.gif" width="40"></td><td>x 5</td></tr>
                    </table>
                </td>
            </tr>
            <tr align="center">
                <td id="slot1" class="slot-box"><img id="i1" src="images/stop0.gif" class="slot-img"></td>
                <td id="slot2" class="slot-box"><img id="i2" src="images/stop0.gif" class="slot-img"></td>
                <td id="slot3" class="slot-box"><img id="i3" src="images/stop0.gif" class="slot-img"></td>
            </tr>
            <tr align="center">
                <td id="slot4" class="slot-box"><img id="i4" src="images/stop0.gif" class="slot-img"></td>
                <td id="slot5" class="slot-box"><img id="i5" src="images/stop0.gif" class="slot-img"></td>
                <td id="slot6" class="slot-box"><img id="i6" src="images/stop0.gif" class="slot-img"></td>
            </tr>
            <tr align="center">
                <td id="slot7" class="slot-box"><img id="i7" src="images/stop0.gif" class="slot-img"></td>
                <td id="slot8" class="slot-box"><img id="i8" src="images/stop0.gif" class="slot-img"></td>
                <td id="slot9" class="slot-box"><img id="i9" src="images/stop0.gif" class="slot-img"></td>
            </tr>
        </table>
        
        <div style="margin-top: 10px; text-align: center;">
            <input type="text" id="bar" value="" readonly style="width: 180px; text-align: center; border: 1px solid #333; background: #000; color: #fff;">
            <br>
            Bet: <input type="text" id="betmoney" value="0" readonly style="width: 50px; text-align: center;">
            Point: <input type="text" id="moneytxt" value="<?php echo $gamepoint_display; ?>" readonly style="width: 80px; text-align: center;">
        </div>
    </div>

    <!-- 컨트롤 버튼들 -->
    <div style="position: absolute; left: 220px; top: 100px; width: 40px;">
        <img src="images/up.gif" onclick="upmoney()" style="cursor:pointer; margin-bottom: 5px;">
        <img src="images/clear.gif" onclick="clearmoney()" style="cursor:pointer; margin-bottom: 5px;">
        <img id="snd_img" src="images/snd_on.gif" onclick="setSound()" style="cursor:pointer; margin-bottom: 5px;">
        <img id="auto_img" src="images/auto_off.gif" onclick="setAuto()" style="cursor:pointer;">
    </div>

    <!-- 랭킹 및 포인트 관리 버튼 -->
    <div style="position: absolute; right: 20px; top: 20px; display: flex; gap: 10px;">
        <a href="rank.php" style="display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px; transition: all 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">🏆 랭킹</a>
        <a href="daily_charge.php" style="display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px; transition: all 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">💰 충전</a>
        <a href="gift.php" style="display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #fd7e14 0%, #ff6b6b 100%); color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px; transition: all 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">🎁 선물</a>
    </div>

    <div style="position: absolute; bottom: 20px; width: 262px; text-align: center;">
        <span id="hold1" style="padding: 5px; background: #FFDDDD; cursor: pointer;" onclick="holdchk(1)">HOLD1</span>
        <span id="hold2" style="padding: 5px; background: #FFDDDD; cursor: pointer;" onclick="holdchk(2)">HOLD2</span>
        <span id="hold3" style="padding: 5px; background: #FFDDDD; cursor: pointer;" onclick="holdchk(3)">HOLD3</span>
        <img src="images/spin.gif" onclick="replay()" style="cursor:pointer; width: 60px; vertical-align: middle;">
    </div>
    
    <iframe name="win" width="0" height="0" style="display:none;"></iframe>
</div>

</body>
</html>
