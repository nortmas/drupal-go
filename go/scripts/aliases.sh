#!/bin/bash
COLS=$(tput cols)
LINES=$(tput lines)

alias god='docker-compose exec -e COLUMNS=$COLS -e LINES=$LINES php drush'
alias godr='docker-compose exec -e COLUMNS=$COLS -e LINES=$LINES php drupal'
alias gor='docker-compose exec -e COLUMNS=$COLS -e LINES=$LINES php vendor/bin/robo'
alias goс='docker-compose exec -e COLUMNS=$COLS -e LINES=$LINES php composer'