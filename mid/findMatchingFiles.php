<?php
function findMatchingFiles($directory, $pattern) {
    // 确保目录存在并且是一个目录
    if (!is_dir($directory)) {
        echo "目录不存在或不是一个有效目录";
        return;
    }

    // 扫描目录中的文件
    $files = scandir($directory);

    // 过滤文件，匹配正则表达式
    $matchingFiles = [];
    foreach ($files as $file) {
        // 跳过 . 和 .. 目录
        if ($file === '.' || $file === '..') {
            continue;
        }

        // 判断文件名是否匹配正则表达式
        if (preg_match($pattern, $file)) {
            $matchingFiles[] = $file;
        }
    }

    // 输出匹配的文件名
    foreach ($matchingFiles as $file) {
        echo $file . PHP_EOL;
    }
}

// 示例用法
$directory = "/data/deskecc/ack/index/";
$pattern = "/^.*\.csv$/";  // 匹配所有 .txt 文件
findMatchingFiles($directory, $pattern);
?>

