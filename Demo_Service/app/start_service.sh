#!/bin/bash

if [ ! -d "./vendor" ]; then
    composer install
fi

if [ ! -f "./vendor/bin/rr_server" ]; then
    php spark burner:init RoadRunner
fi

php spark burner:start
