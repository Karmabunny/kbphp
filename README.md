# Karmabunny Helpers

Just a big bunch of your favourite utilities.

Most of these have been repurposed from Sprout.

Add more if you please.


## Usage

Pin it loosely the latest major version.

```sh
composer require karmabunny/kb:^5
```


## Code standard

### Keep the dependencies to nil.
If you need them, then it doesn't belong in this library.

### Add the damn tests.
Like, even just the happy-cases.


## Running tests

Create tests whenever you can.

Run them like this: `composer test`.


## Deprecation path

Mistakes happen and sometimes you realise that your naming convention is terrible.

So when removing things, first mark them as `@deprecated` this should signal to anyone using the code that it'll disappear in the next _major_ version. Like even if we forget and we're still lugging a bunch of stuff in the compat folder.

As always, when deprecating + removing things always annotate your release tag.


## List of Utilities

### Arrays

Lots of bits.

- array first
- array last
- fill w/ callback
- find w/ callback
- flatten
- queries (aka `value()`)
- create map
- normalise keys
- config loader
- recursive helpers


### Output Buffer

Object interface for `ob()` methods.



### CLI utilities

- text input
- masked input
- invisible input
- question - bool
- options - with key browsing
- colours


### Collections

Models! Models! Models!

Collections extends DataObject with some array-like, virtual, serializable stuff.

This encapsulates a body of other interfaces and helpers:
- Configurable
- Arrayables
- Dirty Objects

Some optional behavioural traits:
- UpdateStrictTrait
- UpdateTidyTrait
- UpdateVirtualTrait
- FieldsTrait
- CachedHelperTrait
- DocValidatorTrait
- RulesValidatorTrait


### CLI utilities

- text input
- masked input
- invisible input
- question - bool
- options - with key browsing
- colours


### Config

Helper to load configs. Extend it and configure your paths. Supports config flattening.


### Country Names

Using php-intl to convert between ISO codes and names.


### Country Zones

Lookup countries from a timezone, or zones within a country.


### CSV

- Importer
- Exporter


### DocType

Parse types from doc comments.


### Encrypt

Configurable openssl based text encryption.


### Env

Environment loading from system or a config file.

Also `isContainer()` - super handy.


### Event

Object event system.


### Generate

Simple helpers to serialise arrays into PHP code.


### HttpStatus

Some consts. Some strings.


### Inflector

Converting plurals. English only - for code patterns. Go find some ICU if you want pluralisation in your UI.


### Job

Generic type for crons and workers.


### Json and Javascript

Helpers to encode JSON objects and embed Javascript.


### Loggable and Log

Per-class logging utilities. Log forwarding and filtering.


### Reflect

Some useful reflection things.

Kind of a mess actually.


### Secrets

Masking or removing known secrets by identifing patterns.


### Serialisation

- Json - normalised encode/decode with exceptions
- XML - now with templating
- Enc
- Url


### Security

- secure random - bytes, string!
- hash password
- comparisons


### Shell

- Safe cmd args
- Async + sync interfaces

Proc wrapper. But actually don't use this.


### Sortable

Sorting lists of objects w/ Array::sort helpers.


### Text

Multibyte helpers, normalisers, masking PII data.


### Time

- utime, microtime as an integer
- time ago
- converting things between DateTime, DateTimeImmutable, DateTimeInterface
- interval helpers
- date periods
- offset formatter
- week/month/years as options, grids
- relative helper


### TimeZones

Conversion between Windows and IANA timezones.


### Url

Encode, decode, parser, builder.


### UUID

for v1, v4, v5 (the good ones)


### Validators

Collection extensions:
- Doc Validator
- Rules Validator (static, using Validity)
- Rules Validator (class, using RuleInterface)


### XML/Dom utils

- Parsing
- Validating
- XPath
- conditionals
- 'expects'
