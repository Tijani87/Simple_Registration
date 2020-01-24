# Setting Up a USSD with Registration

#### A step-by-step guide

- Setting up the logic for USSD is easy with the [Africa's Talking API](docs.africastalking.com/ussd). This is a guide to how to use the
code provided on this [repository](https://github.com/JaniKibichi/ussd-app-with-registration) to create a USSD that allows users to get registered and then access a menu of services.

## Prerequisites
- First, create a config.php file in your root directory and fill in your Africa's Talking API credentials as below.

```PHP
<?php
//This is the config.php file

// Specify your login credentials
$username   = "yourUsername";
$apikey     = "yourAPIKey";

?>
```
- You need to set up on the sandbox and [create](https://sandbox.africastalking.com/ussd/createchannel) a USSD channel that you will use to test by dialing into it
 via our [simulator](https://simulator.africastalking.com:1517/).

- Assuming that you are doing your development on a localhost, you have to expose your application living in the webroot of your localshost to the internet 
via a tunneling application like [Ngrok](https://ngrok.com/). Otherwise, if your server has a public IP, you are good to go! Your URL callback for this demo
 will become:
 http://<your ip address>/RegUSSD/RegistrationUSSD.php

- This application has been developed on an Ubuntu 16.04LTS and lives in the web root at /var/www/html/RegUSSD. Courtesy of Ngrok, the publicly accessible 
url is: https://b11cd817.ngrok.io (instead of http://localhost) which is referenced in the code as well. 
(Create your own which will be different.)

- The webhook or callback to this application therefore becomes: 
https://b11cd817.ngrok.io/RegUSSD/RegistrationUSSD.php. 
To allow the application to talk to the Africa's Talking USSD gateway, this callback URL is placed in the dashboard, [under ussd callbacks 
here](https://account.africastalking.com/ussd/callback).

- Finally, this application works with a connection to a MYSQL database. Create a database with a name, username and password of your choice.
 Also create a session_levels table and a users table. These details are configured in the dbConnector.php and this is required in the main application 
script RegistrationUSSD.php.

mysql> describe users;

| Field         | Type                         | Null  | Key | Default | Extra |
| ------------- |:----------------------------:| -----:|----:| -------:| -----:|
| fullName      | varchar(250)                  |   YES |     | NULL    |       |
| gender        | varchar(250)                  |   YES |     | NULL    |       |
| phoneNumber   | varchar(250)                  |   YES |     | NULL    |       |

4 rows in set (0.53 sec)



## Features on the Services List
This USSD application has the following user journey.

- The user dials the ussd code - something like `*384*303#`

- The application is a simple registration system that ask for name, gender and phone number respectively.


## Code walkthrough
This documentation is for the USSD application that lives in https://b11cd817.ngrok.io/https://bd8a9fc9.ngrok.io/insertussd/index.php

```PHP
<?php
//1. ensure this code runs only after a POST from AT
if(!empty($_POST)){
```
Require all the necessary scripts to run this application
```PHP
	require_once('dbConnector.php');
	require_once('AfricasTalkingGateway.php');
	require_once('config.php');
```	

Receive the HTTP POST from AT
```PHP
	//2. receive the POST from AT
	$sessionId=$_POST['sessionId'];
	$serviceCode=$_POST['serviceCode'];
	$phoneNumber=$_POST['phoneNumber'];
	$text=$_POST['text'];
```

The AT USSD gateway keeps chaining the user response. We want to grab the latest input from a string like 1*1*2
```PHP
	//3. Explode the text to get the value of the latest interaction - think 1*1
	$textArray=explode('*', $text);
	$userResponse=trim(end($textArray));
```

- Interactions with the user can be managed using the received sessionId and a level management process that your application implements as follows.

- The USSD session has a set time limit(20-180 secs based on provider) under which the sessionId does not change. Using this sessionId, it is easy to navigate your user across the USSD menus by graduating their level(menu step) so that you dont serve them the same menu or lose track of where the user is. 

- Set the default level to 0 (or your own numbering scheme) -- the home menu.
- Check the session_levels table for a user with the same phone number as that received in the HTTP POST. If this exists, the user is returning and they therefore have a stored level. Grab that level and serve that user the right menu. Otherwise, serve the user the home menu.
```PHP
	//4. Set the default level of the user
	$level=0;

	//5. Check the level of the user from the DB and retain default level if none is found for this session
	$sql = "select level from session_levels where session_id ='".$sessionId." '";
	$levelQuery = $db->query($sql);
	if($result = $levelQuery->fetch_assoc()) {
  		$level = $result['level'];
	}

	//6. Update level accordingly
	if($result){
		$level = $result['level'];
	}
```

Before serving the menu, check if the incoming phone number request belongs to a registered user(sort of a login). If they are registered, they can access the menu, otherwise, they should first register.
```PHP
	//7. Check if the user is in the db
	$sql7 = "SELECT * FROM users WHERE phoneNumber LIKE '%".$phoneNumber."%' LIMIT 1";
	$userQuery=$db->query($sql7);
	$userAvailable=$userQuery->fetch_assoc();

	//8. Check if the user is available (yes)->Serve the menu; (no)->Register the user
	if($userAvailable && $userAvailable['city']!=NULL && $userAvailable['username']!=NULL){
		//9. Serve the Services Menu
```

