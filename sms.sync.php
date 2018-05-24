<?php
date_default_timezone_set("Asia/Bangkok");

//http://smssync.ushahidi.com/tool/

/**
 * Gets the messages(SMSs) sent by SMSsync as a POST request.
 *
 */
function get_message()
{
    $error = NULL;
    // Set success to false as the default success status
    $success = false;
    /**
     *  Get the phone number that sent the SMS.
     */
    if (isset($_POST['from'])) {
        $from = $_POST['from'];
    } else {
        $error = 'The from variable was not set';
    }
    /**
     * Get the SMS aka the message sent.
     */
    if (isset($_POST['message'])) {
        $message = $_POST['message'];
    } else {
        $error = 'The message variable was not set';
    }
    /**
     * Get the secret key set on SMSsync side
     * for matching on the server side.
     */
    if (isset($_POST['secret'])) {
        $secret = $_POST['secret'];
    }
    /**
     * Get the timestamp of the SMS
     */
    if (isset($_POST['sent_timestamp'])) {
        $sent_timestamp = $_POST['sent_timestamp'];
    }
    /**
     * Get the phone number of the device SMSsync is
     * installed on.
     */
    if (isset($_POST['sent_to'])) {
        $sent_to = $_POST['sent_to'];
    }
    /**
     * Get the unique message id
     */
    if (isset($_POST['message_id'])) {
        $message_id = $_POST['message_id'];
    }
    /**
     * Get device ID
     */
    if (isset($_POST['device_id'])) {
        $device_id = $_POST['device_id'];
    }

    if ($secret != 'SecretSMS') {
        //The screte key set here is SecretSMS. Make sure you enter
        $_logs = "The secret: '.$secret.' -> does not match the one on the server";
        write_message_to_file($string);
    } else {
        // now let's write the info sent by SMSsync to a file called test.txt
        $string = "From: " . $from . "\n";
        $string .= "Message: " . $message . "\n";
        $string .= "Timestamp: " . $sent_timestamp . "\n";
        $string .= "Messages Id:" . $message_id . "\n";
        $string .= "Sent to: " . $sent_to . "\n";
        $string .= "Device ID: " . $device_id . "\n";
        $string .= "REQUEST_METHOD ID: " . $_SERVER['REQUEST_METHOD'] . "\n\n\n";
        write_message_to_file($string);

        if ((strlen($from) > 0) and (strlen($message) > 0) and (strlen($sent_timestamp) > 0) and (strlen($message_id) > 0)) {
            // Xu ly sau
        }

        $m = "Chung toi da nhan duoc tin nhan cua ban";
        $m = ''; // Không phản hổi lại khách hàng.
        if (!empty($m)) {
            // Phản hồi lại đã nhận được nội dung
            send_instant_message($from, $m);
        } else {
            // Đã xử lý xong để báo chuyển nội dung sang phần Published
            $success = true;
            $error = "";
            $response = json_encode([
                "payload" => [
                    "success" => $success,
                    "error" => $error
                ]
            ]);
            send_response($response);
        }
    }
}

/**
 * Writes the received responses to a file. This acts as a database.
 */
function write_message_to_file($message)
{
    $myFile = "test.txt";
    $fh = fopen($myFile, 'a') or die("can't open file");
    @fwrite($fh, $message);
    @fclose($fh);
}

/**
 * Implements the task feature. Sends messages to SMSsync to be sent as
 * SMS to users.
 */
function send_task()
{
    $reply = array();
    $reply[] = array(
        "to" => "+84945338080",
        "message" => "Noi dung gui tu dong 1",
        "uuid" => "042bf515-eq6b-f424-c4pz"
    );
    $reply[] = array(
        "to" => "+84983234156",
        "message" => "Noi dung gui tu dong 2",
        "uuid" => "022b3515-ef6b-f424-c4ws"
    );

    // Send JSON response back to SMSsync
    $response = json_encode(array(
        "payload" => array(
            "task" => "send",
            "secret" => "SecretSMS",
            "messages" => array_values($reply)
        )
    ));
    send_response($response);
}

