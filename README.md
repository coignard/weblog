# Weblog

A minimalistic, plain text-based blog engine written in PHP. It's designed to display content as plain text, keeping it simple, light, and easy to set up, with no need for a database.

[![weblog](https://github.com/coignard/weblog/assets/119790348/c3e9b2e1-0461-42a1-9838-069104819a16)](https://renecoignard.com/)

## Demo

See it in action [here](https://renecoignard.com/).

## Features

- **Text Files as Posts**: Just write your blog posts as plain text files.
- **Simple Configuration**: Manage settings with an easy-to-edit YAML config file.
- **No Database Needed**: No need to set up a database, as all data is stored in files.
- **Customizable Display**: Adjust text width and other display settings through the configuration.

## Getting Started

### Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/coignard/weblog.git
   ```
2. **Place Your Posts**
   Save your blog posts as `.txt` files in the `weblog/custom-category-name/` directory.
3. **Configure**
   Open `config.yml` and adjust the settings to match your preferences.

You're all set! Happy blogging!

### Setting Up Your Web Server

If you're using **nginx**, here's a basic setup you can start with. This configuration assumes Weblog is installed in `/var/www/weblog`.

```nginx
server {
    listen 80;
    server_name example.com;

    root /var/www/weblog;
    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ @rewrite;
    }

    location @rewrite {
        rewrite ^/([^/]+)$ $scheme://$host/$1/ permanent;
        rewrite ^/(.*)/$ /index.php?go=$1 last;
    }

    location ~ \.php$ {
        include fastcgi-php.conf;
        fastcgi_pass php-fpm;
    }
}
```

Replace `example.com` with your actual domain.

## Usage

Navigate to your blog's URL. The homepage will display a list of all posts. Access individual posts by appending `/your-post-slug/` to the URL.

## Contributing

Contributions are welcome! For major changes, please open an issue first to discuss what you would like to change.

## License

This project is open-sourced under the MIT License. See the [LICENSE](LICENSE) file for more details.
