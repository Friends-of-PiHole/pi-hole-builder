# pi-hole-builder
simple blacklist builder for pi hole

## how to run it
simply clone this and duck duck gos tracker-data repo to your machine.
run composer update and then execute: `php bin/console build %path to tracker-data%`, add `-vvv` for more details.

The output will be stored in ./output.txt

## outlook
support for more input formats and automatic blacklist refresh.
