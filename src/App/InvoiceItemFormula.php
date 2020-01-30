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

class InvoiceItemFormula extends \Spot\Entity
{
    protected static $table = "invoices_items_formulas";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "invoice_item_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "code" => ["type" => "string", "length" => 100],
            "hexcode" => ["type" => "string", "length" => 10],
            "description" => ["type" => "text"],
            "density" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "amount" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'invoice_item' => $mapper->belongsTo($entity, 'App\InvoiceItem', 'invoice_item_id')
        ];
    }

    public function transform(InvoiceItemFormula $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "title" => (string) $entity->title ?: "",
            "quantity" => (float) $entity->quantity ?: ""
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
