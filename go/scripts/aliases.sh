#!/bin/bash
COLS=$(tput cols)
LINES=$(tput lines)

alias god='docker compose exec -Te COLUMNS=$COLS -e LINES=$LINES php drush'
alias godr='docker compose exec -Te COLUMNS=$COLS -e LINES=$LINES php drupal'
alias gor='docker compose exec -Te COLUMNS=$COLS -e LINES=$LINES php vendor/bin/robo'
alias goc='docker compose exec -Te COLUMNS=$COLS -e LINES=$LINES php composer'