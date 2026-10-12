<?php
include_once('./zbpath.php');

// ===== 페이지 설정 =====
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$sort_type = isset($_GET['sort']) ? $_GET['sort'] : 'rankpoint';
$limit = 20; // 페이지당 표시 개수

// 정렬 방식 검증
$allowed_sorts = array('rankpoint', 'point', 'allseven', 'allitemh');
if (!in_array($sort_type, $allowed_sorts)) {
    $sort_type = 'rankpoint';
}

// 정렬 SQL 구성
$order_by = '';
switch($sort_type) {
    case 'rankpoint':
        $order_by = 'rankpoint DESC, mb_point DESC';
        $sort_title = '랭킹 포인트';
        break;
    case 'point':
        $order_by = 'mb_point DESC, rankpoint DESC';
        $sort_title = '게임 포인트';
        break;
    case 'allseven':
        $order_by = 'allseven DESC, rankpoint DESC';
        $sort_title = '올 세븐 횟수';
        break;
    case 'allitemh':
        $order_by = 'allitemh DESC, rankpoint DESC';
        $sort_title = '올 과일(상) 횟수';
        break;
    default:
        $order_by = 'rankpoint DESC, mb_point DESC';
        $sort_title = '랭킹 포인트';
}

// ===== 페이지 범위 검증 =====
if ($page < 1) $page = 1;

// ===== 전체 회원 수 조회 =====
$sql = "SELECT COUNT(*) as cnt FROM {$g5['member_table']} WHERE rankpoint > 0 OR mb_point > 0";
$result = sql_fetch($sql);
$total_count = $result['cnt'];
$total_pages = ceil($total_count / $limit);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

// ===== 랭킹 데이터 조회 =====
$offset = ($page - 1) * $limit;
$sql = "SELECT 
            mb_id,
            mb_name,
            mb_nick,
            mb_point AS point,
            rankpoint,
            allseven,
            allitemh,
            alliteml,
            crossseven,
            bar,
            bell,
            bellbonus
         FROM {$g5['member_table']}
         WHERE rankpoint > 0 OR mb_point > 0
         ORDER BY {$order_by}
         LIMIT {$offset}, {$limit}";

$result = sql_query($sql);
$ranking_data = array();
$rank = $offset + 1;

while ($row = sql_fetch_array($result)) {
    $row['rank'] = $rank;
    $ranking_data[] = $row;
    $rank++;
}

// ===== 현재 사용자의 랭킹 정보 조회 =====
$my_rank = 0;
$my_rank_info = null;

