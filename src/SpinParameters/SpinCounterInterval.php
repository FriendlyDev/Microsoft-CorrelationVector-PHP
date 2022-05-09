<?php

namespace MicrosoftCV\SpinParameters;

enum SpinCounterInterval: int
{
    /**
     * The coarse interval drops the 24 least significant bits in DateTime.Ticks
     * resulting in a counter that increments every 1.67 seconds.
     */
    case COARSE = 24;

    /**
     * The fine interval drops the 16 least significant bits in DateTime.Ticks
     * resulting in a counter that increments every 6.5 milliseconds.
     */
    case FINE = 16;

    public function ticksBitsToDrop(): int
    {
        return $this->value;
    }
}
