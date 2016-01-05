<?php
/**
 * Copyright 2015 Tom Walder
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
namespace GDS;

class KeyDebugger
{

    /**
     * Convert the key to a useful human readable format
     *
     * @param KeyInterface $obj_key
     * @return string
     */
    public function renderKeyChain(KeyInterface $obj_key)
    {
        return '[' . $this->keyAsString($obj_key) . ']';
    }

    /**
     * Convert the key to a useful human readable format
     *
     * @param KeyInterface $obj_key
     * @return string
     */
    private function keyAsString(KeyInterface $obj_key)
    {
        $arr_chain = [];
        $mix_ancestry = $obj_key->getAncestry();
        if(is_array($mix_ancestry)) {
            foreach($mix_ancestry as $mix_part) {
                if($mix_part instanceof Key) {
                    $arr_chain[] = $this->keyAsString($mix_part);
                } else {
                    $arr_chain[] = 'error';
                }
            }
        } elseif ($mix_ancestry instanceof Key) {
            $arr_chain[] = $this->keyAsString($mix_ancestry);
        }

        // Tack on 'this' key at the end of the chain
        $arr_parts = [];
        if(null !== $obj_key->getKind()) {
            $arr_parts[] = 'Kind:' . $obj_key->getKind();
        }
        if(null !== $obj_key->getKeyId()) {
            $arr_parts[] = 'Id:' . $obj_key->getKeyId();
        }
        if(null !== $obj_key->getKeyName()) {
            $arr_parts[] = 'Name:' . $obj_key->getKeyName();
        }
        $arr_chain[] = '(' . implode(', ', $arr_parts) . ')';

        return implode('->', $arr_chain);
    }

}