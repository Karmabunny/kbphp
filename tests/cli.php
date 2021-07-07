#!/usr/bin/env php
<?php

use karmabunny\kb\Cli;

require __DIR__ . '/../vendor/autoload.php';

function puts($value) {
    echo json_encode($value), PHP_EOL;
}

echo "readline + tty\n";
puts(Cli::hasReadline());
puts(Cli::hasTTY());

puts(Cli::options('uhhh', ['one', 'two', 'three']));

puts(Cli::masked('masked'));

puts(Cli::invisible('invisible'));

puts(Cli::question('question'));

puts(Cli::input('input'));

