<?php
function loginName()
{
    $string = "WSP-MACLE-&IY31QDP5g"; //  Take from Payment Page ID in Payment Pages interface
    return $string;
}

function tranKey()
{
    $string = "6mYMCailUPpAONJ227__"; // Take from Payment Pages configuration interface
    return $string;

}

function seqNum()
{
    $string = "600601000";
    return $string;
}

function getTMStamp()
{
    $date = date_create();
    $x_fp_timestamp = date_timestamp_get($date);
    return $x_fp_timestamp;
}

function genHash($amount, $curCode)
{
    // The values that contribute to x_fp_hash
    $hmac_data = loginName() . "^" . seqNum() . "^" . getTMStamp() . "^" . $amount . "^" . $curCode;
    $x_fp_hash = hash_hmac('SHA1', $hmac_data, tranKey());
    return $x_fp_hash;
}
