## Breed Liker WebApp
 
## Requirements

| Tools   | Description |
|---------|-------------|
| PHP     | 8.2 >= 8.3  |
| Laravel | 11.*        |

  <br>
 
## Installation

Clone the repo locally:

```sh
git clone https://github.com/nick0221/breedLiker.git
```

<br>
Navigate to the Project Directory:

```sh
cd breedliker
```

Install PHP dependencies:

```sh
composer install
```

Setup configuration:

```sh
cp .env.example .env
```

Generate application key:

```sh
php artisan key:generate
```

 
Run database migrations:

```sh
php artisan migrate
```
  

Run the dev server (the output will give the address):

```sh
php artisan serve
```

<br><br>
Good to go! Visit the url in your browser, and signup:
 ```sh
localhost:8000
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).