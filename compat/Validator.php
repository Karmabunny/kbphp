<?php

namespace karmabunny\kb;

class_alias(ValidatorInterface::class, Validator::class);

if (false) {
    interface Validator extends ValidatorInterface {}
}
