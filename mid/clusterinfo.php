<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>中间件桌面ECC</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="js/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/iconfont.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<h2>生产集群信息合集：</h2>

<?php
// 指定目录路径
$dir = "/data/deskecc/ack/clusterinfo/";

// 打开指定目录
if (is_dir($dir)) {
    // 打开目录
    if ($dh = opendir($dir)) {
        // 循环读取目录中的文件
        while (($file = readdir($dh)) !== false) {
            // 仅处理 .csv 文件
            if (pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
                // 输出超链接
                echo "<a href='view_text.php?file=" . urlencode($file) . "'>" . $file . "</a><br>";
            }
        }
        // 关闭目录句柄
        closedir($dh);
    }
}
?>

</body>
</html>
