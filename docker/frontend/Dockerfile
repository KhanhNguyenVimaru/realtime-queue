FROM node:20-alpine

WORKDIR /app

RUN corepack enable \
    && corepack prepare pnpm@10.33.0 --activate

COPY docker/frontend/entrypoint.sh /usr/local/bin/frontend-entrypoint
RUN chmod +x /usr/local/bin/frontend-entrypoint

ENTRYPOINT ["frontend-entrypoint"]
