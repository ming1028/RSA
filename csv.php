作者：PHP进阶架构师
链接：https://zhuanlan.zhihu.com/p/96910679
来源：知乎
著作权归作者所有。商业转载请联系作者获得授权，非商业转载请注明出处。

<?php

namespace api\service;

class ExportService
{

    public static $outPutFile = '';

    /**
     * 导出文件
     * @param string $fileName
     * @param $data
     * @param array $formFields
     * @return mixed
     */
    public static function exportData($fileName = '', $data, $formFields = [])
    {
        $fileArr = [];
        $tmpPath = \Yii::$app->params['excelSavePath'];

        foreach (array_chunk($data, 10000) as $key => $value) {
            self::$outPutFile = '';
            $subject          = !empty($fileName) ? $fileName : 'data_';
            $subject          .= date('YmdHis');
            if (empty($value) || empty($formFields)) {
                continue;
            }

            self::$outPutFile = $tmpPath . $subject . $key . '.csv';
            if (!file_exists(self::$outPutFile)) {
                touch(self::$outPutFile);
            }
            $index  = array_keys($formFields);
            $header = array_values($formFields);
            self::outPut($header);

            foreach ($value as $k => $v) {
                $tmpData = [];
                foreach ($index as $item) {
                    $tmpData[] = isset($v[$item]) ? $v[$item] : '';
                }
                self::outPut($tmpData);
            }
            $fileArr[] = self::$outPutFile;
        }
        
        $zipFile = $tmpPath . $fileName . date('YmdHi') . '.zip';
        $zipRes = self::zipFile($fileArr, $zipFile);
        return $zipRes;
    }

    /**
     * 向文件写入数据
     * @param array $data
     */
    public static function outPut($data = [])
    {
        if (is_array($data) && !empty($data)) {
            $data = implode(',', $data);
            file_put_contents(self::$outPutFile, iconv("UTF-8", "GB2312//IGNORE", $data) . PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * 压缩文件
     * @param $sourceFile
     * @param $distFile
     * @return mixed
     */
    public static function zipFile($sourceFile, $distFile)
    {
        $zip = new \ZipArchive();
        if ($zip->open($distFile, \ZipArchive::CREATE) !== true) {
            return $sourceFile;
        }

        $zip->open($distFile, \ZipArchive::CREATE);
        foreach ($sourceFile as $file) {
            $fileContent = file_get_contents($file);
            $file        = iconv('utf-8', 'GBK', basename($file));
            $zip->addFromString($file, $fileContent);
        }
        $zip->close();
        return $distFile;
    }
    
        /**
     * 下载文件
     * @param $filePath
     * @param $fileName
     */
    public static function download($filePath, $fileName)
    {
        if (!file_exists($filePath . $fileName)) {
            header('HTTP/1.1 404 NOT FOUND');
        } else {
            //以只读和二进制模式打开文件
            $file = fopen($filePath . $fileName, "rb");

            //告诉浏览器这是一个文件流格式的文件
            Header("Content-type: application/octet-stream");
            //请求范围的度量单位
            Header("Accept-Ranges: bytes");
            //Content-Length是指定包含于请求或响应中数据的字节长度
            Header("Accept-Length: " . filesize($filePath . $fileName));
            //用来告诉浏览器，文件是可以当做附件被下载，下载后的文件名称为$file_name该变量的值
            Header("Content-Disposition: attachment; filename=" . $fileName);

            //读取文件内容并直接输出到浏览器
            echo fread($file, filesize($filePath . $fileName));
            fclose($file);
            exit();
        }
    }
}