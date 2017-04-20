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
 * Example of sending a single text message via Front's SMS Gateway.
 *
 * Please contact Front Information in order to receive the configuration for your SMS Gateway.
 * http://fro.no/kontakt
 *
 * The latest version of the SMS Gateway specification is located at
 * http://fro.no/hjelp/sms_veiledning
 */

date_default_timezone_set('Europe/Oslo');

$gatewayURL = "https://www.pling.as/psk/push.php";

$serviceID = 0; // Your service id as provided by Front
$password = null; // Password as provided by Front. If null, the gateway must be called from a pre-approved IP address.
$fromID = ""; // Your from id (sender of the text message) as configured by Front
$mobile = "0047xxxxxxxx"; // Mobile number in international format (Norwegian numbers begin with 0047)
$messageText = "Test: æøå " . date("Y-m-d H:i:s"); // The message text. Date is included to avoid duplicate message filters by some mobile operators.

/**
 * @param $fromID string Sender of the text message as configured by Front (Can be text or a telephone number)
 * @param $mobile string Recipient of text message in international format (Norwegian numbers start with 0047)
 * @param $messageText string The message text
 * @return integer Front's message id. Record this id for as it is the reference when receiving status messages
 * @throws Exception Failed to send message
 */
function sendMessage($fromID, $mobile, $messageText) {
    global $gatewayURL, $serviceID, $password;

    $ch = curl_init();

    $url = "$gatewayURL?serviceid=$serviceID&phoneno=$mobile&fromid=" . encodeTextParam($fromID)
        . "&txt=" . encodeTextParam($messageText);

    // Using error_log for logging not recommended for production code.
    error_log("Calling gateway with URL: $url");

    if ($password !== null) {
        $url .= "&password=$password";
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $res = curl_exec($ch);

    $curlErrorNo = curl_errno($ch);

    if ($curlErrorNo) {
        $curlError = curl_error($ch);

        error_log("Error calling SMS Gateway: $curlErrorNo; $curlError");
        curl_close($ch);

        throw new Exception("Error calling SMS Gateway: $curlErrorNo; $curlError");
    }

    $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpStatusCode < 200 || $httpStatusCode > 299) {
        error_log("Error calling SMS Gateway: Invalid http status code: $httpStatusCode");
        curl_close($ch);

        throw new Exception("Error calling SMS Gateway: Invalid http status code: $httpStatusCode");
    }

    curl_close($ch);

    $errorCode = parseErrorCode($res);

    if ($errorCode !== 0) {
        error_log("Error calling SMS Gateway: Gateway returned error code $errorCode");
        throw new Exception("Error calling SMS Gateway: Gateway returned error code $errorCode");
    }

    return parseMessageID($res);
}

/**
 * Encode a URL parameter. The gateway expects parameters encoded equivalently to javascript's escape function. See
 * https://www.pling.as/biscape.html for testing character encoding.
 *
 * @param $param string value to be encoded as URL parameter
 * @return string encoded parameter
 */
function encodeTextParam($param) {
    return urlencode(mb_convert_encoding($param, "ISO-8859-1"));
}

/**
 * Parses the error code from the SMS Gateway's http response body
 * @param $res string http response body
 * @return int string the error code (0: ok, positive number: see the Gateway specification for details)
 * @throws Exception Unable to parse error code
 */
function parseErrorCode($res) {
    if (!preg_match("/ErrorCode=([0-9]*)/", $res, $matches)) {
        error_log("Error calling SMS Gateway: Error code not found in response: $res");
        throw new Exception("Error calling SMS Gateway: Error code not found in response");
    }

    return intval($matches[1]);
}

/**
 * Parses Front's message id from the SMS Gateway's http response body
 * @param $res string http response body
 * @return int string Message id
 * @throws Exception Unable to parse message id
 */

function parseMessageID($res) {
    if (!preg_match("/ID=([0-9]*)/", $res, $matches)) {
        error_log("Error calling SMS Gateway: Message ID not found in response: $res");
        throw new Exception("Error calling SMS Gateway: Message ID not found in response");
    }

    return intval($matches[1]);
}


// Test sending a message
echo sendMessage($fromId, $mobile, $messageText);
echo "\n";