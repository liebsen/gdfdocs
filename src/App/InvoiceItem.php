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

class InvoiceItem extends \Spot\Entity
{
    protected static $table = "invoices_items";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "invoice_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "color_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "base_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "pack_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "product_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "uuid" => ["type" => "string", "length" => 50],
            "title" => ["type" => "string", "length" => 200],
            "quantity" => ["type" => "integer", "value" => 0],
            "m2" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "kg" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "performance" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "unit_price" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "amount" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "comments" => ["type" => "text"],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'product' => $mapper->belongsTo($entity, 'App\Product', 'product_id'),
            'color' => $mapper->belongsTo($entity, 'App\ProductColor', 'color_id'),
            'pack' => $mapper->belongsTo($entity, 'App\ProductPack', 'pack_id'),
            'base' => $mapper->belongsTo($entity, 'App\ProductBase', 'base_id'),
            'formulas' => $mapper->hasMany($entity, 'App\InvoiceItemFormula', 'invoice_item_id'),
            'invoice' => $mapper->belongsTo($entity, 'App\Invoice', 'invoice_id')
        ];
    }

    public function transform(InvoiceItem $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "uuid" => (string) $entity->uuid ?: null,
            "m2" => (float) $entity->m2 ?: 0,
            "unit_price" => (float) number_format($entity->unit_price,2,',','.') ?: 0,
            "amount" => (float) number_format($entity->amount,2,',','.') ?: 0,
            "quantity" => (float) $entity->quantity ?: 0,
            "performance" => (float) $entity->performance ?: 0,
            "comments" => (string) $entity->comments ?: "",
            "product" => (object) [ 
                'id' => $entity->product_id,
                'title' => $entity->product->title,
                'pic' => $entity->product->pic1_url,
                'code' => $entity->product->code,
                'colorant_unit' => $entity->product->colorant_unit,
                'hexcode' => $entity->product->hexcode
            ],
            "pack" => (object) [ 
                'id' => $entity->pack_id,
                'title' => $entity->pack->title,
                'kg' => $entity->pack->kg
            ],
            "base" => (object) [ 
                'id' => $entity->base_id,
                'title' => $entity->base->title
            ],
            "color" => (object) [ 
                'id' => $entity->color_id,
                'title' => $entity->color->title
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
            "description" => null
        ]);
    }
}
