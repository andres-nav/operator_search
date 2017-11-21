#!/bin/bash

if [ -z "$1" ] ; then
  echo "Usage: $0 username"
  exit 1
fi

echo "get $1" | nc localhost 1922
