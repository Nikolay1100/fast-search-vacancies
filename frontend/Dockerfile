# Стейдж для разработки
FROM node:20-alpine as build-stage
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
# Важно: здесь НЕТ RUN npm run build, чтобы ускорить запуск в dev-режиме

# Стейдж сборки (для будущего переключения в Prod)
FROM build-stage as production-build
RUN npm run build

# Стейдж Nginx
FROM nginx:stable-alpine as production-stage
COPY --from=production-build /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
