<?php

/*
 * This file is part of the Slim API skeleton package
 *
 * Copyright (c) 2016 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-api-skeleton
 *
 */

namespace App;

use Spot\EntityInterface as Entity;
use Spot\MapperInterface as Mapper;
use Spot\EventEmitter;
use Tuupola\Base62;
use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

class UserColorant extends \Spot\Entity
{
    protected static $table = "users_colorants";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "user_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "colorant_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "price" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "created" => ["type" => "datetime", "value" => new \DateTime()],
            "updated" => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'user' => $mapper->belongsTo($entity, 'App\User', 'user_id'),
            'colorant' => $mapper->belongsTo($entity, 'App\ProductColorant', 'colorant_id')
        ];
    }
    
    public function transform(UserColorant $entity)
    {
        return [
            "id" => (integer) $entity->id ?: "",
            "price" => (float) $entity->price ?: ""
        ];
    }

    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "id" => null,
            "price" => null
        ]);
    }
}
