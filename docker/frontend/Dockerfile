# syntax=docker/dockerfile:1
FROM node:20-alpine

WORKDIR /app

# Copy package files (from the build context root)
COPY frontend/package*.json ./
RUN npm ci || npm install

# Copy the rest of the sources
COPY frontend/ ./

EXPOSE 5173
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
