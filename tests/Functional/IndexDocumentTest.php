<?php

namespace Functional;

use Functional\MappingModel;
use Mockery as m;

/**
 * @group functional
 */
class IndexDocumentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        m::close();

        parent::tearDown();
    }

    public function testDocumentCreation()
    {
        // Create a parent model.
        $parentAttributes = [
            'a1' => 'foo',
            'a2' => 1,
            'a3' => \Carbon\Carbon::now(),
        ];
        $parent = new IndexDocumentParentModel;
        $parent->setRawAttributes($parentAttributes);

        // Create two related models.
        $related1Attributes = [
            'b1' => 'bar1',
        ];
        $related1 = new IndexDocumentRelatedModel;
        $related1->setRawAttributes($related1Attributes);

        $related2Attributes = [
            'b1' => 'bar2',
        ];
        $related2 = new IndexDocumentRelatedModel;
        $related2->setRawAttributes($related2Attributes);

        // Set the relations on the parent model.
        $parent->setRelation('related1', $related1);
        $parent->setRelation('related2', $related2);
        $parent->setIndexRelations(['related1']);

        // Generate the index document.
        $document = $parent->toIndexDocument();

        $this->assertArrayHasKey('a1', $document);
        $this->assertArrayHasKey('a2', $document);
        $this->assertArrayHasKey('a3', $document);

        $this->assertArrayHasKey('related1', $document);
        $this->assertArrayNotHasKey('related2', $document);
    }

    public function testDocumentVisibility()
    {
        // Create a parent model.
        $parentAttributes = [
            'a1' => 'foo',
            'a2' => 1,
            'a3' => \Carbon\Carbon::now(),
        ];
        $parent = new IndexDocumentParentModel;
        $parent->setRawAttributes($parentAttributes);

        // Create two related models.
        $relatedAttributes = [
            'b1' => 'bar1',
            'b2' => 5,
        ];
        $related = new IndexDocumentRelatedModel;
        $related->setRawAttributes($relatedAttributes);

        // Set the relations on the parent model.
        $parent->setRelation('related', $related);
        $parent->setIndexRelations(['related']);

        // Set hidden attributes.
        $parentHidden = [ 'a2' ];
        $parent->setHidden($parentHidden);

        $relatedHidden = [ 'b1' ];
        $related->setHidden($relatedHidden);

        // Generate the index document.
        $document = $parent->toIndexDocument();

        $this->assertArrayHasKey('a1', $document);
        $this->assertArrayNotHasKey('a2', $document);
        $this->assertArrayHasKey('a3', $document);
        $this->assertEquals($parentHidden, $parent->getHidden());

        $this->assertArrayHasKey('related', $document);
        $this->assertArrayNotHasKey('b1', $document['related']);
        $this->assertArrayHasKey('b2', $document['related']);
        $this->assertEquals($relatedHidden, $related->getHidden());
    }
}

class IndexDocumentParentModel extends MappingModel
{
    protected $table = 'phpunit_IndexDocumentParentModel';
    protected $dates = [ 'a3'];
    protected $casts = [ 'a2' => 'int'];
    protected $related = [];

    public function load($relations)
    {
        return $this;
    }
}

class IndexDocumentRelatedModel extends MappingModel
{
    protected $table = 'phpunit_IndexDocumentRelatedModel';
    protected $dates = [ 'b3'];
    protected $casts = [ 'b2' => 'int'];

}
