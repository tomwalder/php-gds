<?php
/**
 * Represents a GDS Book repository
 *
 * @author Tom Walder <twalder@gmail.com>
 */
class BookStore extends GDS\Store
{

    /**
     * Build and return a Schema object describing the data model
     *
     * @return \GDS\Schema
     */
    protected function buildSchema()
    {
        return (new GDS\Schema('Book'))
            ->addString('title')
            ->addString('author')
            ->addString('isbn', TRUE)
            ->setEntityClass('\\Book');
    }

}