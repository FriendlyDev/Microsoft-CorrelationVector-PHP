<?php /** @noinspection StaticClosureCanBeUsedInspection */

use MicrosoftCV\CorrelationVector;
use MicrosoftCV\CorrelationVectorVersion;
use MicrosoftCV\SpinParameters\SpinCounterInterval;
use MicrosoftCV\SpinParameters\SpinCounterPeriodicity;
use MicrosoftCV\SpinParameters\SpinEntropy;

it('Should be able to Create v1 cV', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $correlationVector = CorrelationVector::createCorrelationVector();
    $splitVector = explode('.', $correlationVector->value());

    expect($splitVector)->toHaveCount(2);
    expect((int) $splitVector[1])->toBe(0);
    expect(strlen($splitVector[0]))->toBe(16);
});

it('Should be able to Create v2 cV', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $correlationVector = CorrelationVector::createCorrelationVector(CorrelationVectorVersion::V2);
    $splitVector = explode('.', $correlationVector->value());

    expect($splitVector)->toHaveCount(2);
    expect((int) $splitVector[1])->toBe(0);
    expect(strlen($splitVector[0]))->toBe(22);
});

it('Should be able to Parse v1 vector', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $correlationVector = CorrelationVector::parse('ifCuqpnwiUimg7Pk.1');
    $splitVector = explode('.', $correlationVector->value());

    expect($splitVector[0])->toBe('ifCuqpnwiUimg7Pk');
    expect($splitVector[1])->toBe('1');
});

it('Should be able to Parse v2 vector', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $correlationVector = CorrelationVector::parse('Y58xO9ov0kmpPvkiuzMUVA.3.4.5');
    $splitVector = explode('.', $correlationVector->value());

    expect($splitVector[0])->toBe('Y58xO9ov0kmpPvkiuzMUVA');
    expect($splitVector[1])->toBe('3');
    expect($splitVector[2])->toBe('4');
    expect($splitVector[3])->toBe('5');
});

it('Should be able to increment cV', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $correlationVector = CorrelationVector::createCorrelationVector();
    $correlationVector->increment();
    $splitVector = explode('.', $correlationVector->value());

    expect((int) $splitVector[1])->toBe(1);
});

it('Should be able to extend cV', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $correlationVector = CorrelationVector::createCorrelationVector();
    $splitVector = explode('.', $correlationVector->value());
    $vectorBase = $splitVector[0];
    $extension = $splitVector[1];

    $correlationVector = CorrelationVector::extend($correlationVector->value());
    $splitVector = explode('.', $correlationVector->value());

    expect($splitVector)->toHaveCount(3);
    expect($vectorBase)->toBe($splitVector[0]);
    expect($extension)->toBe($splitVector[1]);
    expect($splitVector[2])->toBe('0');
});

it('Should be able to validate cV creation', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = true;
    $correlationVector = CorrelationVector::createCorrelationVector();
    $correlationVector->increment();

    $splitVector = explode('.', $correlationVector->value());

    expect((int) $splitVector[1])->toBe(1);
});

it('should not extend from empty cV', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;

    // this shouldn't throw since we skip validation
    CorrelationVector::extend('');

    CorrelationVector::$validateCorrelationVectorDuringCreation = true;
    expect(fn() => CorrelationVector::extend(''))
        ->toThrow(RuntimeException::class);
});

it('should throw exception with insufficient chars', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    // this shouldn't throw since we skip validation
    CorrelationVector::extend('tul4NUsfs9Cl7mO.1');

    CorrelationVector::$validateCorrelationVectorDuringCreation = true;
    expect(fn() => CorrelationVector::extend('tul4NUsfs9Cl7mO.1'))
        ->toThrow(RuntimeException::class);
});

it('should throw exception with too many chars', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    // this shouldn't throw since we skip validation
    CorrelationVector::extend('tul4NUsfs9Cl7mOfN/dupsl.1');

    CorrelationVector::$validateCorrelationVectorDuringCreation = true;
    expect(fn() => CorrelationVector::extend('tul4NUsfs9Cl7mOfN/dupsl.1'))
        ->toThrow(RuntimeException::class);
});

it('should throw exception with too big value', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    // this shouldn't throw since we skip validation
    CorrelationVector::extend('tul4NUsfs9Cl7mOf.2147483647.2147483647.2147483647.2147483647.2147483647');

    CorrelationVector::$validateCorrelationVectorDuringCreation = true;
    // bigger than 63 chars
    expect(fn() => CorrelationVector::extend('tul4NUsfs9Cl7mOf.2147483647.2147483647.2147483647.2147483647.2147483647'))
        ->toThrow(RuntimeException::class);
});

it('should throw exception with too big value for v2 version', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    // this shouldn't throw since we skip validation
    CorrelationVector::extend('KZY+dsX2jEaZesgCPjJ2Ng.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647');

    CorrelationVector::$validateCorrelationVectorDuringCreation = true;
    // bigger than 63 chars
    expect(fn() => CorrelationVector::extend('KZY+dsX2jEaZesgCPjJ2Ng.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647'))
        ->toThrow(RuntimeException::class);
});

