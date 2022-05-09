<?php

namespace MicrosoftCV\SpinParameters;

enum SpinEntropy: int
{
    /**
     * Do not generate entropy as part of the spin value.
     */
    case NONE = 0;

    /**
     * Generate entropy using 8 bits.
     */
    case ONE = 1;

    /**
     * Generate entropy using 16 bits.
     */
    case TWO = 2;
}
