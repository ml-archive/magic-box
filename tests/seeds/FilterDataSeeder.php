<?php
namespace Fuzz\MagicBox\Tests\Seeds;

use Fuzz\MagicBox\Tests\Models\Post;
use Fuzz\MagicBox\Tests\Models\Profile;
use Fuzz\MagicBox\Tests\Models\Tag;
use Fuzz\MagicBox\Tests\Models\User;
use Illuminate\Database\Seeder;

class FilterDataSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		foreach ($this->users() as $user) {
			$user_instance = new User;

			foreach (
				[
					'username',
					'name',
					'hands',
					'times_captured',
					'occupation',
				] as $attribute
			) {
				$user_instance->{$attribute} = $user[$attribute];
			}

			$user_instance->save();

			$profile = new Profile;
			foreach ($user['profile'] as $key => $value) {
				$profile->{$key} = $value;
			}
			$profile->user_id = $user_instance->id;
			$profile->save();

			foreach ($user['posts'] as $post) {
				$post_instance          = new Post;
				$post_instance->title   = $post['title'];
				$post_instance->user_id = $user_instance->id;
				$post_instance->save();

				$tag_ids = [];
				foreach ($post['tags'] as $tag) {
					$tag_instance        = new Tag;
					$tag_instance->label = $tag['label'];
					$tag_instance->save();
					$tag_ids[] = $tag_instance->id;
				}

				$post_instance->tags()->sync($tag_ids);
			}
		}

		$users = User::with(
			[
				'profile',
				'posts.tags'
			]
		)->get()->toArray();
		$test  = 'test';
	}

	public function users()
	{
		return [
			[
				'username'       => 'lskywalker@galaxyfarfaraway.com',
				'name'           => 'Luke Skywalker',
				'hands'          => 1,
				'times_captured' => 4,
				'occupation'     => 'Jedi',
				'profile'        => [
					'favorite_cheese' => 'Gouda',
					'favorite_fruit'  => 'Apples',
					'is_human'        => true
				],
				'posts'          => [
					[
						'title' => 'I Kissed a Princess and I Liked it',
						'tags'  => [
							['label' => '#peace',],
							['label' => '#thelastjedi',]
						]
					]
				]
			],
			[
				'username'       => 'lorgana@galaxyfarfaraway.com',
				'name'           => 'Leia Organa',
				'hands'          => 2,
				'times_captured' => 6,
				'occupation'     => null,
				'profile'        => [
					'favorite_cheese' => 'Provolone',
					'favorite_fruit'  => 'Mystery Berries',
					'is_human'        => true
				],
				'posts'          => [
					[
						'title' => 'Smugglers: A Girl\'s Dream',
						'tags'  => [
							['label' => '#princess',],
							['label' => '#mysonistheworst',],
						]
					]
				]
			],
			[
				'username'       => 'solocup@galaxyfarfaraway.com',
				'name'           => 'Han Solo',
				'hands'          => 2,
				'times_captured' => 1,
				'occupation'     => 'Smuggler',
				'profile'        => [
					'favorite_cheese' => 'Cheddar',
					'favorite_fruit'  => null,
					'is_human'        => true
				],
				'posts'          => [
					[
						'title' => '10 Easy Ways to Clean Fur From Couches',
						'tags'  => [
							['label' => '#iknow',],
							['label' => '#triggerfinger',],
							['label' => '#mysonistheworst',],
						]
					]
				]
			],
			[
				'username'       => 'chewbaclava@galaxyfarfaraway.com',
				'name'           => 'Chewbacca',
				'hands'          => 0,
				'times_captured' => 0,
				'occupation'     => 'Smuggler\'s Assistant',
				'profile'        => [
					'favorite_cheese' => 'brie',
					'favorite_fruit'  => null,
					'is_human'        => false
				],
				'posts'          => [
					[
						'title' => 'Rrrrrrr-ghghg Rrrr-ghghghghgh Rrrr-ghghghgh!',
						'tags'  => [
							['label' => '#starwarsfurlife',],
							['label' => '#chewonthis',],
						]
					]
				]
			],
		];
	}
}
