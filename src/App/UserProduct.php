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

class UserProduct extends \Spot\Entity
{
    protected static $table = "users_products";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "user_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "product_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "pack_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "base_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "price" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "created" => ["type" => "datetime", "value" => new \DateTime()],
            "updated" => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'user' => $mapper->belongsTo($entity, 'App\User', 'user_id'),
            'base' => $mapper->belongsTo($entity, 'App\ProductBase', 'base_id'),
            'pack' => $mapper->belongsTo($entity, 'App\ProductPack', 'pack_id'),
            'product' => $mapper->belongsTo($entity, 'App\Product', 'product_id')
            //'beliefs' => $mapper->hasMany($entity, 'App\Belief', 'refocus_id')->order(['created' => 'ASC'])
        ];
    }
    
    public function transform(UserProduct $entity)
    {
        return [
            "id" => (integer) $entity->id ?: "",
            "fulltitle" => implode(' ',[(string) $entity->product->title ?: "",(string) $entity->pack->title ?: ""]),
            "price" => (float) $entity->price ?: "",
            "product" => (object) [
                'id' => (integer) $entity->product_id ?: null,
                'title' => (string) $entity->product->title ?: "",
                'code' => (string) $entity->product->code ?: ""
            ],
            "pack" => (object) [
                'id' => (integer) $entity->pack_id ?: null,
                'title' => (string) $entity->pack->title ?: "",
            ],
            "base" => (object) [
                'id' => (integer) $entity->base_id ?: null,
                'title' => (string) $entity->base->title ?: "",
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
            "id" => null,
            "price" => null
        ]);
    }
}