/**
 * This sends an instant response when the server receive messages(SMSs) from
 * SMSsync. This requires the settings "Get Reply from Server" enabled on
 * SMSsync.
 */
function send_instant_message($to, $m)
{
    $s = true;
    $reply[0] = [
        "to" => $to,
        "message" => $m,
        "uuid" => "1ba368bd-c467-4374-bf28"
    ];
    // Send JSON response back to SMSsync
    $response = json_encode([
        "payload" => [
            "success" => $s,
            "task" => "send",
            "secret" => "SecretSMS",
            "messages" => array_values($reply)
        ]
    ]);
    send_response($response);
}

function send_response($response)
{
    Header("Cache-Control: no-cache, must-revalidate");
    header("Content-type: application/json; charset=utf-8");
    echo $response;
}

function get_sent_message_uuids()
{
    $data = file_get_contents('php://input');
    $queued_messages = file_get_contents('php://input');
    // Writing this to a file for demo purposes.
    // In production, you will have to process the JSON string
    // and remove the messages from the database or where ever the
    // messages are stored so the next Task run, the server won't add
    // these messages.
    write_message_to_file($queued_messages . "\n\n");
    send_message_uuids_waiting_for_a_delivery_report($queued_messages);
}

/**
 * Sends message UUIDS to SMSsync for their sms delivery status report.
 * When SMSsync send messages from the server as SMS to phone numbers, SMSsync
 * can send back status delivery report for these messages.
 */
function send_message_uuids_waiting_for_a_delivery_report($queued_messages)
{
    // Send back the received messages UUIDs back to SMSsync
    $json_obj = json_decode($queued_messages);
    $response = json_encode([
        "message_uuids" => $json_obj->queued_messages
    ]);
    send_response($response);
}

function send_messages_uuids_for_sms_delivery_report()
{
    if (isset($_GET['task']) and $_GET['task'] == 'result') {
        $response = json_encode([
            "message_uuids" => [
                '1ba368bd-c467-4374-bf28'
            ]
        ]);
        send_response($response);
    }
}

/**
 * Get status delivery report on sent messages
 *
 */
function get_sms_delivery_report()
{
    if ($_GET['task'] == 'result' and $_GET['secret'] == 'SecretSMS') {
        $message_results = file_get_contents('php://input');
        write_message_to_file("message " . $message_results . "\n\n");
    }
}
// Execute functions above
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_GET['task']) and $_GET['task'] == 'result') {
        $_logs = "Da vao task result";
        $_logs .= print_r($_GET, true);
        $_logs .= print_r($_POST, true);
        $_logs .= "\n" . date("H:i:s") . "\n";
        $_logs .= "\n\n";
        write_message_to_file($_logs);
        //get_sms_delivery_report();
    } else if (isset($_GET['task']) && $_GET['task'] == 'send') {
        $_logs = "Da vao task send";
        $_logs .= print_r($_GET, true);
        $_logs .= print_r($_POST, true);
        $_logs .= "\n" . date("H:i:s") . "\n";
        $_logs .= "\n\n";
        write_message_to_file($_logs);
        //get_sent_message_uuids();
    } else {
        get_message();
    }
} elseif (isset($_GET['task']) && $_GET['task'] == 'send') {
    send_task();

    $_logs = "task: send\n";
    $_logs .= print_r($_GET, true);
    $_logs .= print_r($_POST, true);
    $_logs .= "\n" . date("H:i:s") . "\n";
    $_logs .= "\n\n";
    write_message_to_file($_logs);
} else {
    $_logs = "Other End\n";
    $_logs .= print_r($_GET, true);
    $_logs .= print_r($_POST, true);
    $_logs .= "\n" . date("H:i:s") . "\n";
    $_logs .= "\n\n";
    write_message_to_file($_logs);
    //send_task();
    // send_messages_uuids_for_sms_delivery_report();
}