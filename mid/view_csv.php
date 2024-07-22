<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>展示文本内容</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="js/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/iconfont.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<?php
// 检查是否设置了文件参数
if (isset($_GET['file'])) {
    // 获取文件名并转义
    $filename = $_GET['file'];
    $filename = basename($filename);
    $filename = str_replace("/", "", $filename); // 防止路径遍历攻击

    // 检查文件是否存在
    if (file_exists("/data/deskecc/ack/clusterinfo/" . $filename) && (pathinfo($filename, PATHINFO_EXTENSION) === 'txt') || (pathinfo($filename, PATHINFO_EXTENSION) === 'csv') ) {
        // 读取文件内容
        $file_content = file_get_contents("/data/deskecc/ack/clusterinfo/" . $filename);
        // 将内容按行分割
        $lines = explode("\n", $file_content);

        // 输出表格
        echo "<table class="table table-striped table-bordered table-hover mp20">";
        foreach ($lines as $line) {
            if (trim($line) !== "") {
                echo "<tr>";
                $cells = explode("数量", $line); // 使用“数量”作为分隔符
                foreach ($cells as $cell) {
                    echo "<td>" . htmlspecialchars(trim($cell)) . "</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table>";


    } else {
        echo "文件不存在或不是 .txt 文件。";
    }
} else {
    echo "未指定文件。";
}
?>

</body>
</html>
