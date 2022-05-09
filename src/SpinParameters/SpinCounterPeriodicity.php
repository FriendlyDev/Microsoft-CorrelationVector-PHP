<?php

namespace MicrosoftCV\SpinParameters;

enum SpinCounterPeriodicity: int
{
    /**
     * Do not store a counter as part of the spin value.
     */
    case NONE = 0;

    /**
     * The short periodicity stores the counter using 16 bits.
     */
    case SHORT = 16;

    /**
     * The medium periodicity stores the counter using 24 bits.
     */
    case MEDIUM = 24;

    public function totalBits(SpinEntropy $entropy): int{
        return $this->value + $entropy->value * 8;
    }
}
