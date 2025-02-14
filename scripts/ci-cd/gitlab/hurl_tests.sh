#!/bin/bash

# load environment variables
set -a && source .env && set +a

# Basic health tests
TESTS="./tests/Hurl/health.hurl"

## feature tests
if $FEATURE_BACKEND; then
  TESTS+=" ./tests/Hurl/backend.hurl"
fi
if $FEATURE_APP_ACCOUNTS; then
  TESTS+=" ./tests/Hurl/login.hurl"
fi

hurl --variables-file .env --test $TESTS --variable SERVER_URL=$SERVER_URL
