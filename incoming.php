<?php
/*
 * Copyright 2017 Front Information AS
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * Example of receiving an incoming text message via Front's SMS Gateway.
 *
 * Please contact Front Information in order to receive the configuration for your SMS Gateway.
 * http://fro.no/kontakt
 *
 * The latest version of the SMS Gateway specification is located at
 * http://fro.no/hjelp/sms_veiledning
 */

$fromID = isset($_GET['fromid']) ? $_GET['fromid'] : null;
$sender = isset($_GET['phonenr']) ? $_GET['phonenr'] : null;
$messageText = isset($_GET['txt']) ? $_GET['txt'] : null;
$time = isset($_GET['time']) ? intval($_GET['time']) : null;
$messageNumber = isset($_GET['countnr']) ? intval($_GET['countnr']) : null;
$keyword = isset($_GET['code']) ? $_GET['code'] : null;

if ($fromID === null || $sender === null || $messageText === null || !($time > 0) || !($messageNumber >= 0)) {
    error_log("Invalid incoming message: $fromID | $sender | $messageText | $time | $messageNumber");
    exit("false");
}

$ok = addMessage($fromID, $sender, $messageText, $time, $messageNumber, $keyword);

exit($ok ? "true" : "false");

/**
 * Example implementation of handling a message
 *
 * @param $fromID string Short number message was sent to
 * @param $sender string Telephone number message was sent from
 * @param $messageText string The text of the message
 * @param $time integer Unix time the message was received
 * @param $messageNumber integer A counting function that counts the number of received messages per customer. This can
 *  be used to investigate whether there is a lack incoming SMS etc.
 * @param $keyword string code word identified at short number where used
 * @return bool Indicate success
 */
function addMessage($fromID, $sender, $messageText, $time, $messageNumber, $keyword) {
    $sql = "INSERT INTO incoming_message (from_id, sender, message_text, received, message_number, keyword) "
        . "VALUES (?, ?, ?, FROM_UNIXTIME(?), ?, ?)";
    $params = array();
    array_push($params, $fromID, $sender, $messageText, $time, $messageNumber, $keyword);

    // TODO run SQL with params
    return true;
}