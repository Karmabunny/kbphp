#!/usr/bin/env php
<?php

use karmabunny\kb\Cli;

require __DIR__ . '/../vendor/autoload.php';

function puts($value) {
    echo json_encode($value), PHP_EOL;
}

Cli::puts(Cli::FG_YELLOW, 'this is yellow');
Cli::puts('This is normal');
Cli::puts(Cli::FG_GREEN, 'this is green', 'also this', Cli::FG_BLUE, Cli::BG_YELLOW, 'and this is blue on yellow', 'also this');

echo "readline + tty\n";
puts(Cli::hasReadline());
puts(Cli::hasTTY());

puts(Cli::options('uhhh', ['one', 'two', 'three']));

puts(Cli::masked('masked'));

puts(Cli::invisible('invisible'));

puts(Cli::question('question'));

puts(Cli::input('input'));

