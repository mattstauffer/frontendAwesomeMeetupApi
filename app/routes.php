<?php

	// @todo: Service these
	App::bind('Fawesome\Meetup\Group', function() {
		$group_id = 11851552;
		return new Fawesome\Meetup\Group($group_id, 'Gainesville-Front-End-Dev-Meetup');
	});

	App::bind('Fawesome\Meetup\Caller', function($app) {
		return new Fawesome\Meetup\Caller(
			getenv('MEETUP_API_KEY'),
			$app->make('Fawesome\Meetup\Group'),
			$app->make('Fawesome\Http\Passthrough')
		);
	});

	// @todo: real Laravel CORS handling
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: X-Requested-With');

	Route::group(array('prefix' => 'api'), function() {
		Route::get('/meetup/events', function () {
			$caller = App::make('Fawesome\Meetup\Caller');

			$return = $caller->getEvents();

			$response = Response::make($return['body'], 200);

			$response->header('Content-Type', $return['header']['Content-Type']);

			return $response;
		});

		Route::get('/meetup/events/{event_id}', function ($event_id) {
			$caller = App::make('Fawesome\Meetup\Caller');

			$return = $caller->getEvent($event_id);

			$response = Response::make($return['body'], 200);

			$response->header('Content-Type', $return['header']['Content-Type']);

			return $response;
		});
	});
