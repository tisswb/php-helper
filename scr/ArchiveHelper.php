<?php
/**
 * Created by php-helper.
 * User: Tisswb
 * Date: 2020/6/2
 * Time: 14:38
 */

namespace tisswb\helper;

use Exception;
use ZipArchive;

/**
 * Class ArchiveHelper
 * @package tisswb\helper
 */
class ArchiveHelper
{
    /**
     * 压缩文件
     * 找不到的自动忽略
     *
     * @param array|string $file
     * @param $zipName
     * @return bool
     */
    public static function zipFile($file, $zipName)
    {
        $fileList = [];
        if (is_array($file)) {
            $fileList = $file;
        } elseif (is_string($file)) {
            $fileList = [$file];
        }
        try {
            $zip = new ZipArchive();
            if ($zip->open($zipName, ZipArchive::CREATE) === true) {
                foreach ($fileList as $file) {
                    if (!is_file($file)) {
                        continue;
                    }
                    $zip->addFile($file);
                }
                $zip->close();
                return true;
            } else {
                throw new Exception('创建压缩包失败' . $zipName);
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 压缩目录
     *
     * @param $dirPath
     * @param $zipName
     * @return bool
     */
    public static function zipPath($dirPath, $zipName)
    {
        if (!is_dir($dirPath)) {
            return false;
        }
        try {
            $relationArr = [
                $dirPath => [
                    'originName' => $dirPath,
                    'is_dir' => true,
                    'encode' => '',
                    'children' => []
                ]
            ];
            static::modifiyFileName($dirPath, $relationArr[$dirPath]['children']);
            $zip = new ZipArchive();
            if ($zip->open($zipName, ZipArchive::CREATE) === true) {
                static::zipDir(
                    array_keys($relationArr)[0], '', $zip,
                    array_values($relationArr)[0]['children']
                );
                $zip->close();
                static::restoreFileName(
                    array_keys($relationArr)[0],
                    array_values($relationArr)[0]['children']
                );
                return true;
            } else {
                throw new Exception('创建压缩包失败' . $zipName);
            }
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @param $realPath
     * @param $zipPath
     * @param $zip
     * @param $relationArr
     */
    private static function zipDir($realPath, $zipPath, &$zip, $relationArr)
    {
        $subZipPath = empty($zipPath) ? '' : $zipPath . DIRECTORY_SEPARATOR;
        if (is_dir($realPath)) {
            foreach ($relationArr as $k => $v) {
                if ($v['is_dir']) {
                    $zip->addEmptyDir($subZipPath . $v['originName']);
                    static::zipDir(
                        $realPath . DIRECTORY_SEPARATOR . $k,
                        $subZipPath . $v['originName'], $zip, $v['children']
                    );
                } else {
                    $zip->addFile($realPath . DIRECTORY_SEPARATOR . $k, $subZipPath . $k);
                    $zip->deleteName($subZipPath . $v['originName']);
                    $zip->renameName($subZipPath . $k, $subZipPath . $v['originName']);
                }
            }
        }
    }

    /**
     * @param $path
     * @param $relationArr
     * @return bool
     */
    private static function modifiyFileName($path, &$relationArr)
    {
        if (!is_dir($path) || !is_array($relationArr)) {
            return false;
        }
        if ($dh = opendir($path)) {
            $count = 0;
            while (($file = readdir($dh)) !== false) {
                if (in_array($file, ['.', '..', null])) {
                    continue;
                }
                $encode = mb_detect_encoding($file, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);
                if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                    $newName = uniqid($count);
                    $relationArr[$newName] = [
                        'originName' => iconv($encode, 'UTF-8', $file),
                        'is_dir' => true,
                        'encode' => $encode,
                        'children' => []
                    ];
                    rename(
                        $path . DIRECTORY_SEPARATOR . $file,
                        $path . DIRECTORY_SEPARATOR . $newName
                    );
                    static::modifiyFileName(
                        $path . DIRECTORY_SEPARATOR . $newName,
                        $relationArr[$newName]['children']
                    );
                    $count++;
                } else {
                    $extension = strchr($file, '.');
                    $newName = uniqid($count);
                    $relationArr[$newName . $extension] = [
                        'originName' => iconv($encode, 'UTF-8', $file),
                        'is_dir' => false,
                        'encode' => $encode,
                        'children' => []
                    ];
                    rename(
                        $path . DIRECTORY_SEPARATOR . $file,
                        $path . DIRECTORY_SEPARATOR . $newName . $extension
                    );
                    $count++;
                }
            }
        }
        return true;
    }

    /**
     * @param $path
     * @param $relationArr
     */
    private static function restoreFileName($path, $relationArr)
    {
        foreach ($relationArr as $k => $v) {
            if (!empty($v['children'])) {
                static::restoreFileName($path . DIRECTORY_SEPARATOR . $k, $v['children']);
                rename(
                    $path . DIRECTORY_SEPARATOR . $k,
                    iconv('UTF-8', $v['encode'], $path . DIRECTORY_SEPARATOR . $v['originName'])
                );
            } else {
                rename(
                    $path . DIRECTORY_SEPARATOR . $k,
                    iconv('UTF-8', $v['encode'], $path . DIRECTORY_SEPARATOR . $v['originName'])
                );
            }
        }
    }
}