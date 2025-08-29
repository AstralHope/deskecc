<?php
function makeLinkforCluster($text) {
    // 定义基础路径常量
    $BASEPATH = '/data/deskecc/ack/clusterinfo/';
    
    // 拼接文件路径
    $filePath = $BASEPATH . $text . '.csv';
    
    // 生成超链接
    $url = 'display_csv_new.php';
    return '<form method="POST" action="' . htmlspecialchars($url) . '" style="display: inline; white-space: nowrap;">
                <input type="hidden" name="file" value="' . htmlspecialchars($filePath) . '">
                <input type="hidden" name="columnIndex" value="3">
                <input type="hidden" name="function" value="makeLinkforNode">
                <input type="hidden" name="hiddenClumnIndex" value="1,2">
                <button type="submit" style="color: blue; text-decoration: underline; background: none; border: none; padding: 0; cursor: pointer; white-space: nowrap;">
                    ' . htmlspecialchars($text) . '
                </button>
            </form>';
}

function makeLinkforNode($text) {
    // 使用 urlencode 函数对文本进行编码，以确保 URL 安全
    $encodedText = $text. '.txt';
    //$encodedText = urlencode($text. '.txt');
    // 返回超链接
    return '<a href="view_txt.php?file=' . $encodedText . '">' . htmlspecialchars($text) . '</a>';
}

function makeLinkforNginx($text) {
    // 使用 urlencode 函数对文本进行编码，以确保 URL 安全
    $encodedText = $text. '.conf';
    //$encodedText = urlencode($text. '.txt');
    // 返回超链接
    return '<a href="view_conf.php?file=' . $encodedText . '">' . htmlspecialchars($text) . '</a>';
}

