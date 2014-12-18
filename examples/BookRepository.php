<?php
/**
 * Represents a GDS Book repository
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class BookRepository extends GDS\Repository
{

    /**
     * Get the configuration for this GDS Model
     *
     * @return \GDS\Schema
     */
    protected function getSchema()
    {
        return (new GDS\Schema('Book'))
            ->addField('name')
            ->addField('author')
            ->addField('isbn');
    }

    /**
     * Create a new instance of the Model class
     *
     * @return \GDS\Model
     */
    public function createEntity()
    {
        return new Book();
    }

}