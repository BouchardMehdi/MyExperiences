FROM node:22-alpine AS build

WORKDIR /app

ARG PUBLIC_BASE_PATH=""
ARG PUBLIC_API_BASE="/api"
ENV PUBLIC_BASE_PATH=${PUBLIC_BASE_PATH}
ENV PUBLIC_API_BASE=${PUBLIC_API_BASE}

COPY package.json package-lock.json* ./
RUN npm ci

COPY . .
RUN npm run build

FROM nginx:1.27-alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/build /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
