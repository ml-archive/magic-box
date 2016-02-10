<?php

namespace Fuzz\MagicBox\Tests;

use Fuzz\MagicBox\Filter;
use Fuzz\MagicBox\Tests\Models\User;
use Fuzz\MagicBox\Tests\Seeds\FilterDataSeeder;
use Illuminate\Support\Facades\Schema;

class FilterTest extends DBTestCase
{
	/**
	 * Number of users seeder creates
	 *
	 * @var int
	 */
	public $user_count = 4;

	/**
	 * Set up and seed the database with seed data
	 *
	 * @return void
	 */
	public function setUp()
	{
		parent::setUp();

		$this->artisan->call(
			'db:seed', [
				'--class' => FilterDataSeeder::class
			]
		);
	}

	/**
	 * Retrieve a query for the model
	 *
	 * @param string $model_class
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	private function getQuery($model_class)
	{
		return $model_class::query();
	}

	private function getModelColumns($model_class)
	{
		$temp_instance = new $model_class;

		return Schema::getColumnListing($temp_instance->getTable());
	}

	public function testItModifiesQuery()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['name' => '^lskywalker'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$original_query = clone $query;
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$this->assertNotSame($original_query, $query);
	}

	public function testItStartsWith()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['username' => '^lskywalker'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		$this->assertEquals('lskywalker@galaxyfarfaraway.com', $results->first()->username);
	}

	public function testItFiltersOrStartsWith()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'username' => '^lskywalker',
			'or'       => ['username' => '^solocup']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));

		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lskywalker@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com']));
		}
	}

	public function testItEndsWith()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['name' => '$gana'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		$this->assertEquals('lorgana@galaxyfarfaraway.com', $results->first()->username);
	}

	public function testItFiltersOrEndsWith()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'name' => '$gana',
			'or'       => ['name' => '$olo']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com']));
		}
	}

	public function testItContains()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['username' => '~clava'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		$this->assertEquals('chewbaclava@galaxyfarfaraway.com', $results->first()->username);
	}

	public function testItFiltersOrContains()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'username' => '~skywalker',
			'or'       => ['username' => '~clava']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lskywalker@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItIsLessThan()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['times_captured' => '<1'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		$this->assertEquals('chewbaclava@galaxyfarfaraway.com', $results->first()->username);
	}

	public function testItFiltersOrIsLessThan()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'times_captured' => '<1',
			'or'       => ['times_captured' => '<3']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['solocup@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItIsGreaterThan()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['hands' => '>1'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['solocup@galaxyfarfaraway.com', 'lorgana@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersOrIsGreaterThan()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'hands' => '>1',
			'or'       => ['hands' => '>0']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['solocup@galaxyfarfaraway.com', 'lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItIsLessThanOrEquals()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['hands' => '<=1'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['chewbaclava@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersOrIsLessThanOrEquals()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'times_captured' => '<=2',
			'or'       => ['times_captured' => '<=5']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['chewbaclava@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com']));
		}
	}

	public function testItIsGreaterThanOrEquals()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'times_captured' => '>=5',
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersOrIsGreaterThanOrEquals()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'times_captured' => '>=5',
			'or'       => ['times_captured' => '>=3']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItEqualsString()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'username' => '=lskywalker@galaxyfarfaraway.com',
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItEqualsInt()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'times_captured' => '=6',
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersOrEqualsString()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'username' => '=lskywalker@galaxyfarfaraway.com',
			'or'       => ['username' => '=lorgana@galaxyfarfaraway.com']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersOrEqualsInt()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'times_captured' => '=4',
			'or'       => ['times_captured' => '=6']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItNotEqualsString()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['username' => '!=lorgana@galaxyfarfaraway.com'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue($result->username !== 'lorgana@galaxyfarfaraway.com');
		}
	}

	public function testItNotEqualsInt()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['times_captured' => '!=4'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue($result->username !== 'lskywalker@galaxyfarfaraway.com');
		}
	}

	public function testItFiltersOrNotEqualString()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'username' => '=lorgana@galaxyfarfaraway.com',
			'or'       => ['username' => '!=lskywalker@galaxyfarfaraway.com']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			// The only one we shouldn't get is lskywalker@galaxyfarfaraway.com'
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersOrNotEqualInt()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'times_captured' => '=6',
			'or'       => ['times_captured' => '!=4']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			// The only one we shouldn't get is lskywalker@galaxyfarfaraway.com'
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItNotNull()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['occupation' => 'NOT_NULL'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lskywalker@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersOrNotNull()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'username' => '~lskywalker',
			'or'       => ['occupation' => 'NOT_NULL']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lskywalker@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItNull()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'occupation' => 'NULL',
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com',]));
		}
	}

	public function testItFiltersOrNull()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'occupation' => '=Jedi',
			'or' => [
				'occupation' => 'NULL',
			],
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItInString()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['name' => '[Chewbacca,Leia Organa,Luke Skywalker]'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItInInt()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['times_captured' => '[0,4,6]'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersOrInString()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'name' => '[Chewbacca,Luke Skywalker]',
			'or'       => ['name' => '[Luke Skywalker,Leia Organa]']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersOrInInt()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'times_captured' => '[0,4]',
			'or'       => ['times_captured' => '[4,6]']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItNotInString()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'name' => '![Leia Organa,Chewbacca]',
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['solocup@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com',]));
		}
	}

	public function testItNotInInt()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'times_captured' => '![6,0]',
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['solocup@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com',]));
		}
	}

	public function testItFiltersOrNotInString()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'name' => '![Leia Organa,Chewbacca]',
			'or' => [
				'name' => '![Leia Organa,Chewbacca,Han Solo]',
			]
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['solocup@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersOrNotInInt()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'times_captured' => '![6,0]',
			'or' => [
				'times_captured' => '![0,4,6]'
			]
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['solocup@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersNestedRelationships()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['profile.favorite_cheese' => '~Gou'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItProperlyDeterminesScalarFilters()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['name' => '=Leia Organa,Luke Skywalker'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(4, count($results)); // It does not filter anything because this is a scalar filter
	}

	public function testItFiltersFalse()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['profile.is_human' => 'false'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersNestedTrue()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['profile.is_human' => 'true'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue(! in_array($result->username, ['chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersNestedNull()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['profile.favorite_fruit' => 'NULL'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['chewbaclava@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com']));
		}
	}

	/**
	 * Check to see if filtering by id works with a many to many relationship.
	 */
	public function testItFiltersNestedBelongsToManyRelationships()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = ['posts.tags.label' => '=#mysonistheworst'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersNestedConjuctions()
	{
		$this->assertEquals(User::all()->count(), $this->user_count);
		$filters        = [
			'username' => '^lskywalker',
			'or'       => [
				'name' => '$gana',
				'and'      => [
					'profile.favorite_cheese' => '=Provolone',
					'username' => '$gana@galaxyfarfaraway.com'
				],
				'or' => [
					'username' => '=solocup@galaxyfarfaraway.com'
				]
			]
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com']));
		}
	}
}
