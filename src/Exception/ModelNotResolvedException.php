<?php

namespace Fuzz\MagicBox\Exception;

use RuntimeException;

/**
 * Class ModelNotResolvedException
 *
 * Thrown when a model could not be resolved by a ModelResolver.
 *
 * @package Fuzz\MagicBox\Exception
 */
class ModelNotResolvedException extends RuntimeException
{
	/**
	 * ModelNotResolvedException constructor.
	 *
	 * @param string          $message
	 * @param int             $code
	 * @param \Exception|null $previous
	 */
	public function __construct($message = 'Model could not be resolved.', $code = 0, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
