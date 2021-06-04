<?php

namespace karmabunny\kb;

class_alias(LoggableInterface::class, Loggable::class);

if (false) {
    interface Loggable extends LoggableInterface {}
}
