<?php

declare(strict_types=1);

namespace App\Cli\Commands;

use App;
use App\Books\Book;
use App\Catalog\Brand;
use App\Cli\CliCommand;
use App\Cli\CliExitCode;
use App\Cli\Input\CliInput;
use App\Cli\Output\CliOutput;
use App\Cli\Output\Foreground;
use App\Finance\Currency;
use App\Finance\CurrencyCode;
use App\Geographic\Area;
use App\Geographic\Country;
use App\Languages\Phrase;
use App\Sessions\IpAddress;
use App\Sessions\Session;
use App\StoreParsers\Entities\Store;
use App\Telegram\Bots\Bot;
use App\Telegram\Systems\SystemCode;
use App\Users\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Kuvardin\DataFilter\DataFilter;
use Kuvardin\TelegramBotsApi\Bot as Api;
use Kuvardin\TelegramBotsApi\Exceptions\TelegramBotsApiException;
use Kuvardin\TinyOrm\Column;
use Kuvardin\TinyOrm\EntityAbstract;
use Kuvardin\TinyOrm\Exception\AlreadyExists;
use Kuvardin\TinyOrm\Sorting\Sorting;
use Kuvardin\TinyOrm\Sorting\SortingSettings;

class Init extends CliCommand
{
    private const array LOG_DIRS = [
        LOGS_DIR . '/errors',
        LOGS_DIR . '/nginx',
    ];

    public static function requirePdoConnection(): bool
    {
        return true;
    }

    public static function execute(CliInput $input): int
    {
        CliOutput::message('Initialization started', true);
        self::initDirs();
        self::initUsers();
        self::initBooks();
        CliOutput::message('Initialization finished', true);
        return CliExitCode::OK;
    }

    private static function initDirs(): void
    {
        foreach (self::LOG_DIRS as $log_dir) {
            App::requireDir($log_dir);
            chmod($log_dir, 0777);
        }
    }

    private static function initUsers(): void
    {
        CliOutput::message('Users initialization');

        $users_data = require INCLFILES_DIR . '/users.php';
        foreach ($users_data as $user_data) {
            $username = DataFilter::requireNotEmptyString($user_data['username']);
            if (User::findOneByUsername($username) === null) {
                User::create(
                    phone_number: $user_data['phone_number'],
                    username: $username,
                    password: $user_data['password'] ?? null,
                    first_name: $user_data['first_name'] ?? null,
                    last_name: $user_data['last_name'] ?? null,
                    middle_name: $user_data['middle_name'] ?? null,
                );

                CliOutput::message("User $username created", tabs: 1);
            }
        }
    }

    private static function initBooks(): void
    {
        CliOutput::message('Books initialization');

        $books_data = require INCLFILES_DIR . '/books.php';
        foreach ($books_data as $book_data) {
            $isbn = App\Utils\Isbn::fromString($book_data['isbn']);

            if (Book::findOneByIsbn($isbn) === null) {
                Book::create(
                    isbn: $isbn,
                    title: $book_data['title'],
                    author: $book_data['author'],
                    year_published: $book_data['year_published'],
                );

                CliOutput::message("Book $isbn created", tabs: 1);
            }
        }
    }
}