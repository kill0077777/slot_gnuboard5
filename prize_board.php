<?php
include_once('./zbpath.php');

// ===== 페이지 설정 =====
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // 페이지당 표시 개수

if ($page < 1) $page = 1;

// ===== 시상 로그 테이블 존재 여부 확인 및 생성 =====
$log_table = $g5['table_prefix'] . 'slot_point_log';

// ===== 전체 시상 기록 수 조회 (게임 승리 및 보상 내역) =====
$sql_cnt = "SELECT COUNT(*) as cnt FROM `{$log_table}` WHERE point_change > 0";
$result_cnt = sql_fetch($sql_cnt);
$total_count = $result_cnt ? $result_cnt['cnt'] : 0;
$total_pages = ceil($total_count / $limit);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

// ===== 시상 내역 데이터 조회 =====
$offset = ($page - 1) * $limit;
$sql = "SELECT l.id, l.mb_id, l.point_change, l.reason, l.reg_date, m.mb_nick, m.mb_name
        FROM `{$log_table}` l
        LEFT JOIN {$g5['member_table']} m ON l.mb_id = m.mb_id
        WHERE l.point_change > 0
        ORDER BY l.id DESC
        LIMIT {$offset}, {$limit}";

$result = sql_query($sql);
$prize_data = array();
while ($row = sql_fetch_array($result)) {
    $prize_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>슬롯머신 시상 게시판</title>
    <style>
        body {
            background-color: #0f172a;
            color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }
        h1 {
            text-align: center;
            color: #fbbf24;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .nav-btns {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .btn {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #0f172a;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.2s;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        th {
            background-color: rgba(15, 23, 42, 0.8);
            color: #fbbf24;
            font-weight: 600;
        }
        tr:hover {
            background-color: rgba(255, 255, 255, 0.03);
        }
        .point-win {
            color: #34d399;
            font-weight: bold;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        .pagination a, .pagination span {
            padding: 6, 12px;
            padding: 6px 12px;
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #f1f5f9;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .pagination a:hover {
            background: #334155;
            border-color: #fbbf24;
        }
        .pagination .current {
            background: #fbbf24;
            color: #0f172a;
            font-weight: bold;
            border-color: #fbbf24;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-btns">
            <a href="rank.php" class="btn">🏆 랭킹 게시판으로 이동</a>
            <a href="smain.php" class="btn">🎮 게임 화면으로</a>
        </div>
        
        <h1>🎖️ 슬롯머신 명예의 전당 & 시상 게시판</h1>
        
        <?php if (empty($prize_data)) { ?>
            <div class="no-data">등록된 시상 내역이 없습니다. 슬롯머신 게임을 플레이하여 당첨 기록을 남겨보세요!</div>
        <?php } else { ?>
            <table>
                <thead>
                    <tr>
                        <th>번호</th>
                        <th>수상자 (닉네임)</th>
                        <th>시상 내용 / 당첨 내역</th>
                        <th>획득 포인트</th>
                        <th>시상 일시</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = $total_count - $offset;
                    foreach ($prize_data as $row) { 
                        $display_name = $row['mb_nick'] ? $row['mb_nick'] : $row['mb_id'];
                        // 보안 이스케이프 처리
                        $safe_name = htmlspecialchars($display_name);
                        $safe_reason = htmlspecialchars($row['reason']);
                        $safe_date = date('Y-m-d H:i:s', $row['reg_date']);
                        $safe_point = number_format($row['point_change']);
                    ?>
                    <tr>
                        <td><?php echo $no--; ?></td>
                        <td><strong><?php echo $safe_name; ?></strong> <span style="font-size: 11px; color: #94a3b8;">(<?php echo htmlspecialchars($row['mb_id']); ?>)</span></td>
                        <td style="text-align: left; padding-left: 20px;"><?php echo $safe_reason; ?></td>
                        <td class="point-win">+<?php echo $safe_point; ?> P</td>
                        <td style="color: #94a3b8; font-size: 13px;"><?php echo $safe_date; ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- 페이지네이션 -->
            <div class="pagination">
                <?php
                $block_limit = 10;
                $start_page = ((floor(($page - 1) / $block_limit)) * $block_limit) + 1;
                $end_page = $start_page + $block_limit - 1;
                if ($end_page > $total_pages) {
                    $end_page = $total_pages;
                }

                if ($page > 1) {
                    echo '<a href="prize_board.php?page=1">처음</a>';
                }

                if ($start_page > 1) {
                    echo '<a href="prize_board.php?page=' . ($start_page - 1) . '">이전</a>';
                }

                for ($i = $start_page; $i <= $end_page; $i++) {
                    if ($i == $page) {
                        echo '<span class="current">' . $i . '</span>';
                    } else {
                        echo '<a href="prize_board.php?page=' . $i . '">' . $i . '</a>';
                    }
                }

                if ($end_page < $total_pages) {
                    echo '<a href="prize_board.php?page=' . ($end_page + 1) . '">다음</a>';
                }

                if ($page < $total_pages) {
                    echo '<a href="prize_board.php?page=' . $total_pages . '">끝</a>';
                }
                ?>
            </div>
        <?php } ?>
    </div>
</body>
</html>
