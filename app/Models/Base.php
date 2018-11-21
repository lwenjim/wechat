<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/2 0002
 * Time: 19:31
 */

namespace App\Models;


class Base extends Model
{
    public function useParamForEqWhere($fields, $params)
    {
        $model = $this;
        if (is_string($fields)) {
            $model = $model->where($fields, $params);
        } elseif (is_array($fields)) {
            foreach ($fields as $field) {
                if (isset($params[$field]) && !empty($params[$field])) {
                    $model = $model->useParamForEqWhere($field, $params[$field]);
                }
            }
        }
        return $model;
    }

    public function useParamForLeftLikeWhere($fields, $params)
    {
        $model = $this;
        if (is_string($fields)) {
            $model = $model->where($fields, 'like', $params . '%');
        } elseif (is_array($fields)) {
            foreach ($fields as $field) {
                if (isset($params[$field]) && !empty($params[$field])) {
                    $model = $model->useParamForLeftLikeWhere($field, $params[$field]);
                }
            }
        }
        return $model;
    }

    public function useParamForInWhere($fields, $params)
    {
        $model = $this;
        if (is_string($fields)) {
            $model = $model->whereIn($fields, $params);
        } elseif (is_array($fields)) {
            foreach ($fields as $field) {
                if (isset($params[$field]) && !empty($params[$field])) {
                    $model = $model->useParamForInWhere($field, $params[$field]);
                }
            }
        }
        return $model;
    }
}