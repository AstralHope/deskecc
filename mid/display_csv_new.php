<?php
// ============================================
// display_csv_new.php
// 完整版 CSV 显示页面
// 支持：分页、搜索、隐藏列、导出 CSV、额外处理列（如生成超链接）
// ============================================

function displayCsv($filePath, $extraColumnIndex = null, $extraFunction = null, $hiddenColumns = '') {
    // 基础路径常量
    $BASEPATH = '';
    $fullFilePath = $BASEPATH . $filePath;
    $fileName = pathinfo($filePath, PATHINFO_FILENAME);

    if (!file_exists($fullFilePath) || !is_readable($fullFilePath)) {
        return '文件不存在或不可读取';
    }

    // 读取 CSV 数据
    $data = [];
    if (($handle = fopen($fullFilePath, 'r')) !== false) {
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $data[] = $row;
        }
        fclose($handle);
    } else {
        return '无法打开文件';
    }

    // 转为 JSON 以便 JS 使用
    $jsonData = json_encode($data);
    $hiddenColumnsArray = array_map('intval', explode(',', $hiddenColumns));

    // ============ 输出 HTML ==============
    $output = '<div>';
    $output .= '<h1>' . htmlspecialchars($fileName) . '</h1>';
    $output .= '<input type="text" id="searchInput" placeholder="搜索..." onkeydown="if(event.key==\'Enter\') filterTable()" style="margin-right:10px;">';
    $output .= '<button onclick="filterTable()">筛选</button>';
    $output .= '<button onclick="clearSearch()">清除筛选</button>';
    $output .= '<button onclick="exportTableToCSV()">导出结果为CSV</button>';

    $output .= '<table border="1"><thead><tr>';
    foreach ($data[0] as $index => $header) {
        if (in_array($index + 1, $hiddenColumnsArray)) continue;
        $output .= '<th onclick="sortTable(' . $index . ')">' . htmlspecialchars($header) . '</th>';
    }
    $output .= '</tr></thead><tbody id="tableBody"></tbody></table>';

    $output .= '共 <span id="totalItems">0</span> 项 每页显示: 
        <select id="rowsPerPage" onchange="changeRowsPerPage()">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
        <div id="pagination"></div>';

    // ============ JS 脚本 ============
    $output .= '<script>
        // 数据初始化
        var data = ' . $jsonData . ';
        var originalData = JSON.parse(JSON.stringify(data));
        var currentPage = 1;
        var rowsPerPage = 10;
        var extraColumnIndex = ' . ($extraColumnIndex !== null ? (int)$extraColumnIndex : 'null') . ';
        var extraFunction = "' . ($extraFunction ?? '') . '"; // 函数名字符串
        var hiddenColumns = ' . json_encode($hiddenColumnsArray) . ';
        var sortAscending = true;

        // ===== 额外处理列函数（可修改或增加） =====
        function makeLinkforCluster(cell, row, index) {
            var BASEPATH = "/data/deskecc/ack/clusterinfo/";
            var filePath = BASEPATH + cell + ".csv";
            return "<form method=\'POST\' action=\'display_csv_new.php\' style=\'display:inline; white-space:nowrap;\'>" +
                   "<input type=\'hidden\' name=\'file\' value=\'" + filePath + "\'>" +
                   "<input type=\'hidden\' name=\'columnIndex\' value=\'3\'>" +
                   "<input type=\'hidden\' name=\'function\' value=\'makeLinkforNode\'>" +
                   "<input type=\'hidden\' name=\'hiddenClumnIndex\' value=\'1,2\'>" +
                   "<button type=\'submit\' style=\'color:blue;text-decoration:underline;background:none;border:none;padding:0;cursor:pointer;white-space:nowrap;\'>" +
                   cell + "</button></form>";
        }

        function makeLinkforNode(cell, row, index) {
            var encoded = cell + ".txt";
            return "<a href=\'view_txt.php?file=" + encoded + "\'>" + cell + "</a>";
        }

        // ===== 额外处理列函数（可修改或增加） =====
        function makeLinkForNginx(cell, row, index) {
            // 动态根据 extraColumnIndex（T列的 index）计算 T-3, T-2, T列
            var colT_3 = row[index - 3] || "";
            var colT_2 = row[index - 2] || "";
            var colT   = row[index] || "";
        
            var encoded = colT_3 + "_" + colT_2 + "_" + colT + ".conf";
        
            return "<a href='view_conf.php?file=" + encoded + "' target='_blank'>" + cell + "</a>";
        }


        // ===== 渲染表格 =====
        function renderTable(page=1) {
            var start = (page-1)*rowsPerPage + 1;
            var end = Math.min(start + rowsPerPage - 1, data.length - 1);
            document.getElementById("totalItems").textContent = data.length - 1;
            updatePagination(page);

            var tableBody = document.getElementById("tableBody");
            tableBody.innerHTML = "";

            for (var i=start;i<=end;i++){
                var row = data[i];
                var tr = document.createElement("tr");
                row.forEach(function(cell,index){
                    if(hiddenColumns.includes(index+1)) return;
                    var td = document.createElement("td");
                    if(index === extraColumnIndex && typeof window[extraFunction] === "function"){
                        td.innerHTML = window[extraFunction](cell,row,index); // ⭐ 调用 JS 全局函数
                    } else {
                        td.textContent = cell;
                    }
                    tr.appendChild(td);
                });
                tableBody.appendChild(tr);
            }
        }

        // ===== 分页 =====
        function updatePagination(page) {
            var totalPages = Math.ceil((data.length-1)/rowsPerPage);
            var div = document.getElementById("pagination"); div.innerHTML="";
            if(page>1){ var b=document.createElement("button"); b.textContent="上一页"; b.onclick=function(){renderTable(page-1)}; div.appendChild(b);}
            div.appendChild(document.createTextNode(" 第 "+page+" 页 / 共 "+totalPages+" 页 "));
            if(page<totalPages){ var b=document.createElement("button"); b.textContent="下一页"; b.onclick=function(){renderTable(page+1)}; div.appendChild(b);}
        }

        // ===== 排序 =====
        function sortTable(colIndex){
            var rows = data.slice(1);
            var isNum = !isNaN(data[1][colIndex]);
            rows.sort(function(a,b){
                var v1=a[colIndex],v2=b[colIndex];
                if(isNum) return sortAscending?v1-v2:v2-v1;
                else return sortAscending?v1.localeCompare(v2):v2.localeCompare(v1);
            });
            data=[data[0]].concat(rows); sortAscending=!sortAscending; renderTable(currentPage);
        }

        // ===== 搜索 =====
        function filterTable(){
            var f=document.getElementById("searchInput").value.toLowerCase();
            if(f===""){ data=JSON.parse(JSON.stringify(originalData)); }
            else {
                data = originalData.filter(function(row,i){ if(i===0) return true; return row.some(c=>c.toLowerCase().includes(f));});
            }
            currentPage=1; renderTable(currentPage);
        }

        function clearSearch(){ document.getElementById("searchInput").value=""; data=JSON.parse(JSON.stringify(originalData)); currentPage=1; renderTable(currentPage);}
        function changeRowsPerPage(){ rowsPerPage=parseInt(document.getElementById("rowsPerPage").value); renderTable(currentPage); }

        // ===== 导出 CSV =====
        function exportTableToCSV(){
            var rows=[]; rows.push(data[0].filter((c,i)=>!hiddenColumns.includes(i+1)));
            for(var i=1;i<data.length;i++){ rows.push(data[i].filter((c,i)=>!hiddenColumns.includes(i+1))); }
            var csv = rows.map(r=>r.map(v=>"\""+String(v).replace(/\"/g,"\"\"")+"\"").join(",")).join("\n");
            var link = document.createElement("a");
            link.href = URL.createObjectURL(new Blob([csv], {type:"text/csv;charset=utf-8;"}));
            link.download="' . $fileName . '_filtered.csv"; link.click();
        }

        renderTable(currentPage); // 初始渲染
    </script>';

    return $output;
}

// =================== 表单提交处理 ===================
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
