# Etapa 1: compilar Angular
FROM node:20-alpine AS builder

WORKDIR /app

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci

COPY frontend/ .

# URLs de las APIs en produccion (rutas relativas via gateway)
RUN sed -i "s|securityUrl:.*|securityUrl: '/security/api',|g" src/environments/environment.ts
RUN sed -i "s|coreUrl:.*|coreUrl: '/api'|g" src/environments/environment.ts

RUN npm run build

# Etapa 2: Nginx sirve la SPA compilada
FROM nginx:1.27-alpine

COPY --from=builder /app/dist/trazabilidad-frontend/browser /usr/share/nginx/html
COPY docker/gateway/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
