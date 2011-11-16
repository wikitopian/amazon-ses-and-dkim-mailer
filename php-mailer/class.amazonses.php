<?php
/**************************************************************************
 * Name: class.amazonses.php
 *
 * Version: 0.1
 *
 * Usage: A mail backed for phpmailer using AmazonSES.
 *
 * Description: 
 *    This script is the container of the class AmazonSES which 
 *    is intended to be used with "phpmailer for php5" as a mail
 *    backend that sends mails using Amazon Simple Mail Service.
 *
 *    To use the class, you must set the $aws_access_key_id and
 *    and $aws_secret_key public variables to "AWSAccessKeyId" and
 *    "AWSSecretKey" respectively, which are provided by Amazon.
 *
 *    The publicly usable function is the 'send_mail'. Please consult
 *    it's source for usage documentation. Another function 
 *    'request_verification' might also be interesting to anyone trying
 *    to verify email addresses with amazonses.
 *
 * Author: Titon Barua <titanix88@gmail.com>
 *
 * Copyright: Titon Barua 2011
 *
 * License:
 *   This source is released under GNU LGPL license,
 *   version 3 and above. Please take a look at the license:
 *   http://www.gnu.org/copyleft/lesser.html
 *
 * Release Date: 15 April, 2011
 */

class AmazonSES
{
    public $amazonSES_base_url = "https://email.us-east-1.amazonaws.com";
    public $debug = FALSE;

    public $aws_access_key_id = "";
    public $aws_secret_key = "";

    protected function
    make_required_http_headers () {
        $headers = array();

        $date_value = date(DATE_RFC2822);
        $headers[] = "Date: {$date_value}";

        $signature = base64_encode(hash_hmac("sha1", 
                                             $date_value,
                                             $this->aws_secret_key,
                                             TRUE));

        $headers[] = 
            "X-Amzn-Authorization: AWS3-HTTPS "
            ."AWSAccessKeyId={$this->aws_access_key_id},"
            ."Algorithm=HmacSHA1,Signature={$signature}";

        $headers[] =
            "Content-Type: application/x-www-form-urlencoded";

        return $headers;
    }

    protected function
    make_query_string
    ($query) {
        $query_str = "";
        foreach ($query as $k => $v)
            { $query_str .= urlencode($k)."=".urlencode($v).'&'; }

        return rtrim($query_str, '&');
    }

    protected function
    parse_amazonSES_error
    ($response) {
        $sxe = simplexml_load_string($response);

        // If the error response can not be parsed properly,
        // then just return the original response content.
        if (($sxe === FALSE) or ($sxe->getName() !== "ErrorResponse"))
            { return $response; }

        return "{$sxe->Error->Code}"
               .(($sxe->Error->Message)?" - {$sxe->Error->Message}":"");
    }

    protected function
    make_request
    ($query) {
        // Prepare headers and query string.
        $request_url = $this->amazonSES_base_url;
        $query_str = $this->make_query_string($query);
        $http_headers = $this->make_required_http_headers();

        if ($this->debug) {
            echo "[AmazonSESDebug] Query Parameters:\n\"";
            print_r($query);
            echo "\"\n";

            printf("[AmazonSES Debug] Http Headers:\n\"%s\"\n",
                                      implode("\n", $http_headers));
            printf("[AmazonSES Debug] Query String:\n\"%s\"\n", $query_str);
        }

        // Prepare curl.
        $cr = curl_init();
        curl_setopt($cr, CURLOPT_URL, $request_url);
        curl_setopt($cr, CURLOPT_POST, $query_str);
        curl_setopt($cr, CURLOPT_POSTFIELDS, $query_str);
        curl_setopt($cr, CURLOPT_HTTPHEADER, $http_headers);
        curl_setopt($cr, CURLOPT_HEADER, TRUE);
        curl_setopt($cr, CURLOPT_RETURNTRANSFER, TRUE);
        //curl_setopt($cr, CURLOPT_NOPROGRESS, FALSE);

        // Make the request and fetch response.
        $response = curl_exec($cr);
        if ($response === FALSE) {
            if ($this->debug)
                { echo "[AmazonSES Debug] curl_exec() returned false.\n"; }

            return array(FALSE, curl_error($cr));
        }

        // Separate header and content.
        $tmpar = explode("\r\n\r\n", $response, 2);
        $response_http_headers = $tmpar[0];
        $response_content = $tmpar[1];

        // Parse the http status code.
        $tmpar = explode(" ", $response_http_headers, 3);
        $response_http_status_code = $tmpar[1];

        if ($this->debug) {
            printf("[AmazonSES Debug] Response with Headers:\n\"%s\"\n",
                   $response);
            printf("[AmazonSES Debug] Response HTTP Status Code:\n\"%s\"\n",
                   $response_http_status_code);
            printf("[AmazonSES Debug] Response Content:\n\"%s\"\n",
                   $response_content);
        }

        if ($response_http_status_code === "200")
            { return array($response_http_status_code, $response_content); }
        else
            { return array($response_http_status_code,
                           $this->parse_AmazonSES_Error($response_content)); }
    }

    //***********************************************************************
    // Name: send_mail
    // Description:
    //    Send mail using amazonSES. Provide $header, $subject,
    //    $body appropriately. The $recipients and $from are mostly expe-
    //    rimental and unneccessary as documented in the SES api.
    //
    //    Return an array in the form -
    //        array(http_status_code, response_content)
    //    if the http_status_code is something other than "200", then
    //    the response_content is an error message parsed from the response.
    //***********************************************************************
    public function
    send_mail
    ($header, $subject, $body,
     $recipients=FALSE, $from=FALSE) {
        // Make sure that there is a blank line between header and body.
        $raw_mail = rtrim($header, "\r\n")."\n\n".$body;

        // Prepare query.
        //*********************************************************//
        $query = array();
        $query["Action"] = "SendRawEmail";

        // Add optional Destination.member.N request parameter.
        if ($recipients) {
            $mcnt = 1;
            foreach ($recipients as $recipient) {
                $query["Destinations.member.{$mcnt}"] = $recipient;
                $mcnt += 1;
            }
        }

        // Add optional Source parameter.
        if ($from)
            { $query["Source"] = $from; }

        // Add mail data.
        $query["RawMessage.Data"] = base64_encode($raw_mail);
        //*********************************************************//

        // Send the mail and forward the result array to the caller.
        return $this->make_request($query);
    }

    public function
    request_verification
    ($email_address) {
        $query = array();

        $query["Action"] = "VerifyEmailAddress";
        $query["EmailAddress"] = $email_address;

        return $this->make_request($query);
    }
}

/* End of file */