it('should throw exception with negetive extension value', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    // this shouldn't throw since we skip validation
    CorrelationVector::extend('tul4NUsfs9Cl7mOf.-10');

    CorrelationVector::$validateCorrelationVectorDuringCreation = true;
    // bigger than 63 chars
    expect(fn () => CorrelationVector::extend('tul4NUsfs9Cl7mOf.-10'))
        ->toThrow(RuntimeException::class);
});

it('should be immutable when increment past max', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $vector = CorrelationVector::extend('tul4NUsfs9Cl7mOf.2147483647.2147483647.2147483647.21474836479');
    $vector->increment();

    expect($vector->value())->toBe('tul4NUsfs9Cl7mOf.2147483647.2147483647.2147483647.21474836479.1');

    for ($i = 0; $i < 20; $i++) {
        $vector->increment();
    }

    expect($vector->value())->toBe('tul4NUsfs9Cl7mOf.2147483647.2147483647.2147483647.21474836479.9!');
});

it('should be immutable when increment past max for v2 version', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $base = 'KZY+dsX2jEaZesgCPjJ2Ng.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.214';
    $vector = CorrelationVector::extend($base);
    $vector->increment();

    expect($vector->value())->toBe($base . '.1');

    for ($i = 0; $i < 20; $i++) {
        $vector->increment();
    }

    expect($vector->value())->toBe($base . '.9!');
});

it('Should be able to Spin should Aways be getting bigger', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $vector = CorrelationVector::createCorrelationVector();

    $lastSpinValue = 0;
    $wrappedCounter = 0;

    for ($i = 0; $i < 9; $i++) {
        // the cV after a Spin will look like <cvBase>.0.<spinValue>.0, so the spinValue is at index = 2.
        $newVector = CorrelationVector::spin(
            $vector->value(),
            SpinCounterInterval::FINE,
            SpinCounterPeriodicity::SHORT,
            SpinEntropy::TWO
        )->value();

        $spinValue = (int) explode('.', $newVector)[2];

        // count the number of times the counter wraps.
        if ($spinValue <= $lastSpinValue) {
            $wrappedCounter++;
        }

        $lastSpinValue = $spinValue;

        usleep(10);
    }

    expect($wrappedCounter)->toBeLessThan(1);
});

it('Should be immutable if spin past max size', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $baseVector = 'tul4NUsfs9Cl7mOf.2147483647.2147483647.2147483647.214748364.23';

    // we hit 63 chars limit, will append '!' to vector
    $vector = CorrelationVector::spin($baseVector);

    expect($vector->value())->toBe($baseVector . '!');
});

it('Should be immutable if spin past max size for v2', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $baseVector = 'KZY+dsX2jEaZesgCPjJ2Ng.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.214';

    // we hit 127 chars limit, will append '!' to vector
    $vector = CorrelationVector::spin($baseVector);

    expect($vector->value())->toBe($baseVector . '!');
});

it('Should be immutable if extend past max size', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $baseVector = 'tul4NUsfs9Cl7mOf.2147483647.2147483647.2147483647.214748364.23';

    // we hit 63 chars limit, will append '!' to vector
    $vector = CorrelationVector::extend($baseVector);

    expect($vector->value())->toBe($baseVector . '!');
});

it('Should be immutable if extend past max size for v2', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $baseVector = 'KZY+dsX2jEaZesgCPjJ2Ng.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2141';

    // we hit 127 chars limit, will append '!' to vector
    $vector = CorrelationVector::extend($baseVector);

    expect($vector->value())->toBe($baseVector . '!');
});

it('Should be immutable with termination sign', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $baseVector = 'tul4NUsfs9Cl7mOf.2147483647.2147483647.2147483647.21474836479.0!';

    // extend should do nothing
    $vector = CorrelationVector::extend($baseVector);
    expect($vector->value())->toBe($baseVector);

    // spin should do nothing
    $vector = CorrelationVector::spin($baseVector);
    expect($vector->value())->toBe($baseVector);

    // increment should do nothing since it has termination sign
    $vector->increment();
    expect($vector->value())->toBe($baseVector);
});

it('Should be immutable with termination sign for v2', function () {
    CorrelationVector::$validateCorrelationVectorDuringCreation = false;
    $baseVector = 'KZY+dsX2jEaZesgCPjJ2Ng.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.2147483647.214.0!';


    // extend should do nothing
    $vector = CorrelationVector::extend($baseVector);
    expect($vector->value())->toBe($baseVector);

    // spin should do nothing
    $vector = CorrelationVector::spin($baseVector);
    expect($vector->value())->toBe($baseVector);

    // increment should do nothing since it has termination sign
    $vector->increment();
    expect($vector->value())->toBe($baseVector);
});
