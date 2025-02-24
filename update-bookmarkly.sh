#!/bin/bash

CURRENT_VERSION=$(cat /var/www/html/bookmarkly/version.txt 2>/dev/null || echo "0.0")
BOOKMARKLY_VERSION="${BOOKMARKLY_VERSION:-1.7}"
DOWNLOAD_URL="https://bookmarkly.nl/download/bookmarkly_update_${BOOKMARKLY_VERSION}.zip"
TEMP_DIR="/tmp"
APP_DIR="/var/www/html/bookmarkly"
DATA_DIR="${APP_DIR}/data"

if (( $(echo "$CURRENT_VERSION >= $BOOKMARKLY_VERSION" | bc -l) )); then
    echo "Current version ($CURRENT_VERSION) is up to date. Target version: $BOOKMARKLY_VERSION"
    exit 0
fi

echo "Starting Bookmarkly update process..."
echo "Updating from version $CURRENT_VERSION to $BOOKMARKLY_VERSION"

TEMP_EXTRACT_DIR=$(mktemp -d)
cd ${TEMP_DIR}

echo "Downloading Bookmarkly version ${BOOKMARKLY_VERSION}..."
curl -L -o bookmarkly.zip ${DOWNLOAD_URL}

if [ $? -ne 0 ]; then
    echo "Failed to download Bookmarkly"
    rm -f bookmarkly.zip
    exit 1
fi

echo "Extracting files..."
unzip -q bookmarkly.zip -d ${TEMP_EXTRACT_DIR}

echo "Protecting data directory..."
if [ -d "${DATA_DIR}" ]; then
    mv ${DATA_DIR} ${TEMP_DIR}/data_backup
fi

echo "Installing new version..."
if [ -d "${TEMP_EXTRACT_DIR}/bookmarkly" ]; then
    cp -r ${TEMP_EXTRACT_DIR}/bookmarkly/* ${APP_DIR}/
else
    cp -r ${TEMP_EXTRACT_DIR}/* ${APP_DIR}/
fi

echo "Restoring data directory..."
if [ -d "${TEMP_DIR}/data_backup" ]; then
    rm -rf ${APP_DIR}/data
    mv ${TEMP_DIR}/data_backup ${DATA_DIR}
fi

echo "Setting permissions..."
chown -R www-data:www-data ${APP_DIR}
chmod -R 755 ${APP_DIR}
chmod -R 777 ${DATA_DIR}

echo "Cleaning up..."
rm -f ${TEMP_DIR}/bookmarkly.zip
rm -rf ${TEMP_EXTRACT_DIR}

echo "Bookmarkly update completed" 
