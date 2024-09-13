<?php

declare(strict_types=1);

namespace App\Books;

use App;
use App\Interfaces\CreatableInterface;
use App\Utils\Isbn;
use Kuvardin\TinyOrm\EntityAbstract;
use App\Traits\CreationDateRequiredTrait;
use Kuvardin\TinyOrm\Connection;
use Kuvardin\TinyOrm\Table;
use Kuvardin\TinyOrm\Exception\AlreadyExists;

class Book extends EntityAbstract
{
    use CreationDateRequiredTrait;

    public const string COL_ISBN = 'isbn';
    public const string COL_TITLE = 'title';
    public const string COL_AUTHOR = 'author';
    public const string COL_YEAR_PUBLISHED = 'year_published';

    protected string $isbn;
    protected string $title;
    protected string $author;
    protected ?int $year_published;

    public function __construct(Connection $pdo, Table $table, array $data)
    {
        parent::__construct($pdo, $table, $data);
        $this->isbn = $data[self::COL_ISBN];
        $this->title = $data[self::COL_TITLE];
        $this->author = $data[self::COL_AUTHOR];
        $this->year_published = $data[self::COL_YEAR_PUBLISHED];
        $this->creation_date = $data[CreatableInterface::COL_CREATION_DATE];
    }

    public static function getEntityTableDefault(): Table
    {
        return new Table('books');
    }

    public static function findOneByIsbn(Isbn $isbn): ?self
    {
        return self::findOneByConditions([
            self::COL_ISBN => $isbn->value,
        ]);
    }

    /**
     * @throws AlreadyExists
     */
    public static function create(
        Isbn $isbn,
        string $title,
        string $author,
        int $year_published = null,
        int $creation_date = null,
    ): self
    {
        return self::createByValuesSet([
            self::COL_ISBN => $isbn->value,
            self::COL_TITLE => $title,
            self::COL_AUTHOR => $author,
            self::COL_YEAR_PUBLISHED => $year_published,
            CreatableInterface::COL_CREATION_DATE => $creation_date ?? time(),
        ]);
    }

    public function getIsbnValue(): string
    {
        return $this->isbn;
    }

    public function setIsbn(Isbn $isbn): self
    {
        $this->setFieldValue(self::COL_ISBN, $this->isbn, $isbn->value);
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->setFieldValue(self::COL_TITLE, $this->title, $title);
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->setFieldValue(self::COL_AUTHOR, $this->author, $author);
        return $this;
    }

    public function getYearPublished(): ?int
    {
        return $this->year_published;
    }

    public function setYearPublished(?int $year_published): self
    {
        $this->setFieldValue(self::COL_YEAR_PUBLISHED, $this->year_published, $year_published);
        return $this;
    }
}
