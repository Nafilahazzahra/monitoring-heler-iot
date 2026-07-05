#!/bin/sh

# Default to localhost if DOMAIN_NAME is not set
DOMAIN_NAME=${DOMAIN_NAME:-localhost}

echo "=== Checking SSL Certificate status for ${DOMAIN_NAME} ==="

CERT_DIR="/etc/letsencrypt/live/${DOMAIN_NAME}"

if [ ! -f "${CERT_DIR}/fullchain.pem" ] || [ ! -f "${CERT_DIR}/privkey.pem" ]; then
    echo "SSL Certificate files not found at ${CERT_DIR}."
    echo "Creating temporary self-signed certificate for ${DOMAIN_NAME} to allow Nginx to start..."
    
    mkdir -p "${CERT_DIR}"
    
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout "${CERT_DIR}/privkey.pem" \
        -out "${CERT_DIR}/fullchain.pem" \
        -subj "/CN=${DOMAIN_NAME}"
        
    echo "Temporary self-signed certificate successfully generated!"
else
    echo "Valid SSL certificate files found. Skipping self-signed generation."
fi

echo "=== SSL Check Complete ==="
