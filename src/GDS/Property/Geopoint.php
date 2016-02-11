<?php
/**
 * Copyright 2016 Tom Walder
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace GDS\Property;

/**
 * Geopoint Property
 *
 * @author Tom Walder <tom@docnet.nu>
 * @package GDS
 */
class Geopoint implements \ArrayAccess
{

    private $flt_lat = 0.0;

    private $flt_lon = 0.0;

    public function __construct($latitude = 0.0, $longitude = 0.0)
    {
        $this->flt_lat = (float)$latitude;
        $this->flt_lon = (float)$longitude;
    }

    public function getLatitude()
    {
        return $this->flt_lat;
    }

    public function getLongitude()
    {
        return $this->flt_lon;
    }

    public function setLatitude($latitude)
    {
        $this->flt_lat = (float)$latitude;
        return $this;
    }

    public function setLongitude($longitude)
    {
        $this->flt_lon = (float)$longitude;
        return $this;
    }

    /**
     * ArrayAccess
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return (0 === $offset || 1 === $offset);
    }

    /**
     * ArrayAccess
     *
     * @param mixed $offset
     * @return float
     */
    public function offsetGet($offset)
    {
        if(0 === $offset) {
            return $this->getLatitude();
        }
        if(1 === $offset) {
            return $this->getLongitude();
        }
        throw new \UnexpectedValueException("Cannot get Geopoint data with offset [{$offset}]");
    }

    /**
     * ArrayAccess
     *
     * @param mixed $offset
     * @param mixed $value
     * @return $this|Geopoint
     */
    public function offsetSet($offset, $value)
    {
        if(0 === $offset) {
            $this->setLatitude($value);
            return;
        }
        if(1 === $offset) {
            $this->setLongitude($value);
            return;
        }
        throw new \UnexpectedValueException("Cannot set Geopoint data with offset [{$offset}]");
    }

    /**
     * ArrayAccess
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        if(0 === $offset) {
            $this->setLatitude(0.0);
            return;
        }
        if(1 === $offset) {
            $this->setLongitude(0.0);
            return;
        }
        throw new \UnexpectedValueException("Cannot unset Geopoint data with offset [{$offset}]");
    }
}