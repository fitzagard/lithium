<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2015, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\aop;

use stdClass;
use lithium\aop\Chain;

class ChainTest extends \lithium\test\Unit {

	public function testAllFiltersAreTriggeredInOrder() {
		$message = null;

		$subject = new Chain([
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => [
				function($params, $next) use (&$message) {
					$message .= '1';
					return $next($params);
				},
				function($params, $next) use (&$message) {
					$message .= '2';
					return $next($params);
				}
			]
		]);
		$subject->run([], function($params) use (&$message) {
			$message .= '3';
		});
		$this->assertEqual('123', $message);
	}

	public function testNoNextStopsFurtherFilters() {
		$message = null;

		$subject = new Chain([
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => [
				function($params, $next) use (&$message) {
					$message .= '1';
					return $next($params);
				},
				function($params, $next) use (&$message) {
					$message .= '2';
				}
			]
		]);
		$subject->run([], function($params) use (&$message) {
			$message .= '3';
		});
		$this->assertEqual('12', $message);
	}

	public function testFilterWrappingInOut() {
		$message = null;

		$subject = new Chain([
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => [
				function($params, $next) use (&$message) {
					$message .= ' 1BEFORE';
					$result = $next($params);
					$message .= ' 1AFTER';
				},
				function($params, $next) use (&$message) {
					$message .= ' 2BEFORE';
					$result = $next($params);
					$message .= ' 2AFTER';
				}
			]
		]);
		$subject->run([], function($params) use (&$message) {
			$message .= ' 3BEFORE';
			$message .= ' 3AFTER';
		});
		$this->assertEqual(' 1BEFORE 2BEFORE 3BEFORE 3AFTER 2AFTER 1AFTER', $message);
	}

	public function testRunReturnsReturnValueFromImplementation() {
		$subject = new Chain([
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => [
				function($params, $next) {
					return $next($params);
				}
			]
		]);
		$result = $subject->run([], function($params) {
			return 'foo';
		});
		$this->assertEqual('foo', $result);
	}

	public function testConsecutiveParamsManipulation() {
		$subject = new Chain([
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => [
				function($params, $next) {
					$params['message'] .= '1';
					return $next($params);
				},
				function($params, $next) {
					$params['message'] .= '2';
					return $next($params);
				}
			]
		]);
		$result = $subject->run(['message' => null], function($params) {
			$params['message'] .= '3';
			return $params['message'];
		});
		$this->assertEqual('123', $result);

		$subject = new Chain([
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => [
				function($params, $next) {
					$params['message'] .= '1';
					return $next($params);
				},
				function($params, $next) {
					$params['message'] = null;
					return $next($params);
				}
			]
		]);
		$result = $subject->run(['message' => null], function($params) {
			$params['message'] .= '3';
			return $params['message'];
		});
		$this->assertEqual('3', $result);
	}

	public function testObjectInParamsKeepsRef() {
		$subject = new Chain([
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => [
				function($params, $next) {
					$params['object']->foo = 'bar';
					return $next($params);
				}
			]
		]);

		$object = new stdClass();
		$originalHash = spl_object_hash($object);

		$result = $subject->run(['object' => $object], function($params) {
			return $params['object'];
		});
		$resultHash = spl_object_hash($result);

		$this->assertEqual($originalHash, $resultHash);
	}
}

?>