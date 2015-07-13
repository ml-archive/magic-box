<?php

namespace Fuzz\MagicBox\Tests;

use Fuzz\MagicBox\Filter;
use Fuzz\MagicBox\EloquentRepository;
use Fuzz\MagicBox\Tests\Models\Post;
use Fuzz\MagicBox\Tests\Models\Profile;
use Fuzz\MagicBox\Tests\Models\User;

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
	 * @param string $repository
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

		return $temp_instance->getFields();
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

		Filter::filterQuery($query, $filters, $columns);

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

	public function testItFiltersNestedRelationships()
	{
		$repository  = $this->getRepository('Fuzz\MagicBox\Tests\Models\User');
		$first_user  = $repository->setInput(
			[
				'username'         => 'Bobby',
				'profile' => [
					'favorite_cheese' => 'Gouda'
				]
			]
		)->save();
		$second_user = $repository->setInput(
			[
				'username'         => 'Robby',
				'profile' => [
					'favorite_cheese' => 'Cheddar'
				]
			]
		)->save();
		$this->assertEquals($repository->all()->count(), 2);

		$found_users = $repository->setFilters(['profile.favorite_cheese' => '~Gou'])->all();
		$this->assertEquals($found_users->count(), 1);
		$this->assertEquals($found_users->first()->username, 'Bobby');
	}
}
