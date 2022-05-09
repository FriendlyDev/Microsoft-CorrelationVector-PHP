<?php

namespace MicrosoftCV;

use MicrosoftCV\SpinParameters\SpinCounterInterval;
use MicrosoftCV\SpinParameters\SpinCounterPeriodicity;
use MicrosoftCV\SpinParameters\SpinEntropy;
use RuntimeException;

class CorrelationVector
{
    /**
     * This is the header that should be used between services to pass the correlation
     * vector.
     */
    public static string $headerName = 'MS-CV';

    /**
     * This is termination sign should be used when vector lenght exceeds
     * max allowed length
     */
    public static string $terminationSign = '!';

    /**
     * Gets or sets a value indicating whether or not to validate the correlation
     * vector on creation.
     */
    public static bool $validateCorrelationVectorDuringCreation = false;
    protected static int $maxVectorLength = 63;
    protected static int $maxVectorLengthV2 = 127;

    // In order to reliably convert a V2 vector base to a guid, the four least significant bits of the last base64
    // content-bearing 6-bit block must be zeros.
    //
    // Base64 characters with four least significant bits of zero are:
    // A - 00 0000
    // Q - 01 0000
    // g - 10 0000
    // w - 11 0000
    protected static int $baseLength = 16;
    protected static int $baseLengthV2 = 22;
    protected static string $base64CharSet = 'BCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    protected static string $base64LastCharSet = 'AQgw';

    public function __construct(
        protected ?string $baseVector,
        protected int $extension,
        public CorrelationVectorVersion $version,
        protected bool $immutable = false,
    )
    {
        $this->immutable = $immutable || self::isOversized($baseVector, $extension, $version);
    }

    /**
     * Creates a new correlation vector by extending an existing value. This should be
     * done at the entry point of an operation.
     *
     * @param string $correlationVector taken from the message header indicated by {@link CorrelationVector#headerName}
     * @returns CorrelationVector A new correlation vector extended from the current vector.
     */
    public static function extend(string $correlationVector): self
    {
        if (self::isImmutable($correlationVector)) {
            return self::parse($correlationVector);
        }

        $version = self::inferVersion($correlationVector);

        if (self::$validateCorrelationVectorDuringCreation) {
            self::validate($correlationVector, $version);
        }

        if (self::isOversized($correlationVector, 0, $version)) {
            return self::parse($correlationVector . self::$terminationSign);
        }

        return new CorrelationVector($correlationVector, 0, $version, false);
    }

    protected static function isImmutable(string $correlationVector): bool
    {
        return $correlationVector && str_ends_with($correlationVector, self::$terminationSign);
    }

    /**
     * Creates a new correlation vector by parsing its string representation
     *
     * @param string $correlationVector
     * @return CorrelationVector
     */
    public static function parse(string $correlationVector): self
    {
        if ($correlationVector) {
            (int) $p = strrpos($correlationVector, '.');
            (bool) $immutable = self::isImmutable($correlationVector);

            if ($p > 0) {
                $extensionValue = $immutable ?
                    substr($correlationVector, $p + 1, strlen($correlationVector) - $p - 1 - strlen(self::$terminationSign))
                    : substr($correlationVector, $p + 1);

                $extension = (int) $extensionValue;

                if ($extension >= 0) {
                    return new self(
                        substr($correlationVector, 0, $p),
                        $extension,
                        self::inferVersion($correlationVector),
                        $immutable,
                    );
                }
            }
        }

        return self::createCorrelationVector();
    }

    protected static function inferVersion(string $correlationVector): CorrelationVectorVersion
    {
        (int) $index = empty($correlationVector) ? -1 : strpos($correlationVector, '.');

        if (self::$baseLength === $index) {
            return CorrelationVectorVersion::V1;
        }

        if (self::$baseLengthV2 === $index) {
            return CorrelationVectorVersion::V2;
        }

        return CorrelationVectorVersion::V1;
    }

    /**
     * Initializes a new instance of the {@link CorrelationVector} class of the
     * given implemenation version. This should only be called when no correlation
     * vector was found in the message header.
     *
     * @param CorrelationVectorVersion|null $version The correlation vector implemenation version.
     * @returns CorrelationVector created correlation vector
     */
    public static function createCorrelationVector(?CorrelationVectorVersion $version = null): self
    {
        $version = $version ?? CorrelationVectorVersion::V1;

        return new self(self::seedCorrelationVector($version), 0, $version, false);
    }

    /**
     * Seed function to randomly generate a 16 character base64 encoded string for the Correlation Vector's base value
     * @returns {string} Returns generated base value
     */
    protected static function seedCorrelationVector(CorrelationVectorVersion $version): string
    {
        $result = '';

        (int) $baseLength = $version === CorrelationVectorVersion::V1 ?
            self::$baseLength :
            self::$baseLengthV2 - 1;

        for ($i = 0; $i < $baseLength; $i++) {
            // result += CorrelationVector.base64CharSet.charAt(Math.floor(Math.random() * CorrelationVector.base64CharSet.length));
            $result .= self::charAt(self::$base64CharSet, (int)(floor((float) ('0.' . random_int(0, PHP_INT_MAX)) * strlen(self::$base64CharSet))));
        }

        if ($version === CorrelationVectorVersion::V2) {
            // result += CorrelationVector.base64LastCharSet.charAt(Math.floor(Math.random() * CorrelationVector.base64LastCharSet.length));
            $result .= self::charAt(self::$base64LastCharSet, (int)(floor((float) ('0.' . random_int(0, PHP_INT_MAX)) * strlen(self::$base64LastCharSet))));
        }

        return $result;
    }

