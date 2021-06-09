# Karmabunny Helpers

Just a big bunch of your favourite utilities.

Most of these have been repurposed from Sprout.

Add more if you please.


## Usage

Everything lives the `karmabunny\kb` namespace.

Currently hosted on our private packagist: https://packages.bunnysites.com/

### 1. Add this to your `composer.json` file.

```json
{
  "config": {
      "preferred-install": {
          "karmabunny/*": "dist"
      }
  },
  "repositories": [{
    "type": "composer",
    "url": "https://packages.bunnysites.com"
  }]
}
```

### 2. Add to your dependencies.

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

When _renaming_ things, we can be a little more relaxed.

1. Deprecating
   - Create an alias in `compat/`
   - Mark the intellisense hack (you'll see it) as `@deprecated`
2. Major version bump
   - Remove the intellisense hack
3. Next major version bump
   - Remove the alias entirely

I understand that our intellisense hack may not last forever, that is, whenever they fix `class_alias()`. So I guess just alias + deprecated, remove next major is also ok.

As always, when deprecating + removing things always annotate your release tag. Imagine your life depends on getting it all in there. Is that a threat? Maybe.


### Collections

Models! Models! Models!


### Validators

Collection extentions:
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


### FnUtils

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


