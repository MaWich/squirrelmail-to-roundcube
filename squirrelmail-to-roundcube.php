<?php
/*
 * This Script copy Address Book from Squirrelmail Database Table address to Roundcube contacts table
 * Include create user when not exist
 */

/*
 * Configuration begin
 */

/*
 * Squirrelmail settings
 */

$SQ_DB_HOST = '127.0.0.1';
$SQ_DB_PORT = '3306';
$SQ_DB_USER = 'USER';
$SQ_DB_PASS = 'PASSWD';
$SQ_DB_DB = 'squirrelmail';
$SQ_DB_AD_TABLE = 'address';

/*
 * Roundcube settings
 */

$RQ_DB_HOST = '127.0.0.2';
$RQ_DB_PORT = '3306';
$RQ_DB_USER = 'USER';
$RQ_DB_PASS = 'PASSWD';
$RQ_DB_DB = 'roundcubemail';
$RQ_DB_AD_TABLE = 'contacts';
$RQ_DB_USER_TABLE = 'contacts';

/*
 * Configuration end
 */
 
/*
 * Connect to Squirrelmail DB
 */
 $mysqli_SQ = new mysqli($SQ_DB_HOST, $SQ_DB_USER, $SQ_DB_PASS, $SQ_DB_DB, $SQ_DB_PORT);
 if ($mysqli_SQ->connect_error) {
     die('Connect Error (' . $mysqli_SQ->connect_errno . ') ' . $mysqli_SQ->connect_error);
 }
 echo 'Success... to Squirrelmail DB ' . $mysqli_SQ->host_info . "\n";

 /*
 * Connect to Roundcube DB
 */
 $mysqli_RQ = new mysqli($RQ_DB_HOST, $RQ_DB_USER, $RQ_DB_PASS, $RQ_DB_DB, $RQ_DB_PORT);
 if ($mysqli_RQ->connect_error) {
     die('Connect Error (' . $mysqli_RQ->connect_errno . ') ' . $mysqli_RQ->connect_error);
 }
 echo 'Success... to Roundcube DB ' . $mysqli_RQ->host_info . "\n";


/*
 * get all Address Book entry's from Squirrelmail
 */
if ($SQ_result = $mysqli_SQ->query("SELECT * FROM address ORDER BY owner")) {
    while($SQ_obj = $SQ_result->fetch_object()){ 
        $owner=$SQ_obj->owner;
        $nickname=utf8_encode($SQ_obj->nickname);
        $firstname=utf8_encode($SQ_obj->firstname);
        $lastname=utf8_encode($SQ_obj->lastname);
        $email=$SQ_obj->email;
        $label=utf8_encode($SQ_obj->label);
    
        /*
         * Check if Squirrelmail Addrees Book Owner exist in Roundcube users
         */
        if ($RQ_result = $mysqli_RQ->query("SELECT username FROM users WHERE username='".$mysqli_RQ->real_escape_string($owner)."'")) {

            /*
             * Create user in Roundcube users if not exist
             */
            if($RQ_result->num_rows == 0 ) {
                echo "Add $owner to Roundcube \n";
                $result=$mysqli_RQ->query("
                    INSERT INTO users (
                        `username`,
                        `mail_host`,
                        `created`,
                        `language`
                    ) VALUES (
                        '". $mysqli_RQ->real_escape_string($owner) ."',
                        '". $mysqli_RQ->real_escape_string("localhost") ."',
                        '". date('Y-m-d H:i:s') ."',
                        '". $mysqli_RQ->real_escape_string("DE_de") ."'        
                    )
                ");
                if (!$result) {
                    echo "Error: " . $mysqli_RQ->error;
                }
            } else {
                
                /*
                 * Get userID from Roundcube users
                 */
                if ($result = $mysqli_RQ->query("SELECT user_id FROM users WHERE username='". $mysqli_RQ->real_escape_string($owner) ."'")) {
                    $obj = $result->fetch_object();
                    $user_id = $obj->user_id;
                }

                /*
                 * Check if Contact exist
                 */
                 if ($result = $mysqli_RQ->query("SELECT email FROM contacts WHERE user_id='$user_id' AND email='". $mysqli_RQ->real_escape_string($email) ."'")) {
                    if($result->num_rows == 0 ) {

                        /*
                        * Insert data to Roundcube contacs
                        */
                        echo "Add Contact $email from $owner to Roundcube Contacts\n";
                        $result=$mysqli_RQ->query("
                        INSERT INTO contacts (
                            `contact_id`,
                            `changed`,
                            `del`,
                            `name`,
                            `email`,
                            `firstname`,
                            `surname`,
                            `vcard`,
                            `user_id`
                        ) VALUES (
                            NULL,
                            '". date('Y-m-d H:i:s') ."',
                            0,
                            '". $mysqli_RQ->real_escape_string($label) ."',
                            '". $mysqli_RQ->real_escape_string($email) ."',
                            '". $mysqli_RQ->real_escape_string($firstname) ."',
                            '". $mysqli_RQ->real_escape_string($lastname) ."',
                            NULL,
                            '". (int)$user_id ."'
                        )
                        ");
                        if (!$result) {
                            echo "Error: " . $mysqli_RQ->error;
                        }                
                    }
                }
            }
        }
    }
}

/*
 * Close Connect to Squirrelmail DB
 */
 $mysqli_SQ->close();

 /*
 * Close Connect to Roundcube DB
 */
 $mysqli_RQ->close();