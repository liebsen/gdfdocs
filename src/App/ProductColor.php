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

class ProductColor extends \Spot\Entity
{
    protected static $table = "products_colors";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "user_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "product_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "base_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "title" => ["type" => "string", "length" => 255],
            "description" => ["type" => "text"],
            "enabled"   => ["type" => "boolean", "value" => true],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'user' => $mapper->belongsTo($entity, 'App\User', 'user_id'),
            'product' => $mapper->belongsTo($entity, 'App\Product', 'product_id'),
            'base' => $mapper->belongsTo($entity, 'App\ProductBase', 'base_id')
        ];
    }
    
    public function transform(ProductColor $entity)
    {
        return [
            "id" => (integer) $entity->id ?: "",
            "title" => (string) $entity->title,
            "product_id" => (integer) $entity->product_id,
            "code" => (string) $entity->product->code ?: "",
            "hexcode" => (string) $entity->product->hexcode?: "",
            "created" => (string) date('j/n/y H:i',$entity->timestamp()),
            "base" => (object) [
                'id' => (integer) $entity->base_id,
                "code" => (string) $entity->base->title ?: ""
            ]
        ];
    }

    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "title" => null,
            "image" => null,
            "enabled" => null
        ]);
    }
}
