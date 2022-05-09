# Correlation Vector PHP

Microsoft-CorrelationVector-PHP provides the PHP implementation of the CorrelationVector protocol for tracing and correlation of events through a distributed system.

# Correlation Vector
## Background

**Correlation Vector** (a.k.a. **cV**) is a format and protocol standard for tracing and correlation of events through a distributed system based on a light weight vector clock.
The standard is widely used internally at Microsoft for first party applications and services and supported across multiple logging libraries and platforms (Services, Clients - Native, Managed, Js, iOS, Android etc). The standard powers a variety of different data processing needs ranging from distributed tracing & debugging to system and business intelligence, in various business organizations.

For more on the correlation vector specification and the scenarios it supports, please refer to the [specification](https://github.com/Microsoft/CorrelationVector) repo.


## Installation

```sh
composer install friendlydev/microsoft-correlationvector-php
```

## Usage

```php
use MicrosoftCV\CorrelationVector;

$ms_cv = CorrelationVector::createCorrelationVector();

print 'Initial vector: ' . $ms_cv->value();
print 'Next iteration: ' . $ms_cv->increment();
```

## Requirements

* PHP >= 8.1

## License

GNU General Public License v3.0

## Authors

Based on the implementation by Microsoft:

<https://github.com/search?q=org%3Amicrosoft+correlationVector>

<https://github.com/Microsoft/Telemetry-Client-for-Android/blob/master/AndroidCll/src/main/java/com/microsoft/cll/android/CorrelationVector.java>
