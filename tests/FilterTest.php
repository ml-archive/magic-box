<?php

namespace Fuzz\MagicBox\Tests;

use Fuzz\MagicBox\Filter;
use Fuzz\MagicBox\EloquentRepository;

class FilterTest extends DBTestCase
{
	/**
	 * Retrieve a sample repository for testing.
	 *
	 * @param string|null $model_class
	 * @param array       $input
	 * @return \Fuzz\MagicBox\EloquentRepository|static
	 */
	private function getRepository($model_class = null, array $input = [])
	{
		if (! is_null($model_class)) {
			return (new EloquentRepository)->setModelClass($model_class)->setInput($input);
		}

		return new EloquentRepository;
	}

	/**
	 * Retrieve a sample repository for testing.
	 *
	 * @param string $model_class
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	private function getQuery($model_class)
	{
		$repository = $this->getRepository($model_class);

		return forward_static_call(
			[
				$repository->getModelClass(),
				'query'
			]
		);
	}

	private function getModelColumns($model_class)
	{
		$this->getRepository($model_class);
		$temp_instance = new $model_class;

		return EloquentRepository::getFields($temp_instance);
	}

	public function testItCanFilterOnFields()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'jon'])->save();
		$second_user = $repository->setInput(['username' => 'melisandre'])->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => '=jon'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 'jon');
	}

	public function testItModifiesQuery()
	{
		$model          = 'Fuzz\MagicBox\Tests\Models\User';
		$query          = $this->getQuery($model);
		$original_query = clone $query;
		$columns        = $this->getModelColumns($model);
		$filters        = ['name' => '^Bob'];

		Filter::filterQuery($query, $filters, $columns, (new $model)->getTable());

		$this->assertNotSame($original_query, $query);
	}

	public function testItStartsWith()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'bobby'])->save();
		$second_user = $repository->setInput(['username' => 'robby'])->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => '^bob'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 'bobby');
	}

	public function testItFiltersOrStartsWith()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'bobby'])->save();
		$second_user = $repository->setInput(['username' => 'robby'])->save();
		$second_user = $repository->setInput(['username' => 'gobby'])->save();
		$this->assertEquals($repository->all()->count(), 3);

		$found_users = $repository->setFilters(
			[
				'username' => '^bob',
				'or'       => ['username' => '^rob']
			]
		)->all();
		$this->assertEquals($found_users->count(), 2);
		$this->assertEquals($found_users->first()->username, 'bobby');
		$this->assertEquals($found_users->last()->username, 'robby');
	}

	public function testItEndsWith()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'Bobby'])->save();
		$second_user = $repository->setInput(['username' => 'ybboR'])->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => '$obby'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 'Bobby');
	}

	public function testItFiltersOrEndsWith()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'Bobby'])->save();
		$second_user = $repository->setInput(['username' => 'ybboR'])->save();
		$second_user = $repository->setInput(['username' => 'John'])->save();
		$this->assertEquals($repository->all()->count(), 3);

		$found_users = $repository->setFilters(
			[
				'username' => '$obby',
				'or'       => ['username' => '$bboR']
			]
		)->all();
		$this->assertEquals($found_users->count(), 2);
		$this->assertEquals($found_users->first()->username, 'Bobby');
		$this->assertEquals($found_users->last()->username, 'ybboR');
	}

	public function testItContains()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'Bobby'])->save();
		$second_user = $repository->setInput(['username' => 'Robby'])->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => '~Bob'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 'Bobby');
	}

	public function testItFiltersOrContains()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'Bobby'])->save();
		$second_user = $repository->setInput(['username' => 'Robby'])->save();
		$second_user = $repository->setInput(['username' => 'Gobby'])->save();
		$this->assertEquals($repository->all()->count(), 3);

		$found_users = $repository->setFilters(
			[
				'username' => '~Bob',
				'or'       => ['username' => '~Rob']
			]
		)->all();
		$this->assertEquals($found_users->count(), 2);
		$this->assertEquals($found_users->first()->username, 'Bobby');
		$this->assertEquals($found_users->last()->username, 'Robby');
	}

	public function testItIsLessThan()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => 3,
				'height'   => 3
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 5,
				'height'   => 5
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => '<5'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 3);
	}

	public function testItFiltersOrIsLessThan()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => 3
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 5
			]
		)->save();
		$third_user  = $repository->setInput(
			[
				'username' => 7
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 3);

		$found_users = $repository->setFilters(
			[
				'username' => '<5',
				'or'       => ['username' => '<7']
			]
		)->all();
		$this->assertEquals($found_users->count(), 2);
		$this->assertEquals($found_users->first()->username, 3);
		$this->assertEquals($found_users->last()->username, 5);
	}

	public function testItIsGreaterThan()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => 5,
				'height'   => 5
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 3,
				'height'   => 3
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => '>3'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 5);
	}

	public function testItFiltersOrIsGreaterThan()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => 5,
				'height'   => 5
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 3,
				'height'   => 3
			]
		)->save();
		$third_user  = $repository->setInput(
			[
				'username' => 1,
				'height'   => 1
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 3);

		$found_users = $repository->setFilters(
			[
				'username' => '>3',
				'or'       => ['username' => '>1']
			]
		)->all();
		$this->assertEquals($found_users->count(), 2);
		$this->assertEquals($found_users->first()->username, 5);
		$this->assertEquals($found_users->last()->username, 3);
	}

	public function testItIsLessThanOrEquals()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => 3,
				'height'   => 3
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 5,
				'height'   => 5
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => '<=3'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 3);
	}

	public function testItFiltersOrIsLessThanOrEquals()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => 3
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 5
			]
		)->save();
		$third_user  = $repository->setInput(
			[
				'username' => 7
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 3);

		$found_users = $repository->setFilters(
			[
				'username' => '<=3',
				'or'       => ['username' => '<=5']
			]
		)->all();
		$this->assertEquals($found_users->count(), 2);
		$this->assertEquals($found_users->first()->username, 3);
		$this->assertEquals($found_users->last()->username, 5);
	}

	public function testItIsGreaterThanOrEquals()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => 5,
				'height'   => 5
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 3,
				'height'   => 3
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => '>=5'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 5);
	}

	public function testItFiltersOrIsGreaterThanOrEquals()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => 5
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 3
			]
		)->save();
		$third_user  = $repository->setInput(
			[
				'username' => 1
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 3);

		$found_users = $repository->setFilters(
			[
				'username' => '>=5',
				'or'       => ['username' => '>=3']
			]
		)->all();
		$this->assertEquals($found_users->count(), 2);
		$this->assertEquals($found_users->first()->username, 5);
		$this->assertEquals($found_users->last()->username, 3);
	}

	public function testItEquals()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'Bobby'])->save();
		$second_user = $repository->setInput(['username' => 'Robby'])->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => '=Bobby'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 'Bobby');
	}

	public function testItFiltersOrEquals()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'Bobby'])->save();
		$second_user = $repository->setInput(['username' => 'Robby'])->save();
		$second_user = $repository->setInput(['username' => 'Gobby'])->save();
		$this->assertEquals($repository->all()->count(), 3);

		$found_users = $repository->setFilters(
			[
				'username' => '=Bobby',
				'or'       => ['username' => '=Robby']
			]
		)->all();
		$this->assertEquals($found_users->count(), 2);
		$this->assertEquals($found_users->first()->username, 'Bobby');
		$this->assertEquals($found_users->last()->username, 'Robby');
	}

	public function testItNotEqual()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'Bobby'])->save();
		$second_user = $repository->setInput(['username' => 'Robby'])->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => '!=Robby'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 'Bobby');
	}

	public function testItFiltersOrNotEqual()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'Bobby'])->save();
		$second_user = $repository->setInput(['username' => 'Robby'])->save();
		$second_user = $repository->setInput(['username' => 'Gobby'])->save();
		$this->assertEquals($repository->all()->count(), 3);

		$found_users = $repository->setFilters(
			[
				'username' => '=Bobby',
				'or'       => ['username' => '!=Gobby']
			]
		)->all();
		$this->assertEquals($found_users->count(), 2);
		$this->assertEquals($found_users->first()->username, 'Bobby');
		$this->assertEquals($found_users->last()->username, 'Robby');
	}

	public function testItNotNull()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'Bobby'])->save();
		$second_user = $repository->setInput(['username' => null])->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => 'NOT_NULL'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 'Bobby');
	}

	public function testItFiltersOrNotNull()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'Bobby'])->save();
		$first_user  = $repository->setInput(['username' => 'Robby'])->save();
		$second_user = $repository->setInput(['username' => null])->save();
		$this->assertEquals($repository->all()->count(), 3);

		$found_users = $repository->setFilters(
			[
				'username' => 'Bobby',
				'or'       => ['username' => 'NOT_NULL']
			]
		)->all();
		$this->assertEquals($found_users->count(), 2);
		$this->assertEquals($found_users->first()->username, 'Bobby');
		$this->assertEquals($found_users->last()->username, 'Robby');
	}

	public function testItNull()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => null])->save();
		$second_user = $repository->setInput(['username' => 'Robby'])->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => 'NULL'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, null);
	}

	public function testItFiltersOrNull()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => null])->save();
		$second_user = $repository->setInput(['username' => 'Robby'])->save();
		$second_user = $repository->setInput(['username' => 'Gobby'])->save();
		$this->assertEquals($repository->all()->count(), 3);

		$found_users = $repository->setFilters(
			[
				'username' => '=Robby',
				'or'       => ['username' => 'NULL']
			]
		)->all();
		$this->assertEquals($found_users->count(), 2);
		$this->assertEquals($found_users->first()->username, null);
		$this->assertEquals($found_users->last()->username, 'Robby');
	}

	public function testItIn()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'Bobby'])->save();
		$second_user = $repository->setInput(['username' => 'Robby'])->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => '[NotRob,Johnny,Bobby]'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 'Bobby');
	}

	public function testItFiltersOrIn()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'Bobby'])->save();
		$second_user = $repository->setInput(['username' => 'Robby'])->save();
		$second_user = $repository->setInput(['username' => 'Gobby'])->save();
		$this->assertEquals($repository->all()->count(), 3);

		$found_users = $repository->setFilters(
			[
				'username' => '[NotRob,Johnny,Bobby]',
				'or'       => ['username' => '[Robby,Mobby]']
			]
		)->all();
		$this->assertEquals($found_users->count(), 2);
		$this->assertEquals($found_users->first()->username, 'Bobby');
		$this->assertEquals($found_users->last()->username, 'Robby');
	}

	public function testItNotIn()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'Bobby'])->save();
		$second_user = $repository->setInput(['username' => 'Robby'])->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => '![Robby,Johnny,NotBob]'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 'Bobby');
	}

	public function testItFiltersOrNotIn()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(['username' => 'Bobby'])->save();
		$second_user = $repository->setInput(['username' => 'Robby'])->save();
		$second_user = $repository->setInput(['username' => 'Gobby'])->save();
		$this->assertEquals($repository->all()->count(), 3);

		$found_users = $repository->setFilters(
			[
				'username' => '![Robby,Gobby,Johnny,NotBob]',
				'or'       => ['username' => '![Gobby,Tobby]']
			]
		)->all();
		$this->assertEquals($found_users->count(), 2);
		$this->assertEquals($found_users->first()->username, 'Bobby');
		$this->assertEquals($found_users->last()->username, 'Robby');
	}

	public function testItFiltersNestedRelationships()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => 'Bobby',
				'profile'  => [
					'favorite_cheese' => 'Gouda'
				]
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 'Robby',
				'profile'  => [
					'favorite_cheese' => 'Cheddar'
				]
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['profile.favorite_cheese' => '~Gou'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 'Bobby');
	}

	public function testItProperlyDeterminesScalarFilters()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => 'Bobby',
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 'Robby',
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => '=Bobby,Robby'])->all();
		$this->assertEquals($found_users->count(), 2); // It does not filter anything because this is a scalar filter
	}

	public function testItFiltersFalse()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => false,
				'profile' => [
					'favorite_cheese' => false
				]
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => true,
				'profile' => [
					'favorite_cheese' => true
				]
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['username' => 'false'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, '0');
	}

	public function testItFiltersNestedTrue()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => 'Bobby',
				'profile' => [
					'favorite_cheese' => true
				]
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 'Robby',
				'profile' => [
					'favorite_cheese' => false
				]
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['profile.favorite_cheese' => 'true'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 'Bobby');
	}

	public function testItFiltersNestedFalse()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => 'Bobby',
				'profile' => [
					'favorite_cheese' => false
				]
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 'Robby',
				'profile' => [
					'favorite_cheese' => true
				]
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['profile.favorite_cheese' => 'false'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 'Bobby');
	}

	public function testItFiltersNestedNull()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => 'Bobby',
				'profile' => [
					'favorite_cheese' => 'Gouda',
					'favorite_fruit' => null
				]
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 'Robby',
				'profile' => [
					'favorite_cheese' => 'Cheddar',
					'favorite_fruit' => 'Apples'
				]
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['profile.favorite_fruit' => 'NULL'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 'Bobby');
	}

	/**
	 * Check to see if filtering by id works with a many to many relationship.
	 */
	public function testItFiltersNestedBelongsToManyRelationships()
	{
		// Make the users, tags, posts, and associate them.
		$this->createTagsAndPosts();
		$repository = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');

		// Sanity check, do we have two users?
		$this->assertEquals($repository->all()->count(), 2);

		// Sanity check, can we filter by just a column name in a nested relationship?
		$this->assertNotEquals(
			$repository->setFilters(['posts.tags.label' => '=History'])->all()->count(), 1
		);

		// The real test, can we filter by the id in a nested many to many relationship?
		$this->assertEquals(
			$repository->setFilters(['posts.tags.id' => '=1'])->all()->count(), 1
		);
	}

