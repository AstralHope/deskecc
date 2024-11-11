<?php
function makeLink($text) {
    // 定义基础路径常量，仅在 makeLink 函数内有效
    $BASEPATH = '/data/deskecc/ack/clusterinfo/';

    // 将基础路径与文本拼接成完整的文件路径
    $filePath = $BASEPATH . $text;

    // 生成 HTML 超链接标签，通过 POST 提交
    $url = 'display_csv_new.php';
    return '<form method="POST" action="' . htmlspecialchars($url) . '" style="display: inline;">
                <input type="hidden" name="file" value="' . htmlspecialchars($filePath) . '">
                <button type="submit" style="color: blue; text-decoration: underline; background: none; border: none; padding: 0; cursor: pointer;">
                    ' . htmlspecialchars($text) . '
                </button>
            </form>';
}

function displayCsv($filePath, $extraColumnIndex = null, $extraFunction = null) {
    // 定义基础路径常量
    $BASEPATH = '/data/deskecc/ack/index/';
    $fullFilePath = $BASEPATH . $filePath;
    
    $fileName = pathinfo($filePath, PATHINFO_FILENAME);

    if (!file_exists($fullFilePath) || !is_readable($fullFilePath)) {
        return '文件不存在或不可读取';
    }

    $data = [];
    if (($handle = fopen($fullFilePath, 'r')) !== false) {
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $data[] = $row;
        }
        fclose($handle);
    } else {
        return '无法打开文件';
    }

    // 将数据转换为 JSON 格式以便在 JavaScript 中处理
    $jsonData = json_encode($data);
    $extraJsFunction = $extraFunction 
        ? 'function(cell) { return `' . $extraFunction('${cell}') . '`; }'
        : 'function(cell) { return cell; }';

    // 输出 HTML 和 JavaScript，包含搜索和分页功能
    $output = '<div>';
    $output .= '<h1>' . htmlspecialchars($fileName) . '</h1>';
    $output .= '<input type="text" id="searchInput" placeholder="搜索..." onkeydown="checkEnter(event)" style="margin-right: 10px;">';
    $output .= '<button id="searchBtn" onclick="filterTable()">筛选</button>';
    $output .= '<button id="clearSearchBtn" onclick="clearSearch()">清除筛选</button>';

    $output .= '<table border="1" id="csvTable"><thead><tr>';
    foreach ($data[0] as $header) {
        $output .= '<th>' . htmlspecialchars($header) . '</th>';
    }
    $output .= '</tr></thead><tbody id="tableBody"></tbody></table>';

    $output .= '<div style="margin-top: 10px; display: flex; align-items: center;">';
    $output .= '共 <span id="totalItems">0</span> 项 ';
    $output .= '每页显示: <select id="rowsPerPage" onchange="changeRowsPerPage()" style="margin-right: 10px;">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>';
    $output .= '<div id="pagination"></div>';
    $output .= '</div>';

    $output .= '<script>
        var data = ' . $jsonData . ';
        var originalData = JSON.parse(JSON.stringify(data));
        var currentPage = 1;
        var rowsPerPage = 10;
        var extraColumnIndex = ' . ($extraColumnIndex !== null ? (int)$extraColumnIndex : 'null') . ';
        var extraFunction = ' . $extraJsFunction . ';
        
        // JavaScript functions to render table and handle pagination
        function renderTable(page = 1) { /* ... */ }
        function updatePagination(page) { /* ... */ }
        function filterTable() { /* ... */ }
        function checkEnter(event) { if (event.key === "Enter") { filterTable(); } }
        function clearSearch() { /* ... */ }
        function changeRowsPerPage() { /* ... */ }
        renderTable(currentPage);
    </script>';

    return $output;
}

// 处理表单提交
$filePath = isset($_POST['file']) ? $_POST['file'] : '';
$extraColumnIndex = isset($_POST['columnIndex']) ? intval($_POST['columnIndex']) : null;

if (empty($filePath)) {
    echo '<form method="POST">';
    echo 'CSV 文件路径: <input type="text" name="file" required>';
    echo ' 额外处理列 (从0开始): <input type="number" name="columnIndex" min="0">';
    echo '<button type="submit">显示数据</button>';
    echo '</form>';
} else {
    echo displayCsv($filePath, $extraColumnIndex, 'makeLink');
}
?>
