<?php

namespace Site\Converter\Process\Tools;

class PictConverter
{
    private static $isPng = true;
    
    private static function checkImageFormat($sFileType): bool
    {
        if ($sFileType === 'image/png') {
            self::$isPng = true;
            return true;
        } elseif ($sFileType === 'image/jpeg') {
            self::$isPng = false;
            return true;
        } else return false;
    }
    
    /**
     * Создает копию файла в формате webp и возвращает массив параметров исходного
     * и сконвертированного файла
     *
     * @param $aFileParams
     * $aFileParams['SRC'] путь к исходной картинке
     * $aFileParams['FILE_NAME'] название файла
     * $aFileParams['CONTENT_TYPE'] тип файла
     * @param int $iQuality
     * @return array
     */
    public static function getWebpArray(array $aFileParams, int $iQuality = 85): array
    {
        if (self::checkImageFormat($aFileParams['CONTENT_TYPE'])) {
            $aFileParams['SRC'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $aFileParams['SRC']);
            $aFilePath = explode('/', $aFileParams['SRC']);
            
            // исключение название файла из пути
            $aFilePath[count($aFilePath) - 1] = '';
            $sFilePathWithoutFilename = implode('/', $aFilePath);
            
            // изменение директории на папку webp
            if (strpos($sFilePathWithoutFilename, 'upload')) {
                $aFileParams['WEBP_PATH'] = str_replace('upload', 'upload/webp', $sFilePathWithoutFilename);
            } elseif (strpos($sFilePathWithoutFilename, 'images')) {
                $aFileParams['WEBP_PATH'] = str_replace('images', 'images/webp', $sFilePathWithoutFilename);
            }
            
            if (self::$isPng) {
                $aFileParams['WEBP_FILE_NAME'] = str_replace('.png', '.webp', strtolower($aFileParams['FILE_NAME']));
            } else {
                $sFileName = strtolower($aFileParams['FILE_NAME']);
                $bContainsJpg = strpos($sFileName, '.jpg');
                if ($bContainsJpg)
                    $aFileParams['WEBP_FILE_NAME'] = str_replace('.jpg', '.webp', $sFileName);
                else
                    $aFileParams['WEBP_FILE_NAME'] = str_replace('.jpeg', '.webp', $sFileName);
            }
            // создание папки, если таковой не существует
            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $aFileParams['WEBP_PATH'])) {
                mkdir($_SERVER['DOCUMENT_ROOT'] . $aFileParams['WEBP_PATH'], 0777, true);
            }
            // составление пути к фалу
            $aFileParams['WEBP_SRC'] = $aFileParams['WEBP_PATH'] . $aFileParams['WEBP_FILE_NAME'];
            // если не существует файла, создаем
            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $aFileParams['WEBP_SRC'])) {
                if (self::$isPng) {
                    $im = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'] . $aFileParams['SRC']);
                } else {
                    $im = imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'] . $aFileParams['SRC']);
                }
                imagepalettetotruecolor($im);
                imagewebp($im, $_SERVER['DOCUMENT_ROOT'] . $aFileParams['WEBP_SRC'], $iQuality);
                imagedestroy($im);
            }
        }
        return $aFileParams;
    }
    
    /**
     * Создает копию файла в формате webp и возвращает путь к нему
     *
     * @param $sFileSrc
     * @return string
     */
    public static function getWebpSrc($sFileSrc): string
    {
        //получение информации о фале
        $aImageInfo = \CFile::MakeFileArray($sFileSrc);
        //конвертация в формат webp
        $aWebpInfo = self::getWebpArray([
            'SRC' => $aImageInfo['tmp_name'],
            'FILE_NAME' => $aImageInfo['name'],
            'CONTENT_TYPE' => $aImageInfo['type'],
        ]);
        return strval($aWebpInfo['WEBP_SRC']);
    }

    /**
     * Возвращает путь к картинке, отформатированной по полученным параметрам
     *
     * @param $file - идентификатор файла или массив описания файла
     * @param int $width
     * @param int $height
     * @param bool $isProportional
     * @param int $iQuality
     * @return mixed
     */
    public static function resizeImageGetSrc($file, int $width, int $height, bool $isProportional = true, int $iQuality = 70)
    {
        $file = \CFile::ResizeImageGet($file, array('width' => $width, 'height' => $height), ($isProportional ? BX_RESIZE_IMAGE_PROPORTIONAL : BX_RESIZE_IMAGE_EXACT), false, false, false, $iQuality);
        
        return $file['src'];
    }

    /**
     * Возвращает путь к картинке, отформатированной по полученным параметрам
     * и преобразованной в формат webp
     *
     * @param $file - идентификатор файла или массив описания файла
     * @param int $width
     * @param int $height
     * @param bool $isProportional
     * @param int $iQuality
     * @return array
     */
    public static function getResizeWebp($file, int $width, int $height, bool $isProportional = true, int $iQuality = 70): array
    {
        $file['SRC'] = self::resizeImageGetSrc($file, $width, $height, $isProportional, $iQuality);

        return self::getWebpArray($file, $iQuality);
    }

    /**
     * * Возвращает путь к картинке, отформатированной по полученным параметрам
     * и преобразованной в формат webp
     *
     * @param $file - идентификатор файла или массив описания файла
     * @param int $width
     * @param int $height
     * @param bool $isProportional
     * @param int $iQuality
     * @return mixed
     */
    public static function getResizeWebpSrc($file, int $width, int $height, bool $isProportional = true, int $iQuality = 70)
    {
        $file['SRC'] = self::resizeImageGetSrc($file, $width, $height, $isProportional, $iQuality);
        
        $file = self::getWebpArray($file, $iQuality);
        
        return $file['WEBP_SRC'];
    }
}