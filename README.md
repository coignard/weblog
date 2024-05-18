# Weblog

A minimalistic, plain text-based blog engine written in PHP, inspired by the simplicity and structure of RFC format. It's designed to display content as plain text, keeping it simple, light, and easy to set up, with no need for a database.

[![Weblog](https://github.com/coignard/weblog/assets/119790348/aea837d6-27c8-4f28-a4ff-ebafc4c4e3ae)](https://renecoignard.com/motherfucking-blog/)

## Demo

See it in action [here](https://renecoignard.com/motherfucking-blog/).

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

- `line_width`: Maximum line width for content rendering (default: 72). This controls how text is wrapped in the output.
- `prefix_length`: Length of the prefix used in formatted text output (default: 3). This is used to indent the text content slightly from the left margin.
- `weblog_dir`: Directory path where blog posts are stored (default: `/weblog/`). This specifies where your text files (representing blog posts) are located.
- `domain`: The domain name where your blog is hosted (default: `localhost`). This is used for generating full URLs in the sitemap and RSS feed, as well as for link generation if `show_urls` is set to `Full`.
- `show_powered_by`: Toggle to show or hide the "Powered by Weblog" information in the footer (default: `On`). Set this to `Off` to remove the footer credit.
- `show_urls`: Determines how URLs are displayed on the main page when listing posts (default: `Full`). Options are:
  - `Off`: Do not show any URLs next to the posts.
  - `Short`: Display only the relative path of the post.
  - `Full`: Display the full URL, including the domain, for each post.
- More detailed configuration options are described in the `config.example.ini`.

### Web Server Setup

Here is a basic nginx configuration:

```nginx
server {
    listen 80;

    root /var/www/weblog;
    index index.php;
    charset utf-8;

    server_name example.com;

    location = /favicon.ico { log_not_found off; access_log off; expires max; }
    location = /robots.txt { log_not_found off; access_log off; allow all; }

    error_page 404 = /index.php?go=404;

    location /sitemap.xml {
        rewrite ^/sitemap.xml$ /index.php?go=sitemap.xml last;
    }

    location = /config.ini {
        deny all;
        return 404;
    }

    location = /autoload.php {
        deny all;
        return 404;
    }

    location ~* ^/src/(.*) {
        deny all;
        return 404;
    }

    location ~* ^/weblog/.+ {
        deny all;
        return 404;
    }

    location / {
        try_files $uri $uri/ @rewrite;
    }

    location @rewrite {
        rewrite ^/(.+)\.txt/$ $scheme://$host/$1.txt permanent;
        rewrite ^/(.+)\.txt$ /index.php?go=$1 last;
        rewrite ^/([^/]+)$ $scheme://$host/$1/ permanent;
        rewrite "^/(\d{4})$" /$1/ permanent;
        rewrite "^/(\d{4})/(\d{2})$" /$1/$2/ permanent;
        rewrite "^/(\d{4})/(\d{2})/(\d{2})$" /$1/$2/$3/ permanent;
        rewrite ^/rss/([\w-]+)$ /rss/$1/ permanent;
        rewrite ^/(.*)/$ /index.php?go=$1 last;
    }

    location ~ \.php$ {
        include fastcgi-php.conf;
        fastcgi_pass php-fpm;
    }
}
```

### Usage

- Navigate to your blog's URL to see a list of all posts.
- Access individual posts by appending `/your-post-name/` to the URL.
- Access posts from a specific date by `/YYYY/MM/DD/`.
- View all posts under a category by `/your-category-name/`.

## Security

- Ensure `config.ini` and `/weblog/` is not accessible via the Web.

## Contributing

Contributions are welcome! For major changes, please open an issue first to discuss what you would like to change.

## License

This project is open-sourced under the MIT License. See the [LICENSE](LICENSE) file for more details.