if ($is_member) {
    $sql = "SELECT 
                @rank := @rank + 1 as rank,
                mb_id,
                mb_nick,
                mb_point AS point,
                rankpoint
            FROM (SELECT @rank := 0) as init,
                 {$g5['member_table']}
            WHERE rankpoint > 0 OR mb_point > 0
            ORDER BY {$order_by}";
    
    $result = sql_query($sql);
    while ($row = sql_fetch_array($result)) {
        if ($row['mb_id'] === $member['mb_id']) {
            $my_rank = $row['rank'];
            $my_rank_info = $row;
            break;
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>슬롯머신 전체 랭킹</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }
    
    .header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        text-align: center;
    }
    
    .header h1 {
        font-size: 32px;
        margin-bottom: 10px;
    }
    
    .header p {
        font-size: 14px;
        opacity: 0.9;
    }
    
    .content {
        padding: 30px;
    }
    
    .controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .sort-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .sort-btn {
        padding: 8px 16px;
        border: 2px solid #667eea;
        background: white;
        color: #667eea;
        border-radius: 5px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .sort-btn:hover {
        background: #f0f0f0;
    }
    
    .sort-btn.active {
        background: #667eea;
        color: white;
    }
    
    .my-rank-info {
        background: #f8f9ff;
        border-left: 4px solid #667eea;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .my-rank-info h3 {
        color: #667eea;
        margin-bottom: 10px;
        font-size: 14px;
    }
    
    .my-rank-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .stat-item {
        background: white;
        padding: 10px;
        border-radius: 5px;
        text-align: center;
        border: 1px solid #e0e0e0;
    }
    
    .stat-label {
        font-size: 12px;
        color: #999;
        margin-bottom: 5px;
    }
    
    .stat-value {
        font-size: 18px;
        font-weight: bold;
        color: #667eea;
    }
    
    .ranking-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }
    
    .ranking-table thead {
        background: #f5f5f5;
        border-bottom: 2px solid #667eea;
    }
    
    .ranking-table th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #333;
        font-size: 13px;
    }
    
    .ranking-table td {
        padding: 15px;
        border-bottom: 1px solid #e0e0e0;
        font-size: 13px;
    }
    
    .ranking-table tbody tr:hover {
        background: #f9f9f9;
    }
    
    .rank-badge {
        display: inline-block;
        width: 40px;
        height: 40px;
        line-height: 40px;
        text-align: center;
        border-radius: 50%;
        font-weight: bold;
        color: white;
        font-size: 14px;
    }
    
    .rank-1 { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); }
    .rank-2 { background: linear-gradient(135deg, #C0C0C0 0%, #A9A9A9 100%); }
    .rank-3 { background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%); }
    .rank-other { background: #667eea; }
    
    .user-name {
        font-weight: 600;
        color: #333;
    }
    
    .stat-highlight {
        background: #fff3cd;
        padding: 2px 6px;
        border-radius: 3px;
        font-weight: 600;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 5px;
        margin-top: 30px;
    }
    
    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        text-decoration: none;
        color: #667eea;
        transition: all 0.3s ease;
    }
    
    .pagination a:hover {
        background: #667eea;
        color: white;
    }
    
    .pagination .current {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
    
    .pagination .disabled {
        color: #ccc;
        cursor: not-allowed;
    }
    
    .no-data {
        text-align: center;
        padding: 40px;
        color: #999;
    }
    
    .no-data p {
        font-size: 16px;
        margin-bottom: 10px;
    }
    
    .back-button {
        display: inline-block;
        padding: 10px 20px;
        background: #667eea;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        margin-top: 20px;
        transition: all 0.3s ease;
    }
    
    .back-button:hover {
        background: #764ba2;
    }
    
    @media (max-width: 768px) {
        .controls {
            flex-direction: column;
            align-items: stretch;
        }
        
        .sort-buttons {
            justify-content: center;
        }
        
        .ranking-table {
            font-size: 12px;
        }
        
        .ranking-table th,
        .ranking-table td {
            padding: 10px 5px;
        }
        
        .rank-badge {
            width: 35px;
            height: 35px;
            line-height: 35px;
            font-size: 12px;
        }
        
        .header h1 {
            font-size: 24px;
        }
    }
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🏆 슬롯머신 전체 랭킹</h1>
        <p>현재 정렬: <strong><?php echo htmlspecialchars($sort_title); ?></strong></p>
    </div>
    
    <div class="content">
        <!-- 현재 사용자 랭킹 정보 -->
        <?php if ($is_member && $my_rank_info): ?>
            <div class="my-rank-info">
                <h3>📊 내 랭킹 정보</h3>
                <div class="my-rank-stats">
                    <div class="stat-item">
                        <div class="stat-label">현재 순위</div>
                        <div class="stat-value">#<?php echo number_format($my_rank); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">랭킹 포인트</div>
                        <div class="stat-value"><?php echo number_format($my_rank_info['rankpoint']); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">게임 포인트</div>
                        <div class="stat-value"><?php echo number_format($my_rank_info['point']); ?></div>
                    </div>
                </div>
            </div>
        <?php elseif ($is_member): ?>
            <div class="my-rank-info">
                <h3>📊 내 랭킹 정보</h3>
                <p style="color: #999;">아직 게임 기록이 없습니다. 게임을 시작하면 랭킹에 등록됩니다.</p>
            </div>
        <?php endif; ?>
        
        <!-- 정렬 버튼 -->
        <div class="controls">
            <div class="sort-buttons">
                <a href="?page=1&sort=rankpoint" class="sort-btn <?php echo $sort_type === 'rankpoint' ? 'active' : ''; ?>">
                    랭킹 포인트 순
                </a>
                <a href="?page=1&sort=point" class="sort-btn <?php echo $sort_type === 'point' ? 'active' : ''; ?>">
                    게임 포인트 순
                </a>
                <a href="?page=1&sort=allseven" class="sort-btn <?php echo $sort_type === 'allseven' ? 'active' : ''; ?>">
                    올 세븐 순
                </a>
                <a href="?page=1&sort=allitemh" class="sort-btn <?php echo $sort_type === 'allitemh' ? 'active' : ''; ?>">
                    올 과일(상) 순
                </a>
            </div>
            <div style="color: #999; font-size: 13px;">
                총 <strong><?php echo number_format($total_count); ?></strong>명
            </div>
        </div>
        
        <!-- 랭킹 테이블 -->
        <?php if (count($ranking_data) > 0): ?>
            <table class="ranking-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">순위</th>
                        <th style="width: 150px;">닉네임</th>
                        <th style="width: 120px;">랭킹 포인트</th>
                        <th style="width: 120px;">게임 포인트</th>
                        <th style="width: 100px;">올 세븐</th>
                        <th style="width: 100px;">올 과일(상)</th>
                        <th style="width: 100px;">올 과일(하)</th>
                        <th style="width: 100px;">십자 세븐</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranking_data as $item): ?>
                        <tr>
                            <td>
                                <?php 
                                    $rank_class = 'rank-other';
                                    if ($item['rank'] == 1) $rank_class = 'rank-1';
                                    elseif ($item['rank'] == 2) $rank_class = 'rank-2';
                                    elseif ($item['rank'] == 3) $rank_class = 'rank-3';
                                ?>
                                <span class="rank-badge <?php echo $rank_class; ?>">
                                    <?php echo $item['rank']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="user-name">
                                    <?php echo htmlspecialchars($item['mb_nick']); ?>
                                    <?php if ($is_member && $item['mb_id'] === $member['mb_id']): ?>
                                        <span style="color: #667eea; font-size: 11px; margin-left: 5px;">(나)</span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <span class="stat-highlight">
                                    <?php echo number_format($item['rankpoint']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($item['point']); ?></td>
                            <td><?php echo number_format($item['allseven']); ?></td>
                            <td><?php echo number_format($item['allitemh']); ?></td>
                            <td><?php echo number_format($item['alliteml']); ?></td>
                            <td><?php echo number_format($item['crossseven']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <p>아직 랭킹 데이터가 없습니다.</p>
                <p style="font-size: 13px;">게임을 시작하면 랭킹에 등록됩니다.</p>
            </div>
        <?php endif; ?>
        
        <!-- 페이지네이션 -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1&sort=<?php echo htmlspecialchars($sort_type); ?>">« 처음</a>
                    <a href="?page=<?php echo $page - 1; ?>&sort=<?php echo htmlspecialchars($sort_type); ?>">‹ 이전</a>
                <?php else: ?>
                    <span class="disabled">« 처음</span>
                    <span class="disabled">‹ 이전</span>
                <?php endif; ?>
                
                <?php 
                    $start_page = max(1, $page - 5);
                    $end_page = min($total_pages, $page + 5);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&sort=<?php echo htmlspecialchars($sort_type); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&sort=<?php echo htmlspecialchars($sort_type); ?>">다음 ›</a>
                    <a href="?page=<?php echo $total_pages; ?>&sort=<?php echo htmlspecialchars($sort_type); ?>">끝 »</a>
                <?php else: ?>
                    <span class="disabled">다음 ›</span>
                    <span class="disabled">끝 »</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- 뒤로가기 버튼 -->
        <a href="smain.php" class="back-button">← 게임으로 돌아가기</a>
    </div>
</div>

</body>
</html>