function displayCsv($filePath, $extraColumnIndex = null, $extraFunction = null, $hiddenColumns = '') {
    // 定义基础路径常量为空
    $BASEPATH = '';
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

    // 解析隐藏的列索引
    $hiddenColumnsArray = array_map('intval', explode(',', $hiddenColumns));

    // 输出 HTML 和 JavaScript，包含搜索和分页功能
    $output = '<div>';
    $output .= '<h1>' . htmlspecialchars($fileName) . '</h1>';
    $output .= '<input type="text" id="searchInput" placeholder="搜索..." onkeydown="checkEnter(event)" style="margin-right: 10px;">';
    $output .= '<button id="searchBtn" onclick="filterTable()">筛选</button>';
    $output .= '<button id="clearSearchBtn" onclick="clearSearch()">清除筛选</button>';
    $output .= '<button onclick="exportTableToCSV()">导出结果为CSV</button>'; // ⭐ 修改点：使用onclick


    $output .= '<table border="1" id="csvTable"><thead><tr>';
    foreach ($data[0] as $index => $header) {
        // 如果当前列在隐藏列列表中，跳过该列
        if (in_array($index + 1, $hiddenColumnsArray)) {
            continue;
        }
        $output .= '<th onclick="sortTable(' . $index . ')">' . htmlspecialchars($header) . '</th>';
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
        var hiddenColumns = ' . json_encode($hiddenColumnsArray) . ';
        var sortAscending = true;
        
        // 渲染表格数据
        function renderTable(page = 1) {
            var start = (page - 1) * rowsPerPage + 1;
            var end = Math.min(start + rowsPerPage - 1, data.length - 1);
            document.getElementById("totalItems").textContent = data.length - 1;
            updatePagination(page);
        
            var tableBody = document.getElementById("tableBody");
            tableBody.innerHTML = "";
        
            for (var i = start; i <= end; i++) {
                var row = data[i];
                var tr = document.createElement("tr");
                row.forEach(function(cell, index) {
                    if (hiddenColumns.includes(index + 1)) return;
                    var td = document.createElement("td");
                    if (index === extraColumnIndex && typeof extraFunction === "function") {
                        td.innerHTML = extraFunction(cell);
                    } else {
                        td.textContent = cell;
                    }
                    tr.appendChild(td);
                });
                tableBody.appendChild(tr);
            }
        }
        
        // 更新分页按钮
        function updatePagination(page) {
            var totalPages = Math.ceil((data.length - 1) / rowsPerPage);
            var paginationDiv = document.getElementById("pagination");
            paginationDiv.innerHTML = "";
        
            if (page > 1) {
                var prevBtn = document.createElement("button");
                prevBtn.textContent = "上一页";
                prevBtn.onclick = function() { renderTable(page - 1); };
                paginationDiv.appendChild(prevBtn);
            }
        
            paginationDiv.appendChild(document.createTextNode(" 第 " + page + " 页 / 共 " + totalPages + " 页 "));
        
            if (page < totalPages) {
                var nextBtn = document.createElement("button");
                nextBtn.textContent = "下一页";
                nextBtn.onclick = function() { renderTable(page + 1); };
                paginationDiv.appendChild(nextBtn);
            }
        }
        
        // 排序表格
        function sortTable(columnIndex) {
            var isNumeric = !isNaN(data[1][columnIndex]);
            var rows = data.slice(1); // 排除表头
        
            rows.sort(function(a, b) {
                var cellA = a[columnIndex];
                var cellB = b[columnIndex];
                if (isNumeric) {
                    return sortAscending ? cellA - cellB : cellB - cellA;
                } else {
                    return sortAscending 
                        ? cellA.localeCompare(cellB) 
                        : cellB.localeCompare(cellA);
                }
            });
        
            data = [data[0]].concat(rows);
            sortAscending = !sortAscending;
            renderTable(currentPage);
        }
        
        // 搜索功能
        function filterTable() {
            var input = document.getElementById("searchInput");
            var filter = input.value.toLowerCase();
            if (filter === "") {
                data = JSON.parse(JSON.stringify(originalData));
            } else {
                data = originalData.filter(function(row, index) {
                    if (index === 0) return true; // 保留表头
                    return row.some(function(cell) {
                        return cell.toLowerCase().indexOf(filter) !== -1;
                    });
                });
            }
            currentPage = 1;
            renderTable(currentPage);
        }
        
        // 检查回车键
        function checkEnter(event) {
            if (event.key === "Enter") {
                filterTable();
            }
        }
        
        // 清除搜索
        function clearSearch() {
            document.getElementById("searchInput").value = "";
            data = JSON.parse(JSON.stringify(originalData));
            currentPage = 1;
            renderTable(currentPage);
        }
        
        // 改变每页显示行数
        function changeRowsPerPage() {
            rowsPerPage = parseInt(document.getElementById("rowsPerPage").value);
            renderTable(currentPage);
        }
        
        // ⭐ 导出 CSV 功能（onclick 模式，不用 id）
        function exportTableToCSV() {
            var rows = [];
            // 表头
            var header = [];
            data[0].forEach(function(cell, index){
                if (!hiddenColumns.includes(index + 1)) header.push(cell);
            });
            rows.push(header);
        
            // 数据行
            for (var i = 1; i < data.length; i++) {
                var row = [];
                data[i].forEach(function(cell, index){
                    if (!hiddenColumns.includes(index + 1)) row.push(cell);
                });
                rows.push(row);
            }
        
            var csv = rows.map(function(r){
                return r.map(function(v){ return "\\""+String(v).replace(/\\"/g,"\\"\\"")+"\\""; }).join(",");
            }).join("\\n");
        
            var link = document.createElement("a");
            link.href = URL.createObjectURL(new Blob([csv], {type: "text/csv;charset=utf-8;"}));
            link.download = "' . $fileName . '_filtered.csv";
            link.click();
        }
        
        // 初始化渲染
        renderTable(currentPage);
    </script>';

    return $output;
}

// 处理表单提交
$filePath = isset($_POST['file']) ? $_POST['file'] : '';
$extraColumnIndex = isset($_POST['columnIndex']) ? intval($_POST['columnIndex']) : null;
$extraFunction = isset($_POST['function']) ? $_POST['function'] : null;
$hiddenColumns = isset($_POST['hiddenClumnIndex']) ? $_POST['hiddenClumnIndex'] : '';

if (empty($filePath)) {
    echo '<form method="POST">';
    echo 'CSV 文件路径: <input type="text" value="/data/deskecc/ack/index/All_cluster_info.csv" name="file" required>';
    echo ' 额外处理列 (从0开始): <input type="number" name="columnIndex" min="0">';
    echo '函数: <input type="text" name="function" value="makeLinkforCluster">';
    echo '隐藏列: <input type="text" name="hiddenClumnIndex">';
    echo '<button type="submit">显示数据</button>';
    echo '</form>';
} else {
    echo displayCsv($filePath, $extraColumnIndex, $extraFunction, $hiddenColumns);
}
?>
