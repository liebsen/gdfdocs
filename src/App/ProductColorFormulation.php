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

class ProductColorFormulation extends \Spot\Entity
{
    protected static $table = "products_colors_formulations";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "user_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "product_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "color_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "base_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "pack_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "amount" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "quantity" => ["type" => "integer", "value" => 0],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'user' => $mapper->belongsTo($entity, 'App\User', 'user_id'),
            'product' => $mapper->belongsTo($entity, 'App\Product', 'product_id'),
            'color' => $mapper->belongsTo($entity, 'App\ProductColor', 'color_id'),
            'base' => $mapper->belongsTo($entity, 'App\ProductBase', 'base_id'),
            'pack' => $mapper->belongsTo($entity, 'App\ProductPack', 'pack_id')
        ];
    }
    
    public function transform(ProductColorFormulation $entity)
    {
        return [
            "id" => (integer) $entity->id ?: "",
            "amount" => (float) number_format($entity->amount,2,',','.') ?: 0,
            "quantity" => (float) $entity->quantity ?: 0,
            "user" => (object) [
                'id' => (integer) $entity->user_id ?: "",
                'code' => (string) $entity->user->first_name?: ""
            ],
            "product" => (object) [
                'id' => (integer) $entity->product_id ?: "",
                'title' => (string) $entity->product->code?: ""
            ],
            "color" => (object) [
                'id' => (integer) $entity->color_id ?: "",
                'title' => (string) $entity->color->title?: ""
            ],
            "base" => (object) [
                'id' => (integer) $entity->base_id ?: "",
                'title' => (string) $entity->base->code?: ""
            ],
            "pack" => (object) [
                'id' => (integer) $entity->pack_id ?: "",
                'title' => (string) $entity->pack->title?: ""
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
