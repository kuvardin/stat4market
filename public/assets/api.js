import axios from "axios";

export default class Api {

  /**
   * @type {Api|null}
   */
  static _instance = null;

  /**
   * JWT access токен
   * @type {Api.AccessToken|null}
   * @private
   */
  _accessToken = null;

  /**
   * JWT refresh токен
   * @type {Api.AccessToken|null}
   * @private
   */
  _refreshToken = null;

  // noinspection JSValidateJSDoc
  /**
   * @type {AxiosInstance}
   * @private
   */
  _axiosInstance;

  /**
   * @param {Api.AccessToken|null} accessToken
   * @param {Api.AccessToken|null} refreshToken
   */
  constructor(accessToken = null, refreshToken = null) {
    this._accessToken = accessToken;
    this._refreshToken = refreshToken;
    this._axiosInstance = axios.create({
      baseURL: `${window.location.protocol}//${window.location.hostname}:${window.location.port}/`,
      responseType: "json",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
    });
  };

  /**
   * @returns {Api}
   */
  static getInstance() {
    if (Api._instance === null) {
      Api._instance = new Api;
    }

    return Api._instance;
  }

  /**
   * @param {Api.AccessToken|null} accessToken
   * @param {boolean} writeToLocalStorage
   */
  setAccessToken(accessToken, writeToLocalStorage = false) {
    this._accessToken = accessToken;
    if (writeToLocalStorage) {
      localStorage.setItem(
        'access_token',
        accessToken === null
          ? null
          : JSON.stringify({ "token": accessToken.token, "expires_at": accessToken.expiresAt }),
      );
    }
  }

  /**
   * @param {Api.AccessToken|null} refreshToken
   * @param {boolean} writeToLocalStorage
   */
  setRefreshToken(refreshToken, writeToLocalStorage = false) {
    this._refreshToken = refreshToken;
    if (writeToLocalStorage) {
      localStorage.setItem(
        'refresh_token',
        refreshToken === null
          ? null
          : JSON.stringify({ "token": refreshToken.token, "expires_at": refreshToken.expiresAt }),
      );
    }
  }

  static Language = class {
    static CODE_RU = 'ru';
    static CODE_KK = 'kk';
    static CODE_EN = 'en';

    /**
     * @type {string[]}
     */
    static ALL = [this.CODE_RU, this.CODE_KK, this.CODE_EN];

    /**
     * Проверка кода языка на существование
     * @param {string} code
     * @return {boolean}
     */
    static checkCode(code) {
      return Api.Language.ALL.includes(code);
    }
  };

  static Action = class {
    static SHOW = 1;
    static CREATE = 2;
    static EDIT = 4;
    static DELETE = 8;

    static LIST_ALL = [
      this.SHOW,
      this.CREATE,
      this.EDIT,
      this.DELETE,
    ];

    static SUM_ALL = this.SHOW | this.CREATE | this.EDIT | this.DELETE;

    /**
     * Проверка прав доступа
     * @param {number} allowedActions Бинарная сумма разрешенных действий
     * @param {number} requiredActions Бинарная сумма требуемых дейстий
     * @param {boolean} requireAll Флаг "Требовать разрешение сразу всех действий"
     * @return boolean
     */
    static check(allowedActions, requiredActions, requireAll = true)
    {
      if (requireAll) {
        return (allowedActions & requiredActions) === requiredActions;
      }

      return (allowedActions & requiredActions) !== 0;
    }
  };

  static Phrase = class {
    /**
     * @type {Map<string,string|null>} Значение фразы на языках
     */
    values = new Map();

    constructor(data) {
      for (let langCode in data) {
        this.values.set(langCode, data[langCode]);
      }
    }

    /**
     * @return {boolean}
     */
    isEmpty() {
      for (let langCode in Object.fromEntries(this.values)) {
        if (this.values.get(langCode) !== null) {
          return false;
        }
      }

      return true;
    }

    /**
     * Затребовать фразу на указанном языке либо на любом другом
     * @param {string} langCode
     * @return string
     */
    require(langCode) {
      if (this.values.has(langCode) && this.values.get(langCode) !== null) {
        return this.values.get(langCode);
      }

      for (let langCode of this.values.keys()) {
        if (this.values.get(langCode) !== null) {
          return this.values.get(langCode);
        }
      }

      console.error('Phrase is empty ' + langCode, this.values);
      return 'EMPTY_PHRASE';
    }
  };

