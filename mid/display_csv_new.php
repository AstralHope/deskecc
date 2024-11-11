<?php
function displayCsv($filePath, $page = 1, $rowsPerPage = 10) {
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

    // 计算总行数和总页数
    $totalRows = count($data) - 1; // 扣除表头行
    $totalPages = ceil($totalRows / $rowsPerPage); 
    $offset = ($page - 1) * $rowsPerPage + 1; // 从数据部分的第二行开始读取

    // 显示总项数在页面顶部
    $output = '<div style="margin-bottom: 10px;">共 ' . $totalRows . ' 项</div>';

    // 列出表头
    $output .= '<table border="1">';
    $output .= '<tr>';
    foreach ($data[0] as $header) {
        $output .= '<th>' . htmlspecialchars($header) . '</th>';
    }
    $output .= '</tr>';

    // 列出当前页的数据
    for ($i = $offset; $i < $offset + $rowsPerPage && $i < $totalRows + 1; $i++) {
        $output .= '<tr>';
        foreach ($data[$i] as $cell) {
            $output .= '<td>' . htmlspecialchars($cell) . '</td>';
        }
        $output .= '</tr>';
    }
    $output .= '</table>';

    // 下拉菜单和分页导航在同一行
    $output .= '<div style="display: flex; align-items: center; margin-top: 10px;">';

    // 下拉菜单用于选择每页显示的行数
    $output .= '<form method="POST" style="display: inline-block; margin-right: 10px;">';
    $output .= '<input type="hidden" name="file" value="' . htmlspecialchars($filePath) . '">';
    $output .= '<input type="hidden" name="page" value="1">'; // 切换行数时从第一页开始
    $output .= '每页显示: <select name="rowsPerPage" onchange="this.form.submit()">';
    $rowsOptions = [10, 20, 50, 100];
    foreach ($rowsOptions as $option) {
        $selected = ($option == $rowsPerPage) ? 'selected' : '';
        $output .= '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>';
    }
    $output .= '</select> 条</form>';

    // 显示当前页和总页数
    $output .= '<span>第 ' . $page . ' 页 / 共 ' . $totalPages . ' 页</span>';

    // 翻页链接紧跟在页数信息后
    if ($page > 1) {
        $output .= ' <form method="POST" style="display: inline;">';
        $output .= '<input type="hidden" name="file" value="' . htmlspecialchars($filePath) . '">';
        $output .= '<input type="hidden" name="page" value="' . ($page - 1) . '">';
        $output .= '<input type="hidden" name="rowsPerPage" value="' . $rowsPerPage . '">';
        $output .= '<button type="submit">上一页</button></form>';
    }
    if ($page < $totalPages) {
        $output .= ' <form method="POST" style="display: inline;">';
        $output .= '<input type="hidden" name="file" value="' . htmlspecialchars($filePath) . '">';
        $output .= '<input type="hidden" name="page" value="' . ($page + 1) . '">';
        $output .= '<input type="hidden" name="rowsPerPage" value="' . $rowsPerPage . '">';
        $output .= '<button type="submit">下一页</button></form>';
    }

    $output .= '</div>';

    return $output;
}

// 获取 POST 参数
$filePath = isset($_POST['file']) ? $_POST['file'] : '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$rowsPerPage = isset($_POST['rowsPerPage']) ? (int)$_POST['rowsPerPage'] : 10;

// 显示文件路径输入表单
if (empty($filePath)) {
    echo '<form method="POST">';
    echo 'CSV 文件路径: <input type="text" name="file" required>';
    echo '<button type="submit">显示数据</button>';
    echo '</form>';
} else {
    // 调用函数并输出结果
    echo displayCsv($filePath, $page, $rowsPerPage);
}
?>
