#!/bin/bash

# Configuratie
CURRENT_VERSION=$(cat /var/www/html/bookmarkly/version.txt 2>/dev/null || echo "0.0")
TARGET_VERSION="${BOOKMARKLY_VERSION:-1.5}"
DOWNLOAD_URL="https://bookmarkly.nl/download/bookmarkly_update_${TARGET_VERSION}.zip"
TEMP_DIR="/tmp"
APP_DIR="/var/www/html/bookmarkly"
DATA_DIR="${APP_DIR}/data"

# Vergelijk versies (gebruikt bc voor floating point vergelijking)
if (( $(echo "$CURRENT_VERSION >= $TARGET_VERSION" | bc -l) )); then
    echo "Current version ($CURRENT_VERSION) is up to date. Target version: $TARGET_VERSION"
    exit 0
fi

echo "Starting Bookmarkly update process..."
echo "Updating from version $CURRENT_VERSION to $TARGET_VERSION"

# Maak temp directory
TEMP_EXTRACT_DIR=$(mktemp -d)
cd ${TEMP_DIR}

# Download de laatste versie
echo "Downloading Bookmarkly version ${TARGET_VERSION}..."
curl -L -o bookmarkly.zip ${DOWNLOAD_URL}

# Controleer of download succesvol was
if [ $? -ne 0 ]; then
    echo "Failed to download Bookmarkly"
    rm -f bookmarkly.zip
    exit 1
fi

# Pak de zip uit in temp directory
echo "Extracting files..."
unzip -q bookmarkly.zip -d ${TEMP_EXTRACT_DIR}

# Backup de data directory
echo "Protecting data directory..."
if [ -d "${DATA_DIR}" ]; then
    mv ${DATA_DIR} ${TEMP_DIR}/data_backup
fi

# Kopieer nieuwe bestanden, maar skip de data directory als die bestaat
echo "Installing new version..."
if [ -d "${TEMP_EXTRACT_DIR}/bookmarkly" ]; then
    cp -r ${TEMP_EXTRACT_DIR}/bookmarkly/* ${APP_DIR}/
else
    cp -r ${TEMP_EXTRACT_DIR}/* ${APP_DIR}/
fi

# Herstel de data directory
echo "Restoring data directory..."
if [ -d "${TEMP_DIR}/data_backup" ]; then
    rm -rf ${APP_DIR}/data
    mv ${TEMP_DIR}/data_backup ${DATA_DIR}
fi

# Zet de juiste rechten
echo "Setting permissions..."
chown -R www-data:www-data ${APP_DIR}
chmod -R 755 ${APP_DIR}
chmod -R 777 ${DATA_DIR}

# Opruimen
echo "Cleaning up..."
rm -f ${TEMP_DIR}/bookmarkly.zip
rm -rf ${TEMP_EXTRACT_DIR}

echo "Bookmarkly update completed" 