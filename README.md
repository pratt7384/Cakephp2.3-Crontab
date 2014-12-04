Cakephp2.3-Crontab
==================

How to run crontab in cakephp2.3


Put "cron.php" into your app directory /app/cron.php

More details on

````
http://colorblindprogramming.com/cronjobs-in-cakephp-2-in-5-steps

````


Step 1: copy app/webroot/index.php to app/cron.php

Step 2: edit app/cron.php

Change the last 3 lines of code as follows:

````php
<?php 
//---------THESE LINES:
	App::uses('Dispatcher', 'Routing');

	$Dispatcher = new Dispatcher();
	$Dispatcher->dispatch(new CakeRequest(), new CakeResponse(array('charset' => Configure::read('App.encoding'))));

//-----------CHANGE TO:
	App::uses('Dispatcher', 'Routing');

	define('CRON_DISPATCHER',true); 

	if($argc == 2) { 
		$Dispatcher = new Dispatcher();
		$Dispatcher->dispatch(new CakeRequest($argv[1]), new CakeResponse(array('charset' => Configure::read('App.encoding'))));
	}
?>
````

Step 3: create Controller/CronController.php

````php
class CronController extends AppController {

	public function beforeFilter() {
	    parent::beforeFilter();
	    $this->layout=null;
	}

	public function test() {
		// Check the action is being invoked by the cron dispatcher 
		if (!defined('CRON_DISPATCHER')) { $this->redirect('/'); exit(); } 

		//no view
		$this->autoRender = false;

		//do stuff...

		return;
	}
}

````


Step 4: Run your cron using the command line:

```
# php ./app/cron.php /cron/test

```


Step 5: Test that loading the same script in browser is not allowed (for security reasons):
http://yourdomain/cron/test redirects to http://yourdomain/

