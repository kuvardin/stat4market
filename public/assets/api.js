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
   * Действие<br><br>
   * <a href="https://aspans.com/api/v1_doc#action">Documentation</a>
   */
  static Action = class {
    /**
     * ID типа
     * @type {String}
     */
    typeId;

    /**
     * ID сессии
     * @type {Number}
     */
    sessionId;

    /**
     * ID авторизации
     * @type {Number|null}
     */
    authorizationId;

    /**
     * ID пользователя
     * @type {Number|null}
     */
    userId;

    /**
     * User-Agent
     * @type {String|null}
     */
    userAgent;

    /**
     * IP адрес
     * @type {String|null}
     */
    ipAddressValue;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.typeId = data['type_id'];
      this.sessionId = data['session_id'];
      this.authorizationId = data['authorization_id'];
      this.userId = data['user_id'];
      this.userAgent = data['user_agent'];
      this.ipAddressValue = data['ip_address_value'];
    }
  };

  /**
   * Филиал интернет-магазина<br><br>
   * <a href="https://aspans.com/api/v1_doc#branch">Documentation</a>
   */
  static Branch = class {
    /**
     * ID
     * @type {Number}
     */
    id;

    /**
     * Наименование
     * @type {Api.Phrase}
     */
    name;

    /**
     * ID интернет-магазина
     * @type {Number}
     */
    storeId;

    /**
     * ID исходного региона
     * @type {Number|null}
     */
    sourceAreaId;

    /**
     * ID региона
     * @type {Number|null}
     */
    areaId;

    /**
     * Локация
     * @type {Api.Location}
     */
    location;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.id = data['id'];
      this.name = new Api.Phrase(data['name']);
      this.storeId = data['store_id'];
      this.sourceAreaId = data['source_area_id'];
      this.areaId = data['area_id'];
      this.location = new Api.Location(data['location']);
    }
  };

  /**
   * Валюта<br><br>
   * <a href="https://aspans.com/api/v1_doc#currency">Documentation</a>
   */
  static Currency = class {
    /**
     * ID
     * @type {Number}
     */
    id;

    /**
     * Код
     * @type {String}
     */
    code;

    /**
     * Символ
     * @type {String}
     */
    symbol;

    /**
     * Формат
     * @type {String}
     */
    format;

    /**
     * Точность
     * @type {Number}
     */
    precision;

    /**
     * Цена 100 долларов
     * @type {Number}
     */
    priceHundredDollars;

    /**
     * Дата последнего обновления
     * @type {Date|null}
     */
    lastUpdateDate;

    /**
     * Дата создания
     * @type {Date}
     */
    creationDate;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.id = data['id'];
      this.code = data['code'];
      this.symbol = data['symbol'];
      this.format = data['format'];
      this.precision = data['precision'];
      this.priceHundredDollars = data['price_hundred_dollars'];
      this.lastUpdateDate = data['last_update_date'] === null ? null : new Date(data['last_update_date'] * 1000);
      this.creationDate = new Date(data['creation_date'] * 1000);
    }
  };

  /**
   * Скидка<br><br>
   * <a href="https://aspans.com/api/v1_doc#discount">Documentation</a>
   */
  static Discount = class {
    /**
     * UUID товара
     * @type {String}
     */
    sourceProductUuid;

    /**
     * ID магазина
     * @type {Number}
     */
    storeId;

    /**
     * Цена товара
     * @type {Number}
     */
    priceValue;

    /**
     * ID валюты
     * @type {Number}
     */
    currencyId;

    /**
     * Средняя цена за 90 дней
     * @type {Number}
     */
    priceAverage90;

    /**
     * Процент разницы за 90 дней
     * @type {Number}
     */
    percents90;

    /**
     * Средняя цена за 180 дней
     * @type {Number}
     */
    priceAverage180;

    /**
     * Процент разницы за 180 дней
     * @type {Number}
     */
    percents180;

    /**
     * Средняя цена за 270 дней
     * @type {Number}
     */
    priceAverage270;

    /**
     * Процент разницы за 270 дней
     * @type {Number}
     */
    percents270;

    /**
     * Средняя цена за все время
     * @type {Number}
     */
    priceAverageTotal;

    /**
     * Процент разницы за все время
     * @type {Number}
     */
    percentsTotal;

    /**
     * Дата создания
     * @type {Number}
     */
    creationDate;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.sourceProductUuid = data['source_product_uuid'];
      this.storeId = data['store_id'];
      this.priceValue = data['price_value'];
      this.currencyId = data['currency_id'];
      this.priceAverage90 = data['price_average_90'];
      this.percents90 = data['percents_90'];
      this.priceAverage180 = data['price_average_180'];
      this.percents180 = data['percents_180'];
      this.priceAverage270 = data['price_average_270'];
      this.percents270 = data['percents_270'];
      this.priceAverageTotal = data['price_average_total'];
      this.percentsTotal = data['percents_total'];
      this.creationDate = data['creation_date'];
    }
  };

  /**
   * Лента изменений цен<br><br>
   * <a href="https://aspans.com/api/v1_doc#discountfeed">Documentation</a>
   */
  static DiscountFeed = class {
    /**
     * ID
     * @type {Number}
     */
    id;

    /**
     * Код
     * @type {String|null}
     */
    code;

    /**
     * Наименование
     * @type {Api.Phrase}
     */
    name;

    /**
     * Описание
     * @type {Api.Phrase|null}
     */
    description;

    /**
     * ID пользователя
     * @type {Number|null}
     */
    userId;

    /**
     * ID валюты
     * @type {Number|null}
     */
    currencyId;

    /**
     * Минимальный процент скидки
     * @type {Number|null}
     */
    discountPercentMin;

    /**
     * Минимальный размер скидки в USD
     * @type {Number|null}
     */
    discountValueMinUsd;

    /**
     * Информация об удалении
     * @type {Api.Action|null}
     */
    deletion;

    /**
     * Дата создания
     * @type {Date}
     */
    creationDate;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.id = data['id'];
      this.code = data['code'];
      this.name = new Api.Phrase(data['name']);
      this.description = data['description'] === null ? null : new Api.Phrase(data['description']);
      this.userId = data['user_id'];
      this.currencyId = data['currency_id'];
      this.discountPercentMin = data['discount_percent_min'];
      this.discountValueMinUsd = data['discount_value_min_usd'];
      this.deletion = data['deletion'] === null ? null : new Api.Action(data['deletion']);
      this.creationDate = new Date(data['creation_date'] * 1000);
    }
  };

  /**
   * Выборка лент уведомлений<br><br>
   * <a href="https://aspans.com/api/v1_doc#discountfeedslist">Documentation</a>
   */
  static DiscountFeedsList = class {
    /**
     * Ленты
     * @type {Api.DiscountFeed[]}
     */
    feeds = [];

    /**
     * Валюты
     * @type {Api.Currency[]}
     */
    currencies = [];

    /**
     * Данные о выборке
     * @type {Api.SelectionData}
     */
    selectionData;

    /**
     * @param {object} data
     */
    constructor(data) {
      data['feeds'].forEach((feeds) => {
        this.feeds.push(new Api.DiscountFeed(feeds));
      });
      data['currencies'].forEach((currencies) => {
        this.currencies.push(new Api.Currency(currencies));
      });
      this.selectionData = new Api.SelectionData(data['selection_data']);
    }
  };

  /**
   * Уведомление об изменении цен<br><br>
   * <a href="https://aspans.com/api/v1_doc#discountfeedsnotification">Documentation</a>
   */
  static DiscountFeedsNotification = class {
    /**
     * ID
     * @type {Number}
     */
    id;

    /**
     * UUID исходного товара
     * @type {String|null}
     */
    sourceProductUuid;

    /**
     * Новая цена (NULL - нет в наличии)
     * @type {Number|null}
     */
    priceValue;

    /**
     * Скидка
     * @type {Api.Discount|null}
     */
    discount;

    /**
     * ID источника уведомления
     * @type {Number}
     */
    sourceId;

    /**
     * ID ленты уведомлений
     * @type {Number|null}
     */
    feedId;

    /**
     * Флаг "Активно"
     * @type {Boolean}
     */
    active;

    /**
     * Дата создания
     * @type {Date}
     */
    creationDate;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.id = data['id'];
      this.sourceProductUuid = data['source_product_uuid'];
      this.priceValue = data['price_value'];
      this.discount = data['discount'] === null ? null : new Api.Discount(data['discount']);
      this.sourceId = data['source_id'];
      this.feedId = data['feed_id'];
      this.active = data['active'];
      this.creationDate = new Date(data['creation_date'] * 1000);
    }
  };

  /**
   * Полная информация об уведомлении о скидке<br><br>
   * <a href="https://aspans.com/api/v1_doc#discountfeedsnotificationfull">Documentation</a>
   */
  static DiscountFeedsNotificationFull = class {
    /**
     * Уведомление о скидке
     * @type {Api.DiscountFeedsNotification}
     */
    notification;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.notification = new Api.DiscountFeedsNotification(data['notification']);
    }
  };

  /**
   * Выборка уведомлений<br><br>
   * <a href="https://aspans.com/api/v1_doc#discountfeedsnotificationslist">Documentation</a>
   */
  static DiscountFeedsNotificationsList = class {
    /**
     * Уведомления
     * @type {Api.DiscountFeedsNotification[]}
     */
    notifications = [];

    /**
     * @param {object} data
     */
    constructor(data) {
      data['notifications'].forEach((notifications) => {
        this.notifications.push(new Api.DiscountFeedsNotification(notifications));
      });
    }
  };

  /**
   * Источник для лент изменений цен<br><br>
   * <a href="https://aspans.com/api/v1_doc#discountfeedssource">Documentation</a>
   */
  static DiscountFeedsSource = class {
    /**
     * ID
     * @type {Number}
     */
    id;

    /**
     * ID пользователя
     * @type {Number|null}
     */
    userId;

    /**
     * ID ленты
     * @type {Number|null}
     */
    feed;

    /**
     * Разрешить/запретить товары из фильтра
     * @type {Boolean}
     */
    allow;

    /**
     * ID магазина
     * @type {Number|null}
     */
    storeId;

    /**
     * ID товара из каталога
     * @type {Number|null}
     */
    catalogProductId;

    /**
     * ID исходного товара
     * @type {Number|null}
     */
    sourceProductId;

    /**
     * ID категории из каталога
     * @type {Number|null}
     */
    catalogCategoryId;

    /**
     * ID исходной категории
     * @type {Number|null}
     */
    sourceCategoryId;

    /**
     * Данные об удалении
     * @type {Api.Action|null}
     */
    deletion;

    /**
     * Данные о создании
     * @type {Api.Action|null}
     */
    creation;

    /**
     * Дата создания
     * @type {Date}
     */
    creationDate;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.id = data['id'];
      this.userId = data['user_id'];
      this.feed = data['feed'];
      this.allow = data['allow'];
      this.storeId = data['store_id'];
      this.catalogProductId = data['catalog_product_id'];
      this.sourceProductId = data['source_product_id'];
      this.catalogCategoryId = data['catalog_category_id'];
      this.sourceCategoryId = data['source_category_id'];
      this.deletion = data['deletion'] === null ? null : new Api.Action(data['deletion']);
      this.creation = data['creation'] === null ? null : new Api.Action(data['creation']);
      this.creationDate = new Date(data['creation_date'] * 1000);
    }
  };

  /**
   * Подписка на изменения цен<br><br>
   * <a href="https://aspans.com/api/v1_doc#discountfeedssubscription">Documentation</a>
   */
  static DiscountFeedsSubscription = class {
    /**
     * ID
     * @type {Number}
     */
    id;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.id = data['id'];
    }
  };

  /**
   * Выборка скидок<br><br>
   * <a href="https://aspans.com/api/v1_doc#discountslist">Documentation</a>
   */
  static DiscountsList = class {
    /**
     * Скидки
     * @type {Api.Discount[]}
     */
    discounts = [];

    /**
     * Товары
     * @type {Api.SourceProduct[]}
     */
    sourceProducts = [];

    /**
     * Магазины
     * @type {Api.Store[]}
     */
    stores = [];

    /**
     * Валюты
     * @type {Api.Currency[]}
     */
    currencies = [];

    /**
     * Данные о выборке
     * @type {Api.SelectionData}
     */
    selectionData;

    /**
     * @param {object} data
     */
    constructor(data) {
      data['discounts'].forEach((discounts) => {
        this.discounts.push(new Api.Discount(discounts));
      });
      data['source_products'].forEach((sourceProducts) => {
        this.sourceProducts.push(new Api.SourceProduct(sourceProducts));
      });
      data['stores'].forEach((stores) => {
        this.stores.push(new Api.Store(stores));
      });
      data['currencies'].forEach((currencies) => {
        this.currencies.push(new Api.Currency(currencies));
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
   * Данные инициализации<br><br>
   * <a href="https://aspans.com/api/v1_doc#initializationdata">Documentation</a>
   */
  static InitializationData = class {
    /**
     * Данные о посетителе
     * @type {Api.SessionInfo}
     */
    sessionInfo;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.sessionInfo = new Api.SessionInfo(data['session_info']);
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
   * Локация<br><br>
   * <a href="https://aspans.com/api/v1_doc#location">Documentation</a>
   */
  static Location = class {
    /**
     * Долгота
     * @type {Number}
     */
    longitude;

    /**
     * Широта
     * @type {Number}
     */
    latitude;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.longitude = data['longitude'];
      this.latitude = data['latitude'];
    }
  };

  /**
   * Изображения товаров<br><br>
   * <a href="https://aspans.com/api/v1_doc#productimages">Documentation</a>
   */
  static ProductImages = class {
    /**
     * Размер 256x256
     * @type {String}
     */
    size256;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.size256 = data['size_256'];
    }
  };

  /**
   * Стоимость товара<br><br>
   * <a href="https://aspans.com/api/v1_doc#productprice">Documentation</a>
   */
  static ProductPrice = class {
    /**
     * ID
     * @type {Number}
     */
    sourceProductId;

    /**
     * Значение цены числом
     * @type {Number|null}
     */
    value;

    /**
     * Значение цены строкой
     * @type {String|null}
     */
    valueFormatted;

    /**
     * ID валюты
     * @type {Number|null}
     */
    currencyId;

    /**
     * ID исходного региона
     * @type {Number|null}
     */
    sourceAreaId;

    /**
     * ID филиала
     * @type {Number|null}
     */
    branchId;

    /**
     * Дата создания
     * @type {Date}
     */
    creationDate;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.sourceProductId = data['source_product_id'];
      this.value = data['value'];
      this.valueFormatted = data['value_formatted'];
      this.currencyId = data['currency_id'];
      this.sourceAreaId = data['source_area_id'];
      this.branchId = data['branch_id'];
      this.creationDate = new Date(data['creation_date'] * 1000);
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
   * Исходный регион<br><br>
   * <a href="https://aspans.com/api/v1_doc#sourcearea">Documentation</a>
   */
  static SourceArea = class {
    /**
     * ID
     * @type {Number}
     */
    id;

    /**
     * ID интернет-магазина
     * @type {Number}
     */
    storeId;

    /**
     * ID родительского исходного региона
     * @type {Number|null}
     */
    parentId;

    /**
     * Географический регион
     * @type {Number|null}
     */
    areaId;

    /**
     * Наименование
     * @type {Api.Phrase}
     */
    name;

    /**
     * Данные об удалении
     * @type {Api.Action|null}
     */
    deletion;

    /**
     * Дата создания
     * @type {Number}
     */
    creationDate;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.id = data['id'];
      this.storeId = data['store_id'];
      this.parentId = data['parent_id'];
      this.areaId = data['area_id'];
      this.name = new Api.Phrase(data['name']);
      this.deletion = data['deletion'] === null ? null : new Api.Action(data['deletion']);
      this.creationDate = data['creation_date'];
    }
  };

  /**
   * Выборка исходных регионов<br><br>
   * <a href="https://aspans.com/api/v1_doc#sourceareaslist">Documentation</a>
   */
  static SourceAreasList = class {
    /**
     * Исходные регионы
     * @type {Api.SourceArea[]}
     */
    sourceAreas = [];

    /**
     * Данные о выборке
     * @type {Api.SelectionData}
     */
    selectionData;

    /**
     * @param {object} data
     */
    constructor(data) {
      data['source_areas'].forEach((sourceAreas) => {
        this.sourceAreas.push(new Api.SourceArea(sourceAreas));
      });
      this.selectionData = new Api.SelectionData(data['selection_data']);
    }
  };

  /**
   * Выборка исходных категорий<br><br>
   * <a href="https://aspans.com/api/v1_doc#sourcecategorieslist">Documentation</a>
   */
  static SourceCategoriesList = class {
    /**
     * Исходные категории
     * @type {Api.SourceCategory[]}
     */
    sourceCategories = [];

    /**
     * Данные о выборке
     * @type {Api.SelectionData}
     */
    selectionData;

    /**
     * @param {object} data
     */
    constructor(data) {
      data['source_categories'].forEach((sourceCategories) => {
        this.sourceCategories.push(new Api.SourceCategory(sourceCategories));
      });
      this.selectionData = new Api.SelectionData(data['selection_data']);
    }
  };

  /**
   * Категория товаров<br><br>
   * <a href="https://aspans.com/api/v1_doc#sourcecategory">Documentation</a>
   */
  static SourceCategory = class {
    /**
     * ID
     * @type {Number}
     */
    id;

    /**
     * ID интернет-магазина
     * @type {Number}
     */
    storeId;

    /**
     * Наименование
     * @type {Api.Phrase}
     */
    name;

    /**
     * Количество товаров
     * @type {Number|null}
     */
    productsTotal;

    /**
     * Количество подкатегорий
     * @type {Number|null}
     */
    childrenTotal;

    /**
     * Информация об удалении
     * @type {Api.Action|null}
     */
    deletion;

    /**
     * Дата создания
     * @type {Date}
     */
    creationDate;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.id = data['id'];
      this.storeId = data['store_id'];
      this.name = new Api.Phrase(data['name']);
      this.productsTotal = data['products_total'];
      this.childrenTotal = data['children_total'];
      this.deletion = data['deletion'] === null ? null : new Api.Action(data['deletion']);
      this.creationDate = new Date(data['creation_date'] * 1000);
    }
  };

  /**
   * Исходный товар<br><br>
   * <a href="https://aspans.com/api/v1_doc#sourceproduct">Documentation</a>
   */
  static SourceProduct = class {
    /**
     * UUID
     * @type {String}
     */
    uuid;

    /**
     * Наименование
     * @type {Api.Phrase}
     */
    name;

    /**
     * ID магазина
     * @type {Number}
     */
    storeId;

    /**
     * Изображения
     * @type {Api.ProductImages|null}
     */
    images;

    /**
     * Доступность хотябы в одном филиале или в одном регионе
     * @type {Boolean}
     */
    available;

    /**
     * Внешний URI адрес
     * @type {String|null}
     */
    externalUri;

    /**
     * Дата последнего обновления данных
     * @type {Date|null}
     */
    lastUpdateDate;

    /**
     * Дата создания
     * @type {Date}
     */
    creationDate;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.uuid = data['uuid'];
      this.name = new Api.Phrase(data['name']);
      this.storeId = data['store_id'];
      this.images = data['images'] === null ? null : new Api.ProductImages(data['images']);
      this.available = data['available'];
      this.externalUri = data['external_uri'];
      this.lastUpdateDate = data['last_update_date'] === null ? null : new Date(data['last_update_date'] * 1000);
      this.creationDate = new Date(data['creation_date'] * 1000);
    }
  };

  /**
   * Полная информация об исходном товаре<br><br>
   * <a href="https://aspans.com/api/v1_doc#sourceproductfull">Documentation</a>
   */
  static SourceProductFull = class {
    /**
     * Основная информация [устарело]
     * @type {Api.SourceProduct}
     */
    mainData;

    /**
     * Основная информация
     * @type {Api.SourceProduct}
     */
    sourceProduct;

    /**
     * История цен
     * @type {Api.ProductPrice[]}
     */
    prices = [];

    /**
     * @param {object} data
     */
    constructor(data) {
      this.mainData = new Api.SourceProduct(data['main_data']);
      this.sourceProduct = new Api.SourceProduct(data['source_product']);
      data['prices'].forEach((prices) => {
        this.prices.push(new Api.ProductPrice(prices));
      });
    }
  };

  /**
   * Выборка исходны товаров<br><br>
   * <a href="https://aspans.com/api/v1_doc#sourceproductslist">Documentation</a>
   */
  static SourceProductsList = class {
    /**
     * Исходные товары
     * @type {Api.SourceProduct[]}
     */
    sourceProducts = [];

    /**
     * Интернет-магазины
     * @type {Api.Store[]}
     */
    stores = [];

    /**
     * Данные о выборке
     * @type {Api.SelectionData}
     */
    selectionData;

    /**
     * @param {object} data
     */
    constructor(data) {
      data['source_products'].forEach((sourceProducts) => {
        this.sourceProducts.push(new Api.SourceProduct(sourceProducts));
      });
      data['stores'].forEach((stores) => {
        this.stores.push(new Api.Store(stores));
      });
      this.selectionData = new Api.SelectionData(data['selection_data']);
    }
  };

  /**
   * Интернет-магазин<br><br>
   * <a href="https://aspans.com/api/v1_doc#store">Documentation</a>
   */
  static Store = class {
    /**
     * ID
     * @type {Number}
     */
    id;

    /**
     * Код
     * @type {String}
     */
    code;

    /**
     * Наименование
     * @type {String}
     */
    name;

    /**
     * Описание
     * @type {Api.Phrase|null}
     */
    description;

    /**
     * Код основной валюты
     * @type {String}
     */
    currencyCode;

    /**
     * URL адрес
     * @type {String}
     */
    url;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.id = data['id'];
      this.code = data['code'];
      this.name = data['name'];
      this.description = data['description'] === null ? null : new Api.Phrase(data['description']);
      this.currencyCode = data['currency_code'];
      this.url = data['url'];
    }
  };

  /**
   * Выборка интернет-магазинов<br><br>
   * <a href="https://aspans.com/api/v1_doc#storeslist">Documentation</a>
   */
  static StoresList = class {
    /**
     * Интернет-магазины
     * @type {Api.Store[]}
     */
    stores = [];

    /**
     * Данные о выборке
     * @type {Api.SelectionData}
     */
    selectionData;

    /**
     * @param {object} data
     */
    constructor(data) {
      data['stores'].forEach((stores) => {
        this.stores.push(new Api.Store(stores));
      });
      this.selectionData = new Api.SelectionData(data['selection_data']);
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
   * ### Получить исходный регион
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2008: Регион не найден
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#areas_getSourceArea)
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.SourceArea>}
   */
  async areas_getSourceArea({
    
  }) {
    return await this.request(
        'v1/areas/getSourceArea',
        {
        },
        (response => new Api.SourceArea(response))
    );
  }

  /**
   * ### Получить выборку исходных регионов
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#areas_getSourceAreas)
   * @param {Object} param0
   * @param {Boolean|null} [param0.deleted] Показывать удаленные
   * @param {Number[]|null} [param0.store] ID интернет-магазина
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.SourceAreasList>}
   */
  async areas_getSourceAreas({
    deleted = null,
    store = null
  }) {
    return await this.request(
        'v1/areas/getSourceAreas',
        {
              deleted: deleted,
              store: store,
        },
        (response => new Api.SourceAreasList(response))
    );
  }

  /**
   * ### Получение филиала
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#branches_getBranch)
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.Branch>}
   */
  async branches_getBranch({
    
  }) {
    return await this.request(
        'v1/branches/getBranch',
        {
        },
        (response => new Api.Branch(response))
    );
  }

  /**
   * ### Получить выборку исходных категорий
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   *
   * Sort by variants: name, id
   *
   * Sort by default: id
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#categories_getSourceCategories)
   * @param {Object} param0
   * @param {Number|null} [param0.page] Номер страницы
   * @param {Number|null} [param0.limit] Количество элементов на одну страницу
   * @param {String|null} [param0.sortBy] Поле сортировки
   * @param {String|null} [param0.sortDirection] Направление сортировки
   * @param {Boolean|null} [param0.deleted] Показывать удаленные
   * @param {Number[]|null} [param0.store] ID интернет-магазина
   * @param {Boolean|null} [param0.hasChildren] Флаг "Есть вложенные категории"
   * @param {Boolean|null} [param0.hasParent] Флаг "Есть родительская категория"
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.SourceCategoriesList>}
   */
  async categories_getSourceCategories({
    page = null,
    limit = null,
    sortBy = null,
    sortDirection = null,
    deleted = null,
    store = null,
    hasChildren = null,
    hasParent = null
  }) {
    return await this.request(
        'v1/categories/getSourceCategories',
        {
              page: page,
              limit: limit,
              sort_by: sortBy,
              sort_direction: sortDirection,
              deleted: deleted,
              store: store,
              has_children: hasChildren,
              has_parent: hasParent,
        },
        (response => new Api.SourceCategoriesList(response))
    );
  }

  /**
   * ### Получение исходной категории товаров
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2006: Категория не найдена
   * - Error #3005: Не выбрана категория
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#categories_getSourceCategory)
   * @param {Object} param0
   * @param {Number} param0.id ID
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.SourceCategory>}
   */
  async categories_getSourceCategory({
    id
  }) {
    return await this.request(
        'v1/categories/getSourceCategory',
        {
              id: id,
        },
        (response => new Api.SourceCategory(response))
    );
  }

  /**
   * ### Получить ленту изменений цен
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2001: У Вас недостаточно прав
   * - Error #2010: Лента не найдена
   * - Error #3008: Не выбрана лента
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#discountFeeds_getFeed)
   * @param {Object} param0
   * @param {Number} param0.id ID ленты
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.DiscountFeed>}
   */
  async discountFeeds_getFeed({
    id
  }) {
    return await this.request(
        'v1/discountFeeds/getFeed',
        {
              id: id,
        },
        (response => new Api.DiscountFeed(response))
    );
  }

  /**
   * ### Получить выборку лент
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2011: Ленты не найдены
   *
   * Sort by variants: id
   *
   * Sort by default: id
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#discountFeeds_getFeedsList)
   * @param {Object} param0
   * @param {Number|null} [param0.page] Номер страницы
   * @param {Number|null} [param0.limit] Количество элементов на одну страницу
   * @param {String|null} [param0.sortBy] Поле сортировки
   * @param {String|null} [param0.sortDirection] Направление сортировки
   * @param {Boolean|null} [param0.deleted] Показывать удаленные
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.DiscountFeedsList>}
   */
  async discountFeeds_getFeedsList({
    page = null,
    limit = null,
    sortBy = null,
    sortDirection = null,
    deleted = null
  }) {
    return await this.request(
        'v1/discountFeeds/getFeedsList',
        {
              page: page,
              limit: limit,
              sort_by: sortBy,
              sort_direction: sortDirection,
              deleted: deleted,
        },
        (response => new Api.DiscountFeedsList(response))
    );
  }

  /**
   * ### Получить уведомление
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#discountFeeds_getNotification)
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.DiscountFeedsNotification>}
   */
  async discountFeeds_getNotification({
    
  }) {
    return await this.request(
        'v1/discountFeeds/getNotification',
        {
        },
        (response => new Api.DiscountFeedsNotification(response))
    );
  }

  /**
   * ### Получение выборки уведомлений
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   *
   * Sort by variants: id
   *
   * Sort by default: id
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#discountFeeds_getNotificationsList)
   * @param {Object} param0
   * @param {Number|null} [param0.page] Номер страницы
   * @param {Number|null} [param0.limit] Количество элементов на одну страницу
   * @param {String|null} [param0.sortBy] Поле сортировки
   * @param {String|null} [param0.sortDirection] Направление сортировки
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.DiscountFeedsNotificationsList>}
   */
  async discountFeeds_getNotificationsList({
    page = null,
    limit = null,
    sortBy = null,
    sortDirection = null
  }) {
    return await this.request(
        'v1/discountFeeds/getNotificationsList',
        {
              page: page,
              limit: limit,
              sort_by: sortBy,
              sort_direction: sortDirection,
        },
        (response => new Api.DiscountFeedsNotificationsList(response))
    );
  }

  /**
   * ### Получение скидок
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2010: Лента не найдена
   * - Error #2012: Скидки не найдены
   *
   * Sort by variants: id
   *
   * Sort by default: id
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#discounts_getDiscounts)
   * @param {Object} param0
   * @param {Number|null} [param0.page] Номер страницы
   * @param {Number|null} [param0.limit] Количество элементов на одну страницу
   * @param {String|null} [param0.sortBy] Поле сортировки
   * @param {String|null} [param0.sortDirection] Направление сортировки
   * @param {Number|null} [param0.feed] ID ленты
   * @param {Number|null} [param0.minPercent90] Минимальный процент за 90 дней
   * @param {Number|null} [param0.minPercent180] Минимальный процент за 180 дней
   * @param {Number|null} [param0.minPercent270] Минимальный процент за 270 дней
   * @param {Number|null} [param0.minPercentTotal] Минимальный процент за все время
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.DiscountsList>}
   */
  async discounts_getDiscounts({
    page = null,
    limit = null,
    sortBy = null,
    sortDirection = null,
    feed = null,
    minPercent90 = null,
    minPercent180 = null,
    minPercent270 = null,
    minPercentTotal = null
  }) {
    return await this.request(
        'v1/discounts/getDiscounts',
        {
              page: page,
              limit: limit,
              sort_by: sortBy,
              sort_direction: sortDirection,
              feed: feed,
              min_percent_90: minPercent90,
              min_percent_180: minPercent180,
              min_percent_270: minPercent270,
              min_percent_total: minPercentTotal,
        },
        (response => new Api.DiscountsList(response))
    );
  }

  /**
   * ### Получить список валют
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#finance_getCurrencies)
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.Currency[]>}
   */
  async finance_getCurrencies({
    
  }) {
    return await this.request(
        'v1/finance/getCurrencies',
        {
        },
        (response => response.map(item => new Api.Currency(item)))
    );
  }

  /**
   * ### Получение валюты
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2005: Валюта не найдена
   * - Error #3003: Не выбрана валюта
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#finance_getCurrency)
   * @param {Object} param0
   * @param {Number} param0.id ID
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.Currency>}
   */
  async finance_getCurrency({
    id
  }) {
    return await this.request(
        'v1/finance/getCurrency',
        {
              id: id,
        },
        (response => new Api.Currency(response))
    );
  }

  /**
   * ### Получение валюты
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2005: Валюта не найдена
   * - Error #3003: Не выбрана валюта
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#finance_getCurrencyByCode)
   * @param {Object} param0
   * @param {String} param0.code Код
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.Currency>}
   */
  async finance_getCurrencyByCode({
    code
  }) {
    return await this.request(
        'v1/finance/getCurrencyByCode',
        {
              code: code,
        },
        (response => new Api.Currency(response))
    );
  }

  /**
   * ### Информация о посетителе
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#getMe)
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.SessionInfo>}
   */
  async getMe({
    
  }) {
    return await this.request(
        'v1/getMe',
        {
        },
        (response => new Api.SessionInfo(response))
    );
  }

  /**
   * ### Инициализация клиента
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#init)
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.InitializationData>}
   */
  async init({
    
  }) {
    return await this.request(
        'v1/init',
        {
        },
        (response => new Api.InitializationData(response))
    );
  }

  /**
   * ### Получить исходный товар
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2009: Товар не найден
   * - Error #3007: Не выбран товар
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#products_getSourceProduct)
   * @param {Object} param0
   * @param {String} param0.uuid UUID исходного товара
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.SourceProduct>}
   */
  async products_getSourceProduct({
    uuid
  }) {
    return await this.request(
        'v1/products/getSourceProduct',
        {
              uuid: uuid,
        },
        (response => new Api.SourceProduct(response))
    );
  }

  /**
   * ### Получение полной информации об исходном товаре
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2009: Товар не найден
   * - Error #3007: Не выбран товар
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#products_getSourceProductFull)
   * @param {Object} param0
   * @param {String} param0.uuid UUID исходного товара
   * @param {Boolean|null} [param0.testMode] Тестовый режим
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.SourceProductFull>}
   */
  async products_getSourceProductFull({
    uuid,
    testMode = null
  }) {
    return await this.request(
        'v1/products/getSourceProductFull',
        {
              uuid: uuid,
              test_mode: testMode,
        },
        (response => new Api.SourceProductFull(response))
    );
  }

  /**
   * ### Получить список товаров
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   *
   * Sort by variants: id, creation_date
   *
   * Sort by default: creation_date
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#products_getSourceProducts)
   * @param {Object} param0
   * @param {Number|null} [param0.page] Номер страницы
   * @param {Number|null} [param0.limit] Количество элементов на одну страницу
   * @param {String|null} [param0.sortBy] Поле сортировки
   * @param {String|null} [param0.sortDirection] Направление сортировки
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.SourceProductsList>}
   */
  async products_getSourceProducts({
    page = null,
    limit = null,
    sortBy = null,
    sortDirection = null
  }) {
    return await this.request(
        'v1/products/getSourceProducts',
        {
              page: page,
              limit: limit,
              sort_by: sortBy,
              sort_direction: sortDirection,
        },
        (response => new Api.SourceProductsList(response))
    );
  }

  /**
   * ### Получить интернет-магазин
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2007: Интернет-магазин не найден
   * - Error #3004: Не выбран интернет-магазин
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#stores_getStore)
   * @param {Object} param0
   * @param {Number} param0.id ID
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.Store>}
   */
  async stores_getStore({
    id
  }) {
    return await this.request(
        'v1/stores/getStore',
        {
              id: id,
        },
        (response => new Api.Store(response))
    );
  }

  /**
   * ### Получить интернет-магазин
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2007: Интернет-магазин не найден
   * - Error #3004: Не выбран интернет-магазин
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#stores_getStoreByCode)
   * @param {Object} param0
   * @param {String} param0.code Код
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.Store>}
   */
  async stores_getStoreByCode({
    code
  }) {
    return await this.request(
        'v1/stores/getStoreByCode',
        {
              code: code,
        },
        (response => new Api.Store(response))
    );
  }

  /**
   * ### Получить выборку интернет-магазинов
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   *
   * Sort by variants: id, code, creation_date, name
   *
   * Sort by default: name
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#stores_getStores)
   * @param {Object} param0
   * @param {Number|null} [param0.page] Номер страницы
   * @param {Number|null} [param0.limit] Количество элементов на одну страницу
   * @param {String|null} [param0.sortBy] Поле сортировки
   * @param {String|null} [param0.sortDirection] Направление сортировки
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.StoresList>}
   */
  async stores_getStores({
    page = null,
    limit = null,
    sortBy = null,
    sortDirection = null
  }) {
    return await this.request(
        'v1/stores/getStores',
        {
              page: page,
              limit: limit,
              sort_by: sortBy,
              sort_direction: sortDirection,
        },
        (response => new Api.StoresList(response))
    );
  }

  /**
   * ### Обновление JWT-токенов
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Виновные уже наказаны, а мы уже работаем над ее исправлением. Пожалуйста, повторите попытку через несколько секунд
   * - Error #3011: Отсутствует refresh-токен
   * - Error #3012: Некорректный refresh-токен
   *
   * [Documentation](https://skidki.local:12443/api/v1_doc#tokens_refresh)
   * @param {Object} param0
   * @param {String} param0.refreshToken Refresh token
   * @throws {Api.ErrorsCollection}
   * @return {Promise<Api.JwtTokensPair>}
   */
  async tokens_refresh({
    refreshToken
  }) {
    return await this.request(
        'v1/tokens/refresh',
        {
              refresh_token: refreshToken,
        },
        (response => new Api.JwtTokensPair(response))
    );
  }

}

