<?php

namespace yiqiang3344\yii2_lib\helper;


use yii\base\Exception;

/**
 * 图像处理公用类
 * User: sidney
 * Date: 2019/8/29
 */
class Image
{
    /**
     * 图片 base64 压缩并编码
     * @param $image_file
     * @return string
     */
    public static function base64EncodeImage($image_file)
    {
        if (filesize($image_file) / 1024 / 1024 > 1) {
            // 源文件大于 1m 进行压缩
            $tmpImg = self::compress($image_file); // 压缩图片
            $base64_image = 'data:image/jpeg;base64,' . chunk_split(base64_encode($tmpImg));
        } else {
            $image_info = getimagesize($image_file);
            $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
            $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        }

        return $base64_image;
    }

    /**
     * 图片压缩
     * @param $source string 源文件地址
     * @param $quality int 压缩质量
     * @param $destination string 存放压缩后文件地址
     * @return false|string
     * @throws Exception
     */
    public static function compress($source, $quality = 50, $destination = '')
    {
        ini_set('memory_limit', -1); // 以防内存问题
        $info = getimagesize($source);

        if ($info['mime'] == 'image/jpeg') {
            $image = imagecreatefromjpeg($source);
        } elseif ($info['mime'] == 'image/gif') {
            $image = imagecreatefromgif($source);
        } elseif ($info['mime'] == 'image/png') {
            $image = imagecreatefrompng($source);
        } else {
            throw new Exception('不支持的类型:' . $info['mime']);
        }

        if ($destination) {
            // 如果指定保存文件路径
            imagejpeg($image, $destination, $quality);

            return $destination;
        } else {
            // 直接输出 image stream
            ob_start();
            imagejpeg($image, null, $quality);
            $data = ob_get_clean();

            return $data;
        }
    }
}
