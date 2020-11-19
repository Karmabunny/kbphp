# Karmabunny Helpers

Just a big bunch of your favourite utilities.

Most of these have been repurposed from Sprout.

Add more if you please.


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


