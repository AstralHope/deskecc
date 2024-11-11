<?php
function displayCsv($filePath) {
    // 检查文件是否存在
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return '文件不存在或不可读取';
    }

    $data = [];
    // 打开 CSV 文件
    if (($handle = fopen($filePath, 'r')) !== false) {
        // 读取所有数据
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $data[] = $row;
        }
        fclose($handle);
    } else {
        return '无法打开文件';
    }

    // 将数据转换为 JSON 格式以便在 JavaScript 中处理
    $jsonData = json_encode($data);

    // 输出 HTML 和 JavaScript
    $output = '<div>';
    $output .= '<input type="text" id="searchInput" placeholder="搜索..." onkeyup="filterTable()">';

    // 显示表格
    $output .= '<table border="1" id="csvTable">';
    $output .= '<thead><tr>';
    foreach ($data[0] as $header) {
        $output .= '<th>' . htmlspecialchars($header) . '</th>';
    }
    $output .= '</tr></thead><tbody id="tableBody"></tbody></table>';

    // 显示总项数和每页行数选择
    $output .= '<div style="margin-top: 10px;">';
    $output .= '共 <span id="totalItems">0</span> 项';
    $output .= ' 每页显示: <select id="rowsPerPage" onchange="changeRowsPerPage()">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>';
    $output .= '</div>';

    // 翻页控件
    $output .= '<div id="pagination" style="margin-top: 10px;"></div>';

    // 数据总数和每页显示的数量
    $output .= '<script>
        var data = ' . $jsonData . ';
        var currentPage = 1;
        var rowsPerPage = 10;

        // 渲染表格数据
        function renderTable(page = 1) {
            var start = (page - 1) * rowsPerPage + 1;
            var end = Math.min(start + rowsPerPage - 1, data.length - 1);

            // 更新总项数
            document.getElementById("totalItems").textContent = data.length - 1;

            // 更新分页
            updatePagination(page);

            var tableBody = document.getElementById("tableBody");
            tableBody.innerHTML = ""; // 清空现有数据

            // 列出当前页的数据
            for (var i = start; i <= end; i++) {
                var row = data[i];
                var tr = document.createElement("tr");
                row.forEach(function(cell) {
                    var td = document.createElement("td");
                    td.textContent = cell;
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

            // 显示上一页按钮
            if (page > 1) {
                var prevBtn = document.createElement("button");
                prevBtn.textContent = "上一页";
                prevBtn.onclick = function() { renderTable(page - 1); };
                paginationDiv.appendChild(prevBtn);
            }

            // 显示页码
            paginationDiv.appendChild(document.createTextNode(" 第 " + page + " 页 / 共 " + totalPages + " 页 "));

            // 显示下一页按钮
            if (page < totalPages) {
                var nextBtn = document.createElement("button");
                nextBtn.textContent = "下一页";
                nextBtn.onclick = function() { renderTable(page + 1); };
                paginationDiv.appendChild(nextBtn);
            }
        }

        // 搜索功能
        function filterTable() {
            var input = document.getElementById("searchInput");
            var filter = input.value.toLowerCase();
            var filteredData = data.filter(function(row, index) {
                if (index === 0) return true; // 保留表头
                return row.some(function(cell) {
                    return cell.toLowerCase().indexOf(filter) !== -1;
                });
            });

            // 更新数据并渲染第一页
            data = filteredData;
            currentPage = 1;
            renderTable(currentPage);
        }

        // 改变每页显示的行数
        function changeRowsPerPage() {
            rowsPerPage = parseInt(document.getElementById("rowsPerPage").value);
            renderTable(currentPage);
        }

        // 初始化
        renderTable(currentPage);
    </script>';

    return $output;
}

// 获取 POST 参数
$filePath = isset($_POST['file']) ? $_POST['file'] : '';

// 显示文件路径输入表单
if (empty($filePath)) {
    echo '<form method="POST">';
    echo 'CSV 文件路径: <input type="text" name="file" required>';
    echo '<button type="submit">显示数据</button>';
    echo '</form>';
} else {
    // 调用函数并输出结果
    echo displayCsv($filePath);
}
?>
