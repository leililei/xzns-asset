<?php
$url = 'https://xznstest-cdn.szruikunwl.com/wxres/ab/Remote/0.0.166/database.assetbundle';
$localFile = 'database.assetbundle';
$outputDir = 'output';
$downloadDir = 'abs';

// 使用 file_get_contents 下载
$content = file_get_contents($url);

if ($content !== false) {
    file_put_contents($localFile, $content);
    echo "下载成功，文件大小: " . strlen($content) . " 字节\n";
} else {
    echo "下载失败\n";
    exit(1);
}

// 创建输出目录
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// 调用 AssetStudio.CLI 解包
$assetStudioPath = 'C:/Program Files/AssetStudio-net10.0-win/AssetStudio.CLI.exe';
$cmd = escapeshellarg($assetStudioPath) . ' ' . escapeshellarg($localFile) . ' ' . escapeshellarg($outputDir) . ' --game Normal --export_type Convert 2>&1';

echo "正在解包 assetbundle...\n";
$output = [];
$returnCode = 0;
exec($cmd, $output, $returnCode);

if ($returnCode !== 0) {
    echo "解包失败，返回码: $returnCode\n";
    echo "输出: " . implode("\n", $output) . "\n";
    exit(1);
}
echo "解包成功！\n";

// 解析 JSON 文件
$jsonFile = $outputDir . '/MonoBehaviour/abdatabase.json';
if (!file_exists($jsonFile)) {
    echo "JSON 文件不存在: $jsonFile\n";
    exit(1);
}

$jsonData = json_decode(file_get_contents($jsonFile), true);
if (!$jsonData || !isset($jsonData['abInfos'])) {
    echo "JSON 解析失败或没有 abInfos\n";
    exit(1);
}

$abInfos = $jsonData['abInfos'];
echo "解析到 " . count($abInfos) . " 个 abInfo\n";

// 创建下载目录
if (!is_dir($downloadDir)) {
    mkdir($downloadDir, 0777, true);
}

// 生成 aria2 下载列表
$baseUrl = 'https://xznstest-cdn.szruikunwl.com/wxres/ab/Remote/abs/';
$aria2InputFile = 'aria2_input.txt';
$fp = fopen($aria2InputFile, 'w');

foreach ($abInfos as $info) {
    $name = $info['Name'];
    $downloadUrl = $baseUrl . $name;
    $localPath = $downloadDir . '/' . $name;

    // 确保目录存在
    $dir = dirname($localPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    // 写入 aria2 输入文件
    // 格式: URL\n  out=本地路径
    fwrite($fp, $downloadUrl . "\n");
    fwrite($fp, "  out=" . $localPath . "\n");
}

fclose($fp);
echo "已生成 aria2 输入文件: $aria2InputFile\n";

// 调用 aria2 下载
echo "开始使用 aria2 下载...\n";
$aria2Cmd = 'aria2c -i ' . escapeshellarg($aria2InputFile) . ' -j 16 -x 16 -s 16 --console-log-level=notice 2>&1';
$aria2Output = [];
$aria2ReturnCode = 0;
exec($aria2Cmd, $aria2Output, $aria2ReturnCode);

if ($aria2ReturnCode === 0) {
    echo "aria2 下载完成！\n";
} else {
    echo "aria2 下载完成，但有部分文件失败\n";
}
echo "输出目录: $downloadDir\n";
?>
