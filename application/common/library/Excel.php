<?php
/**
 * Excel操作类
 * @author etf
 * @version 1.0
 */
namespace app\common\library;
use think\facade\Env;
use Think\Exception;

class Excel
{
    /**
     * excel操作
     * @param array data 包含文件属性与数据
     * @param string file_type [EXCEL|CSV]
     * @param string op_type [DOWNLOAD|SAVE]
     * @param string save_path 当op_type为SAVE时，必须传保存路径
     */
    public static function export($data, $file_type='EXCEL', $op_type='DOWNLOAD', $save_path='')
    {
            require_once(Env::get('vendor_path') . 'phpexcel/Classes/PHPExcel.php');
            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->getProperties()->setCreator(empty($data['Creator']) ? "FlashBuy" : $data['Creator'])
                ->setLastModifiedBy(empty($data['ModifiedBy']) ? "FlashBuy" : $data['ModifiedBy'])
                ->setTitle(empty($data['Title']) ? "Office 2007 XLSX Document" : $data['Title'])
                ->setSubject(empty($data['Subject']) ? "Office 2007 XLSX Document" : $data['Subject'])
                ->setDescription(empty($data['Description']) ? "Office 2007 XLSX Document" : $data['Description'])
                ->setKeywords(empty($data['Keywords']) ? "Office 2007 XLSX Document FalshBuy PHP" : $data['Keywords'])
                ->setCategory(empty($data['Category']) ? "Office 2007 XLSX Document FalshBuy PHP" : $data['Category']);

            $hstring = self::setCell($data['first_line']);//"ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $title = empty($data['Title']) ? "Excel File" : $data['Title'];
            $titlename = empty($data['Titlename']) ? "Excel File" : $data['Titlename'];

            $i = 0;
            // 设置第一行[标题]
            if (!empty($data['first_line']) && is_array($data['first_line'])) {
                foreach ($data['first_line'] as $key => $value) {
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($hstring[$i] . "1", $value);
                    $i++;
                }
            }

            // 设置每一列的宽度
            if (!empty($data['col_width']) && is_array($data['col_width'])) {
                foreach ($data['col_width'] as $key => $value) {
                    $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension($key)->setWidth($value);
                }
            }
            // 填充数据
            $j = 0;
            $k = 0;
            $m = 2;
            $op = array();
            foreach ($data['data'] as $key => $value) {
                $k = 0;
                foreach ($value as $s => $v) {
                    if (is_array($v) && !empty($v)) {
                        if ($v['type'] == 'url') {
                            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($hstring[$j + $k] . ($m), $v['value']);
                            $objPHPExcel->setActiveSheetIndex(0)->getCell($hstring[$j + $k] . ($m))->getHyperlink()->setUrl($v['url_value']);
                            $objPHPExcel->setActiveSheetIndex(0)->getCell($hstring[$j + $k] . ($m))->getHyperlink()->setTooltip('Navigate to website');
                            $objPHPExcel->setActiveSheetIndex(0)->getStyle($hstring[$j + $k] . ($m))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                        }
                    } else {
                        if (2 == $m) {
                            $op[$s] = $k;
                        } else {
                            $k = $op[$s];
                        }
                        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($hstring[$j + $k] . ($m), $v);
                    }
                    if (2 == $m) {
                        $k++;
                    }
                }
                $m++;
            }
            if ($op_type == 'DOWNLOAD') {
                $objPHPExcel->getActiveSheet(0)->setTitle($title);
                $objPHPExcel->setActiveSheetIndex(0);

                ob_end_clean();
                ob_start();

                if ($file_type == 'EXCEL') {
                    header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
                    $down_type = 'Excel5';
                } elseif ($file_type == 'CSV') {
                    header('Content-Type:text/csv');
                    $down_type = 'CSV';
                    echo "\xEF\xBB\xBF";//将BOM提前输出，防止多语言乱码
                }

                header("Content-Disposition: attachment;filename={$titlename}");
                header('Cache-Control: max-age=0');
                $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, $down_type);
                $objWriter->save('php://output');
                exit;
            } else if ($op_type == 'SAVE') {
                $objPHPExcel->getActiveSheet(0)->setTitle($title);
                $objPHPExcel->setActiveSheetIndex(0);

                if ($file_type == 'EXCEL') {
                    $down_type = 'Excel5';
                } elseif ($file_type == 'CSV') {
                    $down_type = 'CSV';
                }
                $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, $down_type);
                $objWriter->save($save_path);
            }
    }

    /**
     * excel 数据读取操作
     * @param array file excel文件路径
     */
    public static function read($file, $row=0, $sheet=0){
        ini_set('memory_limit', '512M');
        if(!file_exists($file)){
            return 'Empty file!';
        }

        require_once(Env::get('vendor_path') . 'phpexcel/Classes/PHPExcel.php');
        $PHPExcel   = new \PHPExcel();
        $PHPReader  = new \PHPExcel_Reader_Excel2007();
        if(!$PHPReader->canRead($file)){
            $PHPReader  = new \PHPExcel_Reader_Excel5();
            if(!$PHPReader->canRead($file)){
                $PHPReader  = new \PHPExcel_Reader_CSV();
                $PHPReader->setInputEncoding('GBK'); // CSV模式下兼容中文导入
                if(!$PHPReader->canRead($file)) {
                    return 'Read error, please check the \'PHPExcel\'';
                }
            }
        }
        $PHPExcel   = $PHPReader->load($file);
        $SheetCount = $PHPExcel->getSheetCount();
        $array      = array();
        for($i=$sheet;$i<$SheetCount;$i++){
            $currentSheet       = $PHPExcel->getSheet($i);
            $allColumn          = self::change_excel($currentSheet->getHighestColumn());
            $allRow             = $currentSheet->getHighestRow();
            $array[$i]["Title"] = $currentSheet->getTitle();
            $array[$i]["Cols"]  = $allColumn;
            $array[$i]["Rows"]  = $allRow;

            $arr = array();
            for($currentRow=$row; $currentRow<=$allRow; $currentRow++){
                $row_tmp = array();
                for($currentColumn=0;$currentColumn<$allColumn;$currentColumn++){
                    $row_tmp[$currentColumn]    = $currentSheet->getCellByColumnAndRow($currentColumn,$currentRow)->getValue();
                    if(is_object($row_tmp[$currentColumn])){
                        $row_tmp[$currentColumn]= $row_tmp[$currentColumn]->__toString();
                    }
                }
                $arr[$currentRow] = $row_tmp;
            }
            $array[$i]["Content"] = $arr;
        }
        unset($currentSheet);
        unset($PHPReader);
        unset($PHPExcel);

        return $array;
    }

    /**
     * 计算某行拥有的列数
     * @param string str
     */
    private static function change_excel($str){
        $len = strlen($str)-1;
        $num = 0;
        for($i=$len;$i>=0;$i--){
            $num += (ord($str[$i]) - 64)*pow(26,$len-$i);
        }
        return $num;
    }

    /**
     * 将excel日期格式 转化为类似2014-03-20 00:00:00格式
     * @param string date
     * @param bool time
     */
    public static function excel_time_format($date, $time=false){
        if(is_numeric($date)){
           $jd = GregorianToJD(1, 1, 1970);
           $gregorian = JDToGregorian($jd+intval($date)-25569);
           $date = explode('/',$gregorian);
           $date_str = str_pad($date[2],4,'0', STR_PAD_LEFT)
             ."-".str_pad($date[0],2,'0', STR_PAD_LEFT)
             ."-".str_pad($date[1],2,'0', STR_PAD_LEFT)
             .($time?" 00:00:00":'');
           return $date_str;
        }
        return $date;
    }

    /**
     * 设置列数
     * @access public
     * @return array
     */
    public static function setCell($header) {
        $cell_title = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        if (empty($header)) {
            return $cell_title;
        }

        // 实际总列数,初始化列数
        $diff_cell = count($header) - count($cell_title);
        if ($diff_cell > 0) {
            $i = 1;
            foreach ($cell_title as $letter) {
                foreach ($cell_title as $cell) {
                    if ($i > $diff_cell) {
                        break 2;
                    }

                    $cell_title[] = $letter . $cell;
                    $i++;
                }
            }
        }

        return $cell_title;
    }
}
