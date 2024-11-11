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
    $output .= '</tr></thead><tbody>';

    // 输出所有行数据
    for ($i = 1; $i < count($data); $i++) {
        $output .= '<tr>';
        foreach ($data[$i] as $cell) {
            $output .= '<td>' . htmlspecialchars($cell) . '</td>';
        }
        $output .= '</tr>';
    }

    $output .= '</tbody></table>';
    $output .= '</div>';

    // JavaScript 代码，用于在客户端实现搜索过滤功能
    $output .= '<script>
        function filterTable() {
            var input = document.getElementById("searchInput");
            var filter = input.value.toLowerCase();
            var table = document.getElementById("csvTable");
            var tr = table.getElementsByTagName("tr");

            // 循环表格中的每一行，检查是否匹配搜索内容
            for (var i = 1; i < tr.length; i++) {
                var row = tr[i];
                var txtValue = row.textContent || row.innerText;
                row.style.display = txtValue.toLowerCase().indexOf(filter) > -1 ? "" : "none";
            }
        }
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
