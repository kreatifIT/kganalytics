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


class SearchItem extends CartItem
{


    public function getEventProperties()
    {
        $this->properties['item_id']   = $this->id;
        $this->properties['item_name'] = $this->name;
        $this->properties['price']     = $this->price;
        $this->properties['quantity']  = $this->quantity;
        return $this->properties;
    }
}