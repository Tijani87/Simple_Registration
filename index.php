<?php
     
        // Print the response as plain text so that the gateway can read it
       // header('Content-type: text/plain');
        require_once('AfricasTalkingGateway.php');
        require_once('config.php');
        require_once('dbConnector.php');

        #We obtain the data which is contained in the post url on our server.

    // Receive the POST from AT
    $sessionId     =$_POST['sessionId'];
    $serviceCode   =$_POST['serviceCode'];
    $phoneNumber   =$_POST['phoneNumber'];
    $text          =$_POST['text'];

    function ussd_proceed($ussd_text){

    echo "CON $ussd_text";

}


        $level = explode("*", $text);
        if (isset($text)) {
   

        if ( $text == "" ) {
            $response="CON Welcome to the registration portal.\nPlease enter you full name";
        }

        if(isset($level[0]) && $level[0]!="" && !isset($level[1])){

          $response="CON Hi ".$level[0].", enter your gender\n";
             
        }
        else if(isset($level[1]) && $level[1]!="" && !isset($level[2])){
                $response="CON Please enter you phone number\n"; 

        }
        else if(isset($level[2]) && $level[2]!="" && !isset($level[3])){
            //Save data to database
            $data=array(
               // 'phoneNumbernumber'=>$phoneNumbernumber,
                'fullName' =>$level[0],
                'gender' => $level[1],
                'phoneNumber'=>$level[2]
                );

         // build sql statement
       
          
        $sth = $sql->prepare("INSERT INTO `users`(`fullName`,`gender`,`phoneNumber`) VALUES('".$data["fullName"]."','".$data["gender"]."','".$data["phoneNumber"]."')");
                        //$db->query($sql);

        //execute insert prepare   
        $sth->execute();
        if($sth->errorCode() == 0) {
            $ussd_text = $data["fullName"]." your registration was successful. Your gender is ".$data["gender"]." and phone number is ".$data["phoneNumber"];
            ussd_proceed($ussd_text);
        } else {
            $errors = $sth->errorInfo();
        }
    

            

            $response=" \nThank you ".$level[0]." for registering.\nWe will keep you updated"; 
    }

        header('Content-type: text/plain');
        echo $response;

    }

?>


