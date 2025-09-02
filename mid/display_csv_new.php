<?php
// 额外处理列函数示例
function makeLinkforCluster($text) {
    $BASEPATH = '/data/deskecc/ack/clusterinfo/';
    $filePath = $BASEPATH . $text . '.csv';
    $url = 'display_csv_new.php';
    return '<form method="POST" action="' . htmlspecialchars($url) . '" style="display:inline;white-space:nowrap;">
                <input type="hidden" name="file" value="' . htmlspecialchars($filePath) . '">
                <input type="hidden" name="columnIndex" value="3">
                <input type="hidden" name="function" value="makeLinkforNode">
                <input type="hidden" name="hiddenClumnIndex" value="1,2">
                <button type="submit" style="color:blue;text-decoration:underline;background:none;border:none;padding:0;cursor:pointer;white-space:nowrap;">
                    ' . htmlspecialchars($text) . '
                </button>
            </form>';
}

function makeLinkforNode($text) {
    $encodedText = $text . '.txt';
    return '<a href="view_txt.php?file=' . $encodedText . '">' . htmlspecialchars($text) . '</a>';
}

// ⭐ 新函数：根据 T 列 index 动态组合 T-3,T-2,T列生成链接
function makeLinkForNginx($text, $row, $colIndex) {
    $colT_3 = isset($row[$colIndex - 3]) ? $row[$colIndex - 3] : '';
    $colT_2 = isset($row[$colIndex - 2]) ? $row[$colIndex - 2] : '';
    $colT   = isset($row[$colIndex]) ? $row[$colIndex] : '';

    $encoded = $colT_3 . '_' . $colT_2 . '_' . $colT . '.conf';

    return '<a href="view_conf.php?file=' . htmlspecialchars($encoded) . '" target="_blank">' . htmlspecialchars($text) . '</a>';
}