	public function testItFiltersNestedConjuctions()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username'  => 'Bobby',
				'profile'   => [
					'favorite_cheese' => 'Gouda'
				]
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 'Robby',
				'profile'  => [
					'favorite_cheese' => 'Cheddar'
				]
			]
		)->save();
		$third_user  = $repository->setInput(
			[
				'username' => 'Robert',
				'profile'  => [
					'favorite_cheese' => 'Cheddar',
				]
			]
		)->save();
		$fourth_user = $repository->setInput(
			[
				'username' => 'Gobby',
				'profile'  => [
					'favorite_cheese' => 'Provolone'
				]
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 4);

		$found_users = $repository->setFilters(
			[
				'username' => '^Bob',
				'or'       => [
					'username' => '^Rob',
					'and'      => [
						'profile.favorite_cheese' => '=Cheddar',
						'username' => '$bby'
					],
					'or' => [
						'username' => '=Gobby'
					]
				]
			]
		)->all();
		$this->assertEquals($found_users->count(), 3);
		$this->assertEquals($found_users->first()->username, 'Bobby');
		$this->assertEquals($found_users->get(1)->username, 'Robby');
		$this->assertEquals($found_users->last()->username, 'Gobby');
	}

	/**
	 * Create a set of users with some profile info.
	 */
	private function createUsers()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username' => 'Bobby',
				'profile'  => [
					'favorite_cheese' => 'Gouda'
				],
				'posts'    => [
					[
						'title' => 'Gouda Tastes Amazing!'
					]
				]
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username' => 'Robby',
				'profile'  => [
					'favorite_cheese' => 'Cheddar'
				]
			]
		)->save();
	}

	/**
	 * Create a set of tags, the first one with a post that relates back to the first user.
	 * The second tag not relating to anything.
	 */
	private function createTagsAndPosts()
	{
		$this->createUsers();
		$repository = $this->getRepository('Fuzz\MagicBox\Tests\Models\Tag');

		$first_tag = $repository->setInput(
			[
				'label' => 'Economics',
				'posts' => [
					[
						'title'   => 'Gouda',
						'user_id' => 1
					]
				]
			]
		)->save();

		$second_tag = $repository->setInput(
			[
				'label' => 'History',
			]
		)->save();
	}
}
