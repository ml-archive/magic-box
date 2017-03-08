<?php
return [
	/*
	|--------------------------------------------------------------------------
	| Depth
	|--------------------------------------------------------------------------
	|
	| Depth properties allow you to set the default depth for magic box
	| middleware to apply. Depth limits how far down the relationship tree
	| an operation will go.
	|
	| For example, if the eager_load_depth is set to 1 and the request has
	| include[]=user.posts. Magic Box will only return the user relationship
	| and will go no further.
	|
	| NOTE: the depth of a repository can still be changed using the setters.
	| Depth value supersedes includable.
	|
	*/
	'eager_load_depth' => 1
];