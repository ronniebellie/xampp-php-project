<?php
/** Nonstatutory TVM math; decimal periodic rates, end-of-period payments. */
function rb_math_domain(float $rate, float $n, bool $wholePeriods = false): void {
    if (!is_finite($rate) || !is_finite($n) || $rate <= -1 || $n < 0 || ($wholePeriods && ($n != floor($n) || $n > 12000))) {
        throw new DomainException('Unsupported mathematical domain: require a positive compounding base and valid nonnegative periods. Annuities require at most 12000 whole payments.');
    }
}
function rb_math_finite(float $value): float {
    if (!is_finite($value)) throw new DomainException('Result exceeds the supported numerical range.');
    return $value;
}
function rb_math_pv(float $fv, float $rate, float $n): float {
    rb_math_domain($rate, $n);
    return rb_math_finite($fv * exp(-$n * log1p($rate)));
}
function rb_math_annuity(float $payment, float $rate, float $n, bool $present = false): float {
    rb_math_domain($rate, $n, true);
    $factor = $rate == 0.0 ? $n : ($present ? -expm1(-$n * log1p($rate)) : expm1($n * log1p($rate))) / $rate;
    return rb_math_finite($payment * $factor);
}
function rb_math_periods(float $pv, float $fv, float $rate, float $compound): float {
    if (!is_finite($pv) || !is_finite($fv) || !is_finite($compound) || $pv <= 0 || $fv <= 0 || $compound <= 0) throw new DomainException('Positive finite amounts and compounding frequency are required.');
    rb_math_domain($rate / $compound, 0);
    if ($fv == $pv) return 0.0;
    if ($rate == 0.0 || ($rate < 0 && $fv > $pv) || ($rate > 0 && $fv < $pv)) throw new DomainException('No solution: target is not attainable in positive time at this rate.');
    return rb_math_finite((log($fv)-log($pv)) / ($compound * log1p($rate/$compound)));
}
function rb_math_growing(float $payment, float $rate, float $growth, float $n, bool $present): float {
    rb_math_domain($rate, $n, true); rb_math_domain($growth, $n, true);
    if ($n == 0) return 0.0;
    if ($rate == $growth) return rb_math_finite($present ? $payment*$n/(1+$rate) : $payment*$n*pow(1+$rate,$n-1));
    // Relative-rate log avoids subtracting nearly equal powers/logarithms.
    if ($present) {
        $value = $payment * -expm1($n * log1p(($growth-$rate)/(1+$rate))) / ($rate-$growth);
    } else {
        $base = 1 + min($rate, $growth);
        $spread = abs($rate-$growth) / $base;
        $value = $payment * pow($base, $n-1) * expm1($n * log1p($spread)) / $spread;
    }
    return rb_math_finite($value);
}
