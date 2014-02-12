<?php

use Laravel\Envoy\Hall;

class HallTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		if (!isset($_ENV['HALL_TOKEN'])) {
			$this->markTestSkipped('Hall room api token not set');
		}
	}

	public function testDefaults()
	{
		$notif = Hall::make($_ENV['HALL_TOKEN']);
		$notif->task('Test');
		$notif->send();
	}

	public function testCustomFrom()
	{
		$notif = Hall::make($_ENV['HALL_TOKEN'], 'My Application');
		$notif->task('Testing custom from');
		$notif->send();
	}

	public function testCustomMessage()
	{
		$notif = Hall::make($_ENV['HALL_TOKEN'], null, 'Something happened');
		$notif->send();
	}
}
