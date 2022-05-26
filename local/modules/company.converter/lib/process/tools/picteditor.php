<?php
namespace Site\Converter\Process\Tools;

class PictEditor
{
    /**
     * Наложение водяного знака на картинку в правом нижнем углу. Накладывается
     * файл watermark.png по пути _server/upload/watermark.png
     *
     * @param $sImagePath
     * @return void
     */
    public static function PostWaterMark(&$sImagePath) : void
    {
        //Получаем папку для загрузок
        $_upload_dir = \COption::GetOptionString('main', 'upload_dir');
        
        //Открываем картинку для наложения
        $wmTarget = $_upload_dir . '/watermark.png';
        $resultImage = imagecreatefromjpeg($sImagePath);
        
        imagealphablending($resultImage, true);
        
        //Создаем временную картинку
        $sImagePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $_upload_dir . '/tmp/' . md5(microtime()) . '.jpg';
        
        //Загружаем PNG ватермарка
        $finalWaterMarkImage = imagecreatefrompng($wmTarget);
        
        //Узнаем размеры картинки водяного знака
        $finalWaterMarkWidth = imagesx($finalWaterMarkImage);
        $finalWaterMarkHeight = imagesy($finalWaterMarkImage);
        
        //Узнаем размеры загружаемой картинки
        $imagesizeW = imagesx($resultImage);
        $imagesizeH = imagesy($resultImage);
        
        //Пихаем водяной знак в нижний правый угол картинки
        imagecopy($resultImage, $finalWaterMarkImage, $imagesizeW - $finalWaterMarkWidth, $imagesizeH - $finalWaterMarkHeight, 0, 0, $finalWaterMarkWidth, $finalWaterMarkHeight);
        
        imagealphablending($resultImage, false);
        imagesavealpha($resultImage, true);
        imagejpeg($resultImage, $sImagePath, 100);
        imagedestroy($resultImage);
        imagedestroy($finalWaterMarkImage);
    }
    
    /**
     * Размытие части картинки, ширина и высота области размытия считается от правого нижнего угла
     * 
     * @param $imagePath - путь к картинке
     * @param $width - ширина блюра
     * @param $height - высота блюра
     * @param $blurIndex - степень размытия: 1-легкая 2-средняя 3-высокая, выше - хуже
     * @return void
     */
    public static function BlurPicturePart(&$imagePath, $width, $height, $blurIndex)
    {
        $_upload_dir = \COption::GetOptionString('main', 'upload_dir');
        $image = self::CreateImageSourceByExtension($imagePath);
        
        if (!$image) {
            return;
        }
        
        //Создаем временную картинку
        $imagePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $_upload_dir . '/tmp/' . md5(microtime()) . '.jpg';
        
        //Узнаем размеры загружаемой картинки
        $imagesizeW = imagesx($image);
        $imagesizeH = imagesy($image);
        
        // вырезаем часть, которую хотим заблюрить 
        $imagePartToBlur = self::GetCroppedImage($image, $imagesizeW - $width, $imagesizeH - $height, $width, $height);
        $bluredImagePart = self::Blur($imagePartToBlur, $blurIndex);
        //Пихаем водяной знак в нижний правый угол картинки
        imagecopy($image, $bluredImagePart, $imagesizeW - $width, $imagesizeH - $height, 0, 0, $width, $height);
        
        imagejpeg($image, $imagePath, 100);
        imagedestroy($image);
        imagedestroy($bluredImagePart);
    }
    
    /**
     * Получение части исходного изображения 
     * 
     * @param $imageSource - сурс изображения
     * @param $x - откуда начинаем обрезать картинку по x
     * @param $y - откуда начинаем обрезать картинку по y
     * @param $width - ширина вырезаемой картинки
     * @param $height - высота вырезаемой картинки
     * @return false|GdImage|resource
     */
    public static function GetCroppedImage($imageSource, $x, $y, $width, $height)
    {
        // Проверка на наличие изображений
        if (!$imageSource) {
            return false;
        }
        
        $croppedImg = imagecreatetruecolor($width, $height);
        
        // сохранение прозрачности (для PNG и GIF)
        imagealphablending($croppedImg, false);
        imagesavealpha($croppedImg, true);
        
        imagecopy($croppedImg, $imageSource, 0, 0, $x, $y, $width, $height);
        
        return $croppedImg;
    }
    
    /**
     * Создание source изображения в зависимости от его расширения
     * 
     * @param $imagePath
     * @return false|GdImage|resource
     */
    public static function CreateImageSourceByExtension($imagePath)
    {
        $sImageExtension = pathinfo($imagePath, PATHINFO_EXTENSION);
        switch ($sImageExtension) {
            case 'gif':
                $imageSource = imagecreatefromgif($imagePath);
                break;
            case 'jpg':
            case 'jpeg':
                $imageSource = imagecreatefromjpeg($imagePath);
                break;
            case 'png':
                $imageSource = imagecreatefrompng($imagePath);
                break;
            default:
                return false;
        }
        
        unset($sImageExtension);
        return $imageSource;
    }
    
    /**
     * Блюр изображения с настройкой силы блюра
     * 
     * @param $gdImageResource
     * @param int $blurFactor
     * @return mixed
     */
    public static function Blur($gdImageResource, int $blurFactor = 3)
    {
        $blurFactor = round($blurFactor);
        
        $originalWidth = imagesx($gdImageResource);
        $originalHeight = imagesy($gdImageResource);
        
        $smallestWidth = ceil($originalWidth * pow(0.5, $blurFactor));
        $smallestHeight = ceil($originalHeight * pow(0.5, $blurFactor));
        
        // для первого запуска предыдущее изображение является исходным
        $prevImage = $gdImageResource;
        $prevWidth = $originalWidth;
        $prevHeight = $originalHeight;
        
        // постепенно увеличиваем и уменьшаем масштаб, полностью размывая
        for ($i = 0; $i < $blurFactor; $i++) {
            // determine dimensions of next image
            $nextWidth = $smallestWidth * pow(2, $i);
            $nextHeight = $smallestHeight * pow(2, $i);
            
            // изменяем прошлое изображение до нового размера
            $nextImage = imagecreatetruecolor($nextWidth, $nextHeight);
            imagecopyresized($nextImage, $prevImage, 0, 0, 0, 0, $nextWidth, $nextHeight, $prevWidth, $prevHeight);
            
            // применяем блюр-фильтр
            imagefilter($nextImage, IMG_FILTER_GAUSSIAN_BLUR);
            
            // теперь изображение делаем прошлым для следующего шага итерации
            $prevImage = $nextImage;
            $prevWidth = $nextWidth;
            $prevHeight = $nextHeight;
        }
        
        // уменьшаем до исходного состояния и ллюрим еще раз
        imagecopyresized($gdImageResource, $nextImage, 0, 0, 0, 0, $originalWidth, $originalHeight, $nextWidth, $nextHeight);
        imagefilter($gdImageResource, IMG_FILTER_GAUSSIAN_BLUR);
        
        // чистим память
        imagedestroy($prevImage);
        
        return $gdImageResource;
    }
    
    /**
     * Метод для очищения временной папки
     *
     * @return bool
     */
    public static function Clear() : bool
    {
        $_upload_dir = \COption::GetOptionString('main', 'upload_dir');
        $_WFILE = glob($_SERVER['DOCUMENT_ROOT'] . '/' . $_upload_dir . '/tmp/*.jpg');
        foreach ($_WFILE as $_file)
            unlink($_file);
        return true;
    }
}