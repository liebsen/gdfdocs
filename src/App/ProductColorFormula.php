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

class ProductColorFormula extends \Spot\Entity
{
    protected static $table = "products_colors_formulas";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "color_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "colorant_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "unit"   => ["type" => "string", "length" => 5],
            "amount" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'color' => $mapper->belongsTo($entity, 'App\ProductColor', 'color_id'),
            'colorant' => $mapper->belongsTo($entity, 'App\ProductColorant', 'colorant_id')
        ];
    }
    
    public function transform(ProductColorFormula $entity)
    {
        return [
            "id" => (integer) $entity->id ?: "",
            "title" => (string) $entity->title?: "",
            "description" => (string) $entity->colorant->description?: "",
            "unit" => (string) $entity->unit?: "",
            "amount" => (float) $entity->amount?: "",
            "performance" => (float) $entity->texture->performance,
            "color" => (object) [
                'id' => (integer) $entity->color_id ?: "",
                'title' => (string) $entity->color->code?: ""
            ],
            "colorant" => (object) [
                'id' => (integer) $entity->colorant_id ?: "",
                'code' => (string) $entity->colorant->code?: ""
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
