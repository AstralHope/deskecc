<?php
$start_time = microtime(true);

// 执行Python脚本
exec('python3 wordCloud/724.py', $output, $return_var);

$end_time = microtime(true);
$execution_time = $end_time - $start_time;

// 检查Python脚本是否成功执行
if ($return_var === 0) {
    $image_path = 'wordCloud/Images/724.png';
    echo json_encode(['image_path' => $image_path, 'execution_time' => $execution_time]);
} else {
    echo json_encode(['error' => 'Failed to generate image']);
}
?>
