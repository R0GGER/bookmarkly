# bookmarkly

```bash
docker pull ghcr.io/r0gger/bookmarkly:main
```

```
services:
  bookmarkly:
    image: ghcr.io/r0gger/bookmarkly:main
    ports:
      - "80:80"
    volumes:
      - bookmarkly_data:/var/www/html/bookmarkly/data
    environment:
      - BOOKMARKLY_VERSION=1.0 
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 10s

volumes:
  bookmarkly_data: 
```
