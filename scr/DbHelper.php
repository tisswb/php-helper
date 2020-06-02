<?php
/**
 * Created by php-helper.
 * User: Tisswb
 * Date: 2020/6/2
 * Time: 14:40
 */

namespace tisswb\helper;

use yii\db\ActiveRecord;

/**
 * Class DbHelper
 * @package tisswb\helper
 */
class DbHelper
{
    /**
     * @param string $className 模型类
     * @param array $data 数组
     * @param string $field 依赖字段
     * @param array $params
     * @return int
     */
    public static function batchUpdate($className, $data, $field, $params = [])
    {
        /** @var ActiveRecord $className */
        $tableName = $className::getDb()->quoteSql($className::tableName());
        $updateSql = static::createSql($tableName, $data, $field, $params);
        return $className::getDb()->createCommand($updateSql)->execute();
    }

    /**
     * 批量更新函数
     * @param string $table
     * @param array $data 待更新的数据，二维数组格式
     * @param string $field string 值不同的条件，默认为id
     * @param array $params array 值相同的条件，键值对应的一维数组
     * @return bool|string
     */
    private static function createSql($table, $data, $field, $params = [])
    {
        if (!is_array($data) || !$field || !is_array($params)) {
            return false;
        }
        $updates = static::parseUpdate($data, $field);
        $where = static::parseParams($params);
        $fields = array_column($data, $field);
        $fields = implode(',', array_map(function ($value) {
            return "'" . $value . "'";
        }, $fields));
        return sprintf('UPDATE %s SET %s WHERE `%s` IN (%s) %s', $table, $updates, $field, $fields,
            $where);
    }

    /**
     * 将二维数组转换成CASE WHEN THEN的批量更新条件
     * @param $data array 二维数组
     * @param $field string 列名
     * @return string sql语句
     */
    private static function parseUpdate($data, $field)
    {
        $sql = '';
        $keys = array_keys(current($data));
        foreach ($keys as $column) {
            if ($column == $field) {
                continue;
            }
            $sql .= sprintf("`%s` = CASE `%s` \n", $column, $field);
            foreach ($data as $line) {
                $sql .= sprintf("WHEN '%s' THEN '%s' \n", $line[$field], $line[$column]);
            }
            $sql .= "END,";
        }
        return rtrim($sql, ',');
    }

    /**
     * 解析where条件
     * @param $params
     * @return array|string
     */
    private static function parseParams($params)
    {
        $where = [];
        foreach ($params as $key => $value) {
            $where[] = sprintf("`%s` = '%s'", $key, $value);
        }
        return $where ? ' AND ' . implode(' AND ', $where) : '';
    }
}