  /**
   * Полученные ошибки
   */
  static ErrorsCollection = class {
    /**
     * @type {Api.Error[]} Список ошибок
     */
    errors = [];

    /**
     * @type {number[]} Список кодов
     */
    codes = [];

    /**
     * @param {Api.Error} error
     */
    addError(error) {
      this.errors.push(error);
      this.codes.push(error.code);
    }

    /**
     * Поиск кода ошибки в списке полученных
     * @param {number} codes
     * @return {boolean}
     */
    hasCode(...codes) {
      for (code of codes) {
        if (this.codes.includes(code)) {
          return true;
        }
      }

      return false;
    }
  };

  /**
   * Книга<br><br>
   * <a href="https://aspans.com/api/v1_doc#book">Documentation</a>
   */
  static Book = class {
    /**
     * ID
     * @type {Number}
     */
    id;

    /**
     * Наименование
     * @type {String}
     */
    title;

    /**
     * ISBN
     * @type {String}
     */
    isbn;

    /**
     * Автор
     * @type {String}
     */
    author;

    /**
     * Год издания
     * @type {Number|null}
     */
    yearPublished;

    /**
     * Таймштамп создания записи
     * @type {Date}
     */
    createdAt;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.id = data['id'];
      this.title = data['title'];
      this.isbn = data['isbn'];
      this.author = data['author'];
      this.yearPublished = data['year_published'];
      this.createdAt = new Date(data['created_at'] * 1000);
    }
  };

  /**
   * Выборка книг<br><br>
   * <a href="https://aspans.com/api/v1_doc#bookslist">Documentation</a>
   */
  static BooksList = class {
    /**
     * Книги
     * @type {Api.Book[]}
     */
    books = [];

    /**
     * Данные о выборке
     * @type {Api.SelectionData}
     */
    selectionData;

    /**
     * @param {object} data
     */
    constructor(data) {
      data['books'].forEach((books) => {
        this.books.push(new Api.Book(books));
      });
      this.selectionData = new Api.SelectionData(data['selection_data']);
    }
  };

  /**
   * Информация об ошибке<br><br>
   * <a href="https://aspans.com/api/v1_doc#error">Documentation</a>
   */
  static Error = class {
    /**
     * @type {Number}
     */
    code;

    /**
     * @type {String|null}
     */
    inputField;

    /**
     * @type {Api.Phrase}
     */
    description;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.code = data['code'];
      this.inputField = data['input_field'];
      this.description = new Api.Phrase(data['description']);
    }
  };

  /**
   * JWT-токен<br><br>
   * <a href="https://aspans.com/api/v1_doc#jwttoken">Documentation</a>
   */
  static JwtToken = class {
    /**
     * Значение токена
     * @type {String}
     */
    value;

    /**
     * Дата истечения
     * @type {Date}
     */
    expirationDate;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.value = data['value'];
      this.expirationDate = new Date(data['expiration_date'] * 1000);
    }
  };

  /**
   * Пара JWT-токенов<br><br>
   * <a href="https://aspans.com/api/v1_doc#jwttokenspair">Documentation</a>
   */
  static JwtTokensPair = class {
    /**
     * Токен для доступа
     * @type {Api.JwtToken}
     */
    accessToken;

    /**
     * Токен для обновления
     * @type {Api.JwtToken}
     */
    refreshToken;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.accessToken = new Api.JwtToken(data['access_token']);
      this.refreshToken = new Api.JwtToken(data['refresh_token']);
    }
  };

  /**
   * Информация о выборке<br><br>
   * <a href="https://aspans.com/api/v1_doc#selectiondata">Documentation</a>
   */
  static SelectionData = class {
    /**
     * Лимит
     * @type {Number}
     */
    limit;

    /**
     * Общее количество элементов
     * @type {Number}
     */
    totalAmount;

    /**
     * Страница
     * @type {Number}
     */
    page;

    /**
     * Общее количество страниц
     * @type {Number}
     */
    pagesTotal;

    /**
     * Поле, по которому производится сортировка
     * @type {String}
     */
    sortBy;

    /**
     * Направление сортировки
     * @type {String}
     */
    sortDirection;

    /**
     * Варианты полей сортировки
     * @type {String[]}
     */
    sortVariants = [];

    /**
     * @param {object} data
     */
    constructor(data) {
      this.limit = data['limit'];
      this.totalAmount = data['total_amount'];
      this.page = data['page'];
      this.pagesTotal = data['pages_total'];
      this.sortBy = data['sort_by'];
      this.sortDirection = data['sort_direction'];
      this.sortVariants = data['sort_variants'];
    }

    /**
     * Получить данные о выборки для следующей страницы
     * @return {Api.SelectionData|null}
     */
    getNext() {
      if (this.pagesTotal < this.page) {
        return new Api.SelectionData({
          'limit': this.limit,
          'total_amount': this.totalAmount,
          'page': this.page + 1,
          'pages_total': this.pagesTotal,
          'sort_by': this.sortBy,
          'sort_direction': this.sortDirection,
          'sort_variants': this.sortVariants,
        });
      }

      return null;
    }
  };

  /**
   * Данные о сессии<br><br>
   * <a href="https://aspans.com/api/v1_doc#session">Documentation</a>
   */
  static Session = class {
    /**
     * Код языка
     * @type {String}
     */
    languageCode;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.languageCode = data['language_code'];
    }
  };

  /**
   * Сессия<br><br>
   * <a href="https://aspans.com/api/v1_doc#sessioninfo">Documentation</a>
   */
  static SessionInfo = class {
    /**
     * Пользователь
     * @type {Api.User|null}
     */
    user;

    /**
     * Сессия
     * @type {Api.Session}
     */
    session;

    /**
     * JWT-токены
     * @type {Api.JwtTokensPair|null}
     */
    tokens;

    /**
     * Временная зона
     * @type {String}
     */
    timezone;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.user = data['user'] === null ? null : new Api.User(data['user']);
      this.session = new Api.Session(data['session']);
      this.tokens = data['tokens'] === null ? null : new Api.JwtTokensPair(data['tokens']);
      this.timezone = data['timezone'];
    }
  };

  /**
   * Пользователь<br><br>
   * <a href="https://aspans.com/api/v1_doc#user">Documentation</a>
   */
  static User = class {
    /**
     * ID
     * @type {Number}
     */
    id;

    /**
     * Номер телефона
     * @type {String|null}
     */
    phoneNumber;

    /**
     * Юзернейм
     * @type {String|null}
     */
    username;

    /**
     * Имя
     * @type {String}
     */
    firstName;

    /**
     * Фамилия
     * @type {String|null}
     */
    lastName;

    /**
     * Отчество
     * @type {String|null}
     */
    middleName;

    /**
     * Дата последнего посещения
     * @type {Date|null}
     */
    lastRequestDate;

    /**
     * Флаг "Онлайн"
     * @type {Boolean}
     */
    isOnline;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.id = data['id'];
      this.phoneNumber = data['phone_number'];
      this.username = data['username'];
      this.firstName = data['first_name'];
      this.lastName = data['last_name'];
      this.middleName = data['middle_name'];
      this.lastRequestDate = data['last_request_date'] === null ? null : new Date(data['last_request_date'] * 1000);
      this.isOnline = data['is_online'];
    }
  };


  /**
   * Отправка запроса к API
   * @param {string} method Метод API
   * @param {object} data Данные
   * @param {function|null} before Callback-функция для преобработки данных ответа
   * @param {boolean} allowRecursive Защита от бесконечной рекурсии
   * @throws {Api.ErrorsCollection}
   * @return {Promise}
   */
  async request(method, data, before = null, allowRecursive = true) {
    const url = '/api/' + method;

    if (this._accessToken !== null && method !== 'v1/refreshToken') {
      if ((this._accessToken.expiresAt - 100000) < Math.floor(Date.now() / 1000)) {
        const newTokensPair = await this.refreshToken({token: this._refreshToken.token});
        this.setAccessToken(newTokensPair.accessToken, true);
        this.setRefreshToken(newTokensPair.refreshToken, true);
        console.log('Access tokens updated automatically ;)');
      }

      this._axiosInstance.defaults.headers.Authorization = `Bearer ${this._accessToken.token}`;
    } else if (this._axiosInstance.defaults.headers.hasOwnProperty('Authorization')) {
      delete this._axiosInstance.defaults.headers.Authorization;
    }

    try {
      let response = await this._axiosInstance.post(url, data);

      const result = response.data['result'];
      const apiErrorsData = response.data['errors'];

      if (apiErrorsData.length) {
        let errorsCollection = new Api.ErrorsCollection;

        apiErrorsData.forEach((apiErrorData) => {
          errorsCollection.addError(new Api.Error(apiErrorData));
        });

        throw errorsCollection;
      }

      if (before !== null) {
        return before(result);
      }

      return result;
    } catch (e) {
      if (e?.response?.status === 401) {
        const newTokensPair = await this.refreshToken({token: this._refreshToken.token});
        this.setAccessToken(newTokensPair.accessToken, true);
        this.setRefreshToken(newTokensPair.refreshToken, true);
        console.log('Access tokens updated automatically after error ;)');
        return this.request(method, data, before, false);
      } else {
        throw e;
      }
    }
  };

  /**
   * ### Удаление книги
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2005: Книга не найдена
   * - Error #3001: Не выбрана книга
   *
   * [Documentation](https://localhost:4443/api/v1_doc#deleteBooksWithId)
   * @param {Object} param0
   * @param {Number} param0.id ID книги
   * @throws {Api.ErrorsCollection}
   * @return {Promise<void>}
   */
  async deleteBooksWithId({
    id
  }) {
    return await this.request(
        'v1/deleteBooksWithId',
        {
              id: id,
        },
    );
  }

  /**
   * ### Получение выборки книг
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2006: Книги не найдены
   *
   * Sort by variants: title, year_published, author, isbn, id
   *
   * Sort by default: id
   *
   * [Documentation](https://localhost:4443/api/v1_doc#getBooks)
   * @param {Object} param0
   * @param {Number|null} [param0.page] Номер страницы
   * @param {Number|null} [param0.limit] Количество элементов на одну страницу
   * @param {String|null} [param0.sortBy] Поле сортировки
   * @param {String|null} [param0.sortDirection] Направление сортировки
   * @param {String|null} [param0.query] Поисковый запрос
   * @param {Number|null} [param0.yearPublished] Дата публикациия
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.BooksList>}
   */
  async getBooks({
    page = null,
    limit = null,
    sortBy = null,
    sortDirection = null,
    query = null,
    yearPublished = null
  }) {
    return await this.request(
        'v1/getBooks',
        {
              page: page,
              limit: limit,
              sort_by: sortBy,
              sort_direction: sortDirection,
              query: query,
              year_published: yearPublished,
        },
        (response => new Api.BooksList(response))
    );
  }

  /**
   * ### Получение книги
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2005: Книга не найдена
   * - Error #3001: Не выбрана книга
   *
   * [Documentation](https://localhost:4443/api/v1_doc#getBooksWithId)
   * @param {Object} param0
   * @param {Number} param0.id ID книги
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.Book>}
   */
  async getBooksWithId({
    id
  }) {
    return await this.request(
        'v1/getBooksWithId',
        {
              id: id,
        },
        (response => new Api.Book(response))
    );
  }

  /**
   * ### Создание книги
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2004: Такой элемент уже существует
   * - Error #3002: Не введено наименование книги
   * - Error #3003: Не введен автор книги
   * - Error #3004: Не введен ISBN
   * - Error #3005: Некорректный ISBN
   *
   * [Documentation](https://localhost:4443/api/v1_doc#postBooks)
   * @param {Object} param0
   * @param {String} param0.title Наименование
   * @param {String} param0.author Автор
   * @param {String} param0.isbn ISBN
   * @param {Number|null} [param0.yearPublished] Дата год публикации
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.Book>}
   */
  async postBooks({
    title,
    author,
    isbn,
    yearPublished = null
  }) {
    return await this.request(
        'v1/postBooks',
        {
              title: title,
              author: author,
              year_published: yearPublished,
              isbn: isbn,
        },
        (response => new Api.Book(response))
    );
  }

  /**
   * ### Редактирование книги
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2004: Такой элемент уже существует
   * - Error #2005: Книга не найдена
   * - Error #3001: Не выбрана книга
   * - Error #3005: Некорректный ISBN
   *
   * [Documentation](https://localhost:4443/api/v1_doc#putBooksWithId)
   * @param {Object} param0
   * @param {Number} param0.id ID книги
   * @param {String|null} [param0.title] Наименование
   * @param {String|null} [param0.author] Автор
   * @param {Number|null} [param0.yearPublished] Год публикации
   * @param {Boolean|null} [param0.clearYearPublished] Очистить год публикации
   * @param {String|null} [param0.isbn] ISBN
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.Book>}
   */
  async putBooksWithId({
    id,
    title = null,
    author = null,
    yearPublished = null,
    clearYearPublished = null,
    isbn = null
  }) {
    return await this.request(
        'v1/putBooksWithId',
        {
              id: id,
              title: title,
              author: author,
              year_published: yearPublished,
              clear_year_published: clearYearPublished,
              isbn: isbn,
        },
        (response => new Api.Book(response))
    );
  }

}

