fluxcap
=======

This package contains:

- immutable classes `DateTime`, `Date` and `Time` (wrapping `\DateTimeImmutable`)
- immutable class `TimeZone` (wrapping `\DateTimeZone`)
- immutable class `Duration` (wrapping `\DateInterval`)
- enums `Month` and `WeekdayOld`
  
[![Latest release](https://img.shields.io/github/v/release/hill-valley/fluxcap?sort=semver&style=flat-square)](https://github.com/hill-valley/fluxcap/releases)
![PHP version requirement](https://img.shields.io/packagist/php-v/hill-valley/fluxcap?style=flat-square)
[![Psalm type coverage](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fshepherd.dev%2Fgithub%2Fhill-valley%2Ffluxcap%2Fcoverage)](https://shepherd.dev/github/hill-valley/fluxcap)
![License](https://img.shields.io/github/license/hill-valley/fluxcap?style=flat-square)

Installation
------------

```
composer require hill-valley/fluxcap
```

Examples
--------

### `DateTime`, `Date`, `Time`

```php
use HillValley\Fluxcap\DateTime;
use HillValley\Fluxcap\Date;
use HillValley\Fluxcap\Time;
use HillValley\Fluxcap\TimeZone;

$dateTime = DateTime::now();
$dateTime = DateTime::fromString('2015-10-21 09:30:00');
$dateTime = DateTime::fromString('2015-10-21 09:30:00', TimeZone::fromString('Europe/Berlin'));
$dateTime = DateTime::fromFormat('d.m.Y H.i', '21.10.2015 09.30');
$dateTime = DateTime::fromParts(2015, 10, 21, 9, 30, 0);
$dateTime = DateTime::fromNative(new \DateTimeImmutable());

$date = Date::today();
$date = Date::fromString('2015-10-21');

$time = Time::now();
$time = Time::fromString('09:30:00');

$dateTime->getYear();
$dateTime->toIso(); // 2015-10-21T09:30:00.000000+02:00
$dateTime->format('d.m.Y, H:i');
$dateTime->formatIntl(\IntlDateFormatter::MEDIUM); // requires intl extension

$dateTime2 = $dateTime->addDays(3);
$date2 = $date->toLastDayOfMonth();
$date2 = $date->toFirstDayOfQuarter();

$dateTime->isPast();
$dateTime->equals($dateTime2);
$dateTime->lowerEquals($dateTime2);
$duration = $dateTime->diff($dateTime2);
```

### `Duration`

```php
use HillValley\Fluxcap\Duration;

$duration = Duration::fromString('P2DT5H');
$duration = Duration::fromParts(days: 2, hours: 5);
$duration = Duration::years(2);

$duration->toIso(); // P2DT5H
$duration->format('%d days');
$duration->getHours();
```

### `Month`, `Weekday`

```php
use HillValley\Fluxcap\Month;
use HillValley\Fluxcap\Weekday;

$month = Month::October;

$weekdays = Weekday::cases();
$weekday = Weekday::Tuesday;

// same methods for months

$weekday->getName();
$weekday->getAbbreviation();

$weekday->getIntlName(); // via intl extension
$weekday->getIntlAbbreviation();

$count = $weekday->diffToNext(Weekday::Friday);
```