// 主函数：显示 CSV
function displayCsv($filePath, $extraColumnIndex = null, $extraFunction = null, $hiddenColumns = '') {
    $BASEPATH = ''; // ⭐ 常量放在函数内
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

    $jsonData = json_encode($data);
    $hiddenColumnsArray = array_map('intval', explode(',', $hiddenColumns));

    $output = '<div>';
    $output .= '<h1>' . htmlspecialchars($fileName) . '</h1>';
    $output .= '<input type="text" placeholder="搜索..." onkeydown="if(event.key==\'Enter\'){filterTable();}" style="margin-right:10px;">';
    $output .= '<button onclick="filterTable()">筛选</button>';
    $output .= '<button onclick="clearSearch()">清除筛选</button>';
    $output .= '<button onclick="exportTableToCSV()">导出结果为CSV</button>';

    $output .= '<table border="1"><thead><tr>';
    foreach ($data[0] as $index => $header) {
        if (in_array($index + 1, $hiddenColumnsArray)) continue;
        $output .= '<th onclick="sortTable(' . $index . ')">' . htmlspecialchars($header) . '</th>';
    }
    $output .= '</tr></thead><tbody></tbody></table>';

    $output .= '<div style="margin-top:10px; display:flex; align-items:center;">
                    共 <span id="totalItems">0</span> 项 
                    每页显示:
                    <select onchange="changeRowsPerPage()" style="margin-left:5px;margin-right:10px;">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <div id="pagination"></div>
                </div>';

    // ===== JS 部分 =====
    $output .= '<script>
        var data = ' . $jsonData . ';
        var originalData = JSON.parse(JSON.stringify(data));
        var currentPage = 1;
        var rowsPerPage = 10;
        var extraColumnIndex = ' . ($extraColumnIndex !== null ? (int)$extraColumnIndex : 'null') . ';
        var extraFunction = ' . ($extraFunction !== null ? json_encode($extraFunction) : 'null') . ';
        var hiddenColumns = ' . json_encode($hiddenColumnsArray) . ';
        var sortAscending = true;

        // ⭐ 额外处理列函数映射
        var extraFunctions = {
            "makeLinkforCluster": makeLinkforCluster,
            "makeLinkforNode": makeLinkforNode,
            "makeLinkForNginx": makeLinkForNginx
        };

        // 渲染表格
        function renderTable(page = 1) {
            var start = (page-1)*rowsPerPage +1;
            var end = Math.min(start+rowsPerPage-1,data.length-1);
            document.getElementById("totalItems").textContent = data.length-1;

            updatePagination(page);

            var tbody = document.querySelector("tbody");
            tbody.innerHTML = "";

            for(var i=start;i<=end;i++){
                var row = data[i];
                var tr = document.createElement("tr");
                row.forEach(function(cell,index){
                    if(hiddenColumns.includes(index+1)) return;
                    var td = document.createElement("td");
                    if(index===extraColumnIndex && extraFunction && extraFunctions[extraFunction]){
                        td.innerHTML = extraFunctions[extraFunction](cell,row,index);
                    } else {
                        td.textContent = cell;
                    }
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
            }
        }

        function updatePagination(page){
            var totalPages = Math.ceil((data.length-1)/rowsPerPage);
            var paginationDiv = document.getElementById("pagination");
            paginationDiv.innerHTML="";
            if(page>1){
                var prevBtn = document.createElement("button");
                prevBtn.textContent="上一页";
                prevBtn.onclick=function(){ renderTable(page-1); };
                paginationDiv.appendChild(prevBtn);
            }
            paginationDiv.appendChild(document.createTextNode(" 第 "+page+" 页 / 共 "+totalPages+" 页 "));
            if(page<totalPages){
                var nextBtn = document.createElement("button");
                nextBtn.textContent="下一页";
                nextBtn.onclick=function(){ renderTable(page+1); };
                paginationDiv.appendChild(nextBtn);
            }
        }

        function sortTable(col){
            var isNumeric = !isNaN(data[1][col]);
            var rows = data.slice(1);
            rows.sort(function(a,b){
                var aVal=a[col], bVal=b[col];
                if(isNumeric) return sortAscending?aVal-bVal:bVal-aVal;
                return sortAscending?aVal.localeCompare(bVal):bVal.localeCompare(aVal);
            });
            data = [data[0]].concat(rows);
            sortAscending=!sortAscending;
            renderTable(currentPage);
        }

        function filterTable(){
            var input = document.querySelector("input[placeholder=\'搜索...\']");
            var filter = input.value.toLowerCase();
            if(filter==="") data = JSON.parse(JSON.stringify(originalData));
            else data = originalData.filter(function(row,index){ if(index===0) return true; return row.some(function(cell){ return cell.toLowerCase().includes(filter); }); });
            currentPage=1;
            renderTable(currentPage);
        }

        function clearSearch(){
            document.querySelector("input[placeholder=\'搜索...\']").value="";
            data = JSON.parse(JSON.stringify(originalData));
            currentPage=1;
            renderTable(currentPage);
        }

        function changeRowsPerPage(){
            rowsPerPage = parseInt(document.querySelector("select").value);
            renderTable(currentPage);
        }

        function exportTableToCSV(){
            var rows=[];
            var header=[];
            data[0].forEach(function(cell,index){ if(!hiddenColumns.includes(index+1)) header.push(cell); });
            rows.push(header);
            for(var i=1;i<data.length;i++){
                var r=[];
                data[i].forEach(function(cell,index){ if(!hiddenColumns.includes(index+1)) r.push(cell); });
                rows.push(r);
            }
            var csv = rows.map(function(r){ return r.map(function(v){ return \'"\' + String(v).replace(/"/g,"""") + \'"\' }).join(","); }).join("\n");
            var link = document.createElement("a");
            link.href = URL.createObjectURL(new Blob([csv],{type:"text/csv;charset=utf-8;"}));
            link.download="' . $fileName . '_filtered.csv";
            link.click();
        }

        // 初始化
        renderTable(currentPage);
    </script>';

    return $output;
}

// 处理表单提交
$filePath = isset($_POST['file']) ? $_POST['file'] : '';
$extraColumnIndex = isset($_POST['columnIndex']) ? intval($_POST['columnIndex']) : null;
$extraFunction = isset($_POST['function']) ? $_POST['function'] : null;
$hiddenColumns = isset($_POST['hiddenClumnIndex']) ? $_POST['hiddenClumnIndex'] : '';

if(empty($filePath)){
    echo '<form method="POST">';
    echo 'CSV 文件路径: <input type="text" value="/data/deskecc/ack/index/All_cluster_info.csv" name="file" required>';
    echo ' 额外处理列 (从0开始): <input type="number" name="columnIndex" min="0">';
    echo '函数: <input type="text" name="function" value="makeLinkforCluster">';
    echo '隐藏列: <input type="text" name="hiddenClumnIndex">';
    echo '<button type="submit">显示数据</button>';
    echo '</form>';
}else{
    echo displayCsv($filePath,$extraColumnIndex,$extraFunction,$hiddenColumns);
}
?>
