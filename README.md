# Weblog

A minimalistic, plain text-based blog engine written in PHP, inspired by the simplicity and structure of RFC format. It's designed to display content as plain text, keeping it simple, light, and easy to set up, with no need for a database.

[![Weblog](https://github.com/coignard/weblog/assets/119790348/e6abdedf-dfd0-4850-8dcb-f3576681b5f5)](https://renecoignard.com/)

## Demo

See it in action [here](https://renecoignard.com/).

## Features

- **Text Files as Posts**: Write your blog posts as plain text files.
- **Simple Configuration**: Manage settings through a simple `config.ini` file.
- **No Database Needed**: All data is stored in text files, whoa!

## Getting Started

### Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/coignard/weblog.git
   ```
2. **Configure**
   Open `config.ini` and adjust the settings as needed (see Configuration section below).
3. **Deploy Posts**
   Save your blog posts as `.txt` files in the `weblog/your-category-name/` directory.

It's that easy! Happy blogging!

### Configuration

Edit the `config.ini` file in the root directory to setup your weblog:

- `line_width`: Maximum line width for content rendering (default: 72).
- `prefix_length`: Length of prefix used in formatted text output (default: 3).
- `weblog_dir`: Directory path where blog posts are stored (default: `/weblog/`).
- `domain`: The domain name where your blog is hosted (default: `http://localhost`).

### Web Server Setup

Here is a basic nginx configuration:

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
        rewrite ^/(.+)\.txt/$ $scheme://$host/$1.txt permanent;
        rewrite ^/(.+)\.txt$ /index.php?go=$1 last;
        rewrite ^/([^/]+)$ $scheme://$host/$1/ permanent;
        rewrite ^/(.*)/$ /index.php?go=$1 last;
    }

    location ~ \.php$ {
        include fastcgi-php.conf;
        fastcgi_pass php-fpm;
    }

    location ~* \.ini$ {
        deny all;
    }
}
```

### Usage

- Navigate to your blog's URL to see a list of all posts.
- Access individual posts by appending `/your-post-name/` to the URL.
- Access posts from a specific date by `/YYYY/MM/DD/`.
- View all posts under a category by `/your-category-name/`.

## Contributing

Contributions are welcome! For major changes, please open an issue first to discuss what you would like to change.

## License

This project is open-sourced under the MIT License. See the [LICENSE](LICENSE) file for more details.
