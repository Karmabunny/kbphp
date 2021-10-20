# Karmabunny Helpers

Just a big bunch of your favourite utilities.

Most of these have been repurposed from Sprout.

Add more if you please.


## Usage

Pin it loosely the latest major version.

```sh
composer require karmabunny/kb:^2
```


## Code standard

### Keep the dependencies to nil.
If you need them, then it doesn't belong in this library.

### Must support php 7.0.
But feel free to slip in some ponyfills.

### Add the damn tests.
Like, even just the happy-cases.


## Running tests

Create tests whenever you can.

Run them like this: `composer test`.


## Deprecation path

Mistakes happen (like Copyable) and sometimes you realise that your naming convention is terrible.

So when removing things, first mark them as `@deprecated` this should signal to anyone using the code that it'll disappear in the next _major_ version.

As always, when deprecating + removing things always annotate your release tag. Imagine your life depends on getting it all in there. Is that a threat? Maybe.


### Collections

Models! Models! Models!

Collections extends DataObject with some array-like, virtual, serializable stuff.

Some optional behavioural traits:
- UpdateStrictTrait
- UpdateTidyTrait
- UpdateVirtualTrait
- FieldsTrait
- CachedHelperTrait


### Validators

Collection extensions:
- Doc Validator
- Rules Validator

Also the Validity class. Yay.


### Loggable

Per-class logging utilities. Has logger forwarding. Kinda interesting.


### Jobs

Generic type for crons and workers.


### Reflect

Some useful reflection things.


### Serialisation

- Json - normalised encode/decode with exceptions
- XML - now with templating
- Enc
- Url


### Security

- secure random - bytes, string!
- hash password
- comparisons


### Time

- utime, microtime as an integer
- time ago
- converting things between DateTime, DateTimeImmutable, DateTimeInterface
- date periods


### Arrays

Non-standard or weakly supported things.

- array first
- array last
- fill w/ callback
- find w/ callback
- flatten!
- queries (aka `value()`)
- create map
- normalise keys
- config loader!

The config loader is particular pleasant. It supports both traditional `$config` and modern `return [];` style configs. Combined with `value()` you can recreate `Kohana::config()` with ease.


### Consts

- HttpStatus
- CountryNames


### UUID

for v1, v4, v5 (the good ones)


### Env

Environment loading from system or a config file.

Also `isDocker()` - super handy.


### FnUtils (Wrap)

Mostly related to `array_map()` and `array_filter()`.


### CSV

- Importer
- Exporter


### XML/Dom utils

- Parsing
- Validating
- XPath!
- 'expects'


### URL

- Encode + decode
- URL builder


### Shell utilities

- Safe cmd args
- Async + sync interfaces


### CLI utilities

- text input
- masked input
- invisible input
- question - bool
- options - with key browsing
