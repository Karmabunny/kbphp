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


### Rdb (redis)

Thin wrapper around the php-redis extension.

Although it's now recommended to use `predis/predis` instead.


### Reflect

Some useful reflection things.


### Serialisation

- Json - normalised encode/decode with exceptions
- XML - now with templating
- Enc
- Url


### Security

Boring stuff.


### Time

It's just timeago.


### Arrays

Non-standard or weakly supported things.

- array first
- array last
- fill w/ callback
