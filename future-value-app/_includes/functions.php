<?php

// Present Value of a single future amount
function present_value($future_value, $rate, $years) {
    return $future_value / pow(1 + $rate, $years);
}

// Future Value of a single present amount
function future_value($present_value, $rate, $years) {
    return $present_value * pow(1 + $rate, $years);
}

// Future Value of an ordinary annuity (end-of-period deposits)
function future_value_annuity($payment, $rate, $years) {
    return $payment * ((pow(1 + $rate, $years) - 1) / $rate);
}

// Required payment to reach a target future value (ordinary annuity)
function required_payment_for_target($target_fv, $rate, $years, $present_value = 0) {
    $fv_of_present = $present_value * pow(1 + $rate, $years);
    $remaining = $target_fv - $fv_of_present;

    return $remaining * $rate / (pow(1 + $rate, $years) - 1);
}