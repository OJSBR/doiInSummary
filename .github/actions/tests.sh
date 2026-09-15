#!/bin/bash

set -e

npx cypress run  --headless --browser chrome  --config '{"specPattern":["plugins/generic/doiInSummary/cypress/tests/functional/*.cy.js"]}'
