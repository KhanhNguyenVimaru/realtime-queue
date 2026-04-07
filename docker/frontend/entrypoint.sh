#!/usr/bin/env sh
set -e

if [ ! -x /app/node_modules/.bin/nuxt ]; then
  pnpm install
fi

exec "$@"
