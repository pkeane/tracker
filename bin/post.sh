#!/bin/sh

curl -X post --data-binary @sample.json -H 'Content-type: application/json' -u pkeane:pass http://pkeane.net/popdb/items

