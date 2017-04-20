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
 * Example of recording text message status change.
 *
 * Please contact Front Information in order to receive the configuration for your SMS Gateway.
 * http://fro.no/kontakt
 *
 * The latest version of the SMS Gateway specification is located at
 * http://fro.no/hjelp/sms_veiledning
 */

$frontMessageId = isset($_GET['origid']) ? intval($_GET['origid']) : null;
$frontStatus = isset($_GET['status']) ? intval($_GET['status']) : null;

$status = getStatus($frontStatus);

if (!($frontMessageId > 0 && is_string($status))) {
    error_log("Missing or invalid message id / status: $frontMessageId / $frontStatus");

    echo "false";
    exit();
}

$ok = updateStatus($frontMessageId, $status);

echo $ok ? "true" : "false";

exit();

/**
 * Convert to human readable message status.
 *
 * @param $frontStatus integer Front's numeric status
 * @return null|string String representation of the status or null for an invalid Front numeric status
 */
function getStatus($frontStatus) {
    switch ($frontStatus) {
        case -1:
            return "SENT";
        case 4:
            return "DELIVERED";
        case 5:
            return "FAILED";
    }

    return null;
}

/**
 * Example implementation of updating the status of a message via SQL. Replace with your custom implementation.
 * @param $frontMessageId integer Front's message id, you should have recorded this when you submitted the message to the SMS Gateway.
 * @param $status string The human readable status
 * @return bool Was the update successful
 */
function updateStatus($frontMessageId, $status) {
    $sql = null;
    $params = array();

    // Note that messages can be receive a given status several times if the message was split into multiple SMS
    // messages (for example when the message text over 160 characters).
    if ($status === "SENT") {
        // Note that sometimes that "sent" status arrives after the "delivered" or "failed" status.
        $sql = "UPDATE message SET last_modified = CURRENT_TIMESTAMP(), status = ? WHERE front_message_id = ? AND status NOT IN (?, ?, ?)";
        array_push($params, $status, $frontMessageId, "SENT", "DELIVERED", "FAILED");
    } else if ($status === "FAILED") {
        $sql = "UPDATE message SET last_modified = CURRENT_TIMESTAMP(), status = ? WHERE front_message_id = ? AND status NOT IN (?, ?)";
        array_push($params, $status, $frontMessageId, "DELIVERED", "FAILED");
    } else if ($status === "DELIVERED") {
        $sql = "UPDATE message SET last_modified = CURRENT_TIMESTAMP(), status = ? WHERE front_message_id = ? AND status != ?";
        array_push($params, $status, $frontMessageId, "DELIVERED");
    } else {
        error_log("Unsupported status: $status");
        return false;
    }

    // TODO execute the sql
    return true;
}

