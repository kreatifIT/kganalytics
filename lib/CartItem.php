<?php

/**
 * This file is part of the Kreatif\Project package.
 *
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 29.05.21
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kreatif\kganalytics;


class CartItem
{
    protected string $id;
    protected string $name;
    protected array  $properties;
    protected ?float $price    = null;
    protected ?int   $quantity = null;

    public function __construct(string $id, string $name, array $properties = [])
    {
        if (isset($properties['price'])) {
            $this->setPrice($properties['price']);
            unset($properties['price']);
        }
        if (isset($properties['quantity'])) {
            $this->setQuantity($properties['quantity']);
            unset($properties['quantity']);
        }

        $this->id         = $id;
        $this->name       = $name;
        $this->properties = $properties;
    }

    public function getEventProperties()
    {
        $this->properties['item_id']   = $this->id;
        $this->properties['item_name'] = $this->name;
        $this->properties['price']     = $this->price;
        $this->properties['quantity']  = $this->quantity;
        return $this->properties;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }
}