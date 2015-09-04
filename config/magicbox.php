<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Repository Class
	|--------------------------------------------------------------------------
	|
	| When using the "Eloquent" Repository, we need to know which repository
	| should be used to retrieve your models. Of course, it is often just
	| the "EloquentRepository" repository but you may use whatever you like.
	|
	*/

	'repository' => Fuzz\MagicBox\EloquentRepository::class,
];
