FROM nginx:alpine

# Install openssl for generating temporary certs
RUN apk add --no-cache openssl

# Copy configuration templates
COPY templates /etc/nginx/templates

# Copy entrypoint helper script
COPY 10-create-self-signed.sh /docker-entrypoint.d/10-create-self-signed.sh

# Remove CRLF line endings (Windows compatibility) and make the script executable
RUN tr -d '\r' < /docker-entrypoint.d/10-create-self-signed.sh > /tmp/10-create-self-signed.sh && \
    mv /tmp/10-create-self-signed.sh /docker-entrypoint.d/10-create-self-signed.sh && \
    chmod +x /docker-entrypoint.d/10-create-self-signed.sh