    protected static function charAt(string $input, int $position): string
    {
        if ($position < 0) {
            $position *= -1;
        }

        if ($position > strlen($input)) {
            do {
                $position -= (int) (str_pad(strlen($input), (strlen($position) - 1), '0', STR_PAD_RIGHT));
            } while ($position > strlen($input));
        }

        return $input[$position - 1];
    }

    /**
     * @throws RuntimeException
     */
    protected static function validate(string $correlationVector, CorrelationVectorVersion $version): void
    {
        $maxVectorLength = $baseLength = 0;

        match ($version) {
            CorrelationVectorVersion::V1 => (static function () use (&$maxVectorLength, &$baseLength) {
                $maxVectorLength = self::$maxVectorLength;
                $baseLength = self::$baseLength;
            })(),
            CorrelationVectorVersion::V2 => (static function () use (&$maxVectorLength, &$baseLength) {
                $maxVectorLength = self::$maxVectorLengthV2;
                $baseLength = self::$baseLengthV2;
            })(),
        };

        if (! $correlationVector || strlen($correlationVector) > $maxVectorLength) {
            throw new RuntimeException('The ' . $version->value . ' correlation vector can not be null or bigger than ' . $maxVectorLength . ' characters');
        }

        $parts = explode('.', $correlationVector);
        $partsCount = count($parts);

        if ($partsCount < 2 || strlen($parts[0]) !== $baseLength) {
            throw new RuntimeException('Invalid correlation vector ' . $correlationVector . '. Invalid base value ' . $parts[0]);
        }

        for ($i = 1; $i < $partsCount; $i++) {
            $result = (int) $parts[$i];

            if ($result < 0) {
                throw new RuntimeException('Invalid correlation vector ' . $correlationVector . '. Invalid extension value ' . $parts[0]);
            }
        }
    }

    protected static function isOversized(string $baseVector, int $extension, CorrelationVectorVersion $version): bool
    {
        if (! $baseVector) {
            return false;
        }

        (int) $size = strlen($baseVector) + 1 + ($extension > 0 ? floor(log10($extension)) : 0) + 1;

        return (
            (
                $version === CorrelationVectorVersion::V1 &&
                $size > self::$maxVectorLength
            ) ||
            (
                $version === CorrelationVectorVersion::V2 &&
                $size > self::$maxVectorLengthV2
            )
        );
    }

    /**
     * Creates a new correlation vector by applying the Spin operator to an existing value.
     * this should be done at the entry point of an operation.
     *
     * @param string                 $correlationVector taken from the message header indicated by {@link CorrelationVector#headerName}
     * @param SpinCounterInterval    $interval
     * @param SpinCounterPeriodicity $periodicity
     * @param SpinEntropy            $entropy
     * @return CorrelationVector
     */
    public static function spin(
        string                 $correlationVector,
        SpinCounterInterval    $interval = SpinCounterInterval::COARSE,
        SpinCounterPeriodicity $periodicity = SpinCounterPeriodicity::SHORT,
        SpinEntropy            $entropy = SpinEntropy::TWO,
    ): self
    {
        if (self::isImmutable($correlationVector)) {
            return self::parse($correlationVector);
        }

        $version = self::inferVersion($correlationVector);

        if (self::$validateCorrelationVectorDuringCreation) {
            self::validate($correlationVector, $version);
        }

        $ticks = (int) (microtime(true) * 1000) * 10000;
        $value = base_convert($ticks, 10, 2);
        $value = substr($value, 0, strlen($value) - $interval->ticksBitsToDrop());

        if ($entropy->value > 0) {
            $entropyPow = $entropy->value * 8;
            $entropyVal = (float) ('0.' . random_int(0, PHP_INT_MAX));
            $entropyVal *= (2 ** ($entropyPow - 1));
            $entropyVal = base_convert(round($entropyVal), 10, 2);

            $value .= str_pad(
                $entropyVal,
                $entropyPow,
                '0',
                STR_PAD_LEFT,
            );
        }

        $allowedBits = min(52, $periodicity->totalBits($entropy));

        if (strlen($value) > $allowedBits) {
            $value = substr($value, (strlen($value) - $allowedBits));
        }

        $baseVector = $correlationVector . '.' . (string) intval($value, 2);

        if (self::isOversized($baseVector, 0, $version)) {
            return self::parse($correlationVector . self::$terminationSign);
        }

        return new self($baseVector, 0, $version, false);
    }

    /**
     * Increments the current extension by one. Do this before passing the value to an
     * outbound message header.
     *
     * @returns string the new value as a string that you can add to the outbound message header indicated by {@link CorrelationVector#headerName}.
     */
    public function increment(): string
    {
        if ($this->immutable) {
            return $this->value();
        }

        if ($this->extension === PHP_INT_MAX) {
            return $this->value();
        }

        (int) $next = $this->extension + 1;

        if (self::isOversized($this->baseVector, $next, $this->version)) {
            $this->immutable = true;

            return $this->value();
        }

        $this->extension = $next;

        return $this->baseVector . $next;
    }

    /**
     * Gets the value of the correlation vector as a string.
     */
    public function value(): string
    {
        return $this->baseVector . '.' . $this->extension . ($this->immutable ? self::$terminationSign : '');
    }

    /**
     * Returns a string that represents the current object.
     *
     * @returns string A string that represents the current object.
     */
    public function __toString(): string
    {
        return $this->value();
    }
}
