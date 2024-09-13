library api;

import 'dart:convert';
import 'dart:async';
import 'dart:io';
import 'dart:developer';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';


/// Сессия API
class ApiSession {
  static const String _fieldAccessTokenValue = 'access_token_value';
  static const String _fieldAccessTokenExpirationDate = 'access_token_expiration_date';
  static const String _fieldRefreshTokenValue = 'refresh_token_value';
  static const String _fieldRefreshTokenExpirationDate = 'refresh_token_expiration_date';

  SharedPreferences? prefs;
  JwtToken? accessToken;
  JwtToken? refreshToken;
  Session? session;
  User? user;
  String? timezone;

  late Api api;

  ApiSession() {
    api = Api(this);
  }

  Future<void> initData() async {
    InitializationData initializationData = await api.init();
    session = initializationData.sessionInfo.session;
    user = initializationData.sessionInfo.user;
    setTokens(initializationData.sessionInfo.tokens);
    timezone = initializationData.sessionInfo.timezone;
  }

  Future<void> initTokens() async {
    prefs ??= await SharedPreferences.getInstance();
    String? accessTokenValue = prefs!.getString(_fieldAccessTokenValue);
    int? accessTokenExpirationDate = prefs!.getInt(_fieldAccessTokenExpirationDate);
    if (accessTokenValue != null && accessTokenExpirationDate != null) {
      accessToken = JwtToken(accessTokenValue, accessTokenExpirationDate);
    }

    String? refreshTokenValue = prefs!.getString(_fieldRefreshTokenValue);
    int? refreshTokenExpirationDate = prefs!.getInt(_fieldRefreshTokenExpirationDate);
    if (refreshTokenValue != null && refreshTokenExpirationDate != null) {
      refreshToken = JwtToken(refreshTokenValue, refreshTokenExpirationDate);
    }
  }

  void setTokens(JwtTokensPair? tokensPair) {
    if (tokensPair == null) {
      prefs!.remove(_fieldAccessTokenValue);
      prefs!.remove(_fieldAccessTokenExpirationDate);
      prefs!.remove(_fieldRefreshTokenValue);
      prefs!.remove(_fieldRefreshTokenExpirationDate);
    } else {
      prefs!.setString(_fieldAccessTokenValue, tokensPair.accessToken.value);
      prefs!.setInt(_fieldAccessTokenExpirationDate, tokensPair.accessToken.expirationDate);
      prefs!.setString(_fieldRefreshTokenValue, tokensPair.refreshToken.value);
      prefs!.setInt(_fieldRefreshTokenExpirationDate, tokensPair.refreshToken.expirationDate);
    }

    accessToken = tokensPair?.accessToken;
    refreshToken = tokensPair?.refreshToken;
  }
}

class Phrase {
  Map<String, String?> values;

  Phrase.fromMap(Map<String, String?> data)
      : values = data;

  static Phrase? tryFromMap(Map<String, String?> data) {
    Phrase result = Phrase.fromMap(data);
    return result.isEmpty() ? null : result;
  }

  bool isEmpty() {
    return false;
  }

  String? get(String languageCode) {
    return values[languageCode];
  }

  String require(String languageCode) {
    String? result = values[languageCode];
    if (result == null) {
      for (String? string in values.values) {
        if (string != null) {
          return string;
        }
      }
    }

    return result!;
  }
}

class ApiErrorsList {
  Set<int> codes = {};
  List<Error> errors = [];

  ApiErrorsList(List<Map<String, dynamic>> errorsData) {
    for (Map<String, dynamic> errorData in errorsData) {
      Error error = Error.fromMap(errorData);
      errors.add(error);
      codes.add(error.code);
    }
  }
}


class Action {
  /// ID типа
  String typeId;

  /// ID сессии
  int sessionId;

  /// ID авторизации
  int? authorizationId;

  /// ID пользователя
  int? userId;

  /// User-Agent
  String? userAgent;

  /// IP адрес
  String? ipAddressValue;

  Action.fromMap(Map<String, dynamic> data)
    :
    typeId = data['type_id'],
    sessionId = data['session_id'],
    authorizationId = data['authorization_id'],
    userId = data['user_id'],
    userAgent = data['user_agent'],
    ipAddressValue = data['ip_address_value'];
}

class Branch {
  /// ID
  int id;

  /// Наименование
  Phrase name;

  /// ID интернет-магазина
  int storeId;

  /// ID исходного региона
  int? sourceAreaId;

  /// ID региона
  int? areaId;

  /// Локация
  Location location;

  Branch.fromMap(Map<String, dynamic> data)
    :
    id = data['id'],
    name = Phrase.fromMap(Map<String, String?>.from(data['name'])),
    storeId = data['store_id'],
    sourceAreaId = data['source_area_id'],
    areaId = data['area_id'],
    location = Location.fromMap(data['location']);
}

class Currency {
  /// ID
  int id;

  /// Код
  String code;

  /// Символ
  String symbol;

  /// Формат
  String format;

  /// Точность
  int precision;

  /// Цена 100 долларов
  double priceHundredDollars;

  /// Дата последнего обновления
  int? lastUpdateDate;

  /// Дата создания
  int creationDate;

  Currency.fromMap(Map<String, dynamic> data)
    :
    id = data['id'],
    code = data['code'],
    symbol = data['symbol'],
    format = data['format'],
    precision = data['precision'],
    priceHundredDollars = data['price_hundred_dollars'],
    lastUpdateDate = data['last_update_date'],
    creationDate = data['creation_date'];
}

class Discount {
  /// UUID товара
  String sourceProductUuid;

  /// ID магазина
  int storeId;

  /// Цена товара
  double priceValue;

  /// ID валюты
  int currencyId;

  /// Средняя цена за 90 дней
  double priceAverage90;

  /// Процент разницы за 90 дней
  double percents90;

  /// Средняя цена за 180 дней
  double priceAverage180;

  /// Процент разницы за 180 дней
  double percents180;

  /// Средняя цена за 270 дней
  double priceAverage270;

  /// Процент разницы за 270 дней
  double percents270;

  /// Средняя цена за все время
  double priceAverageTotal;

  /// Процент разницы за все время
  double percentsTotal;

  /// Дата создания
  int creationDate;

  Discount.fromMap(Map<String, dynamic> data)
    :
    sourceProductUuid = data['source_product_uuid'],
    storeId = data['store_id'],
    priceValue = data['price_value'],
    currencyId = data['currency_id'],
    priceAverage90 = data['price_average_90'],
    percents90 = data['percents_90'],
    priceAverage180 = data['price_average_180'],
    percents180 = data['percents_180'],
    priceAverage270 = data['price_average_270'],
    percents270 = data['percents_270'],
    priceAverageTotal = data['price_average_total'],
    percentsTotal = data['percents_total'],
    creationDate = data['creation_date'];
}

class DiscountFeed {
  /// ID
  int id;

  /// Код
  String? code;

  /// Наименование
  Phrase name;

  /// Описание
  Phrase? description;

  /// ID пользователя
  int? userId;

  /// ID валюты
  int? currencyId;

  /// Минимальный процент скидки
  int? discountPercentMin;

  /// Минимальный размер скидки в USD
  double? discountValueMinUsd;

  /// Информация об удалении
  Action? deletion;

  /// Дата создания
  int creationDate;

  DiscountFeed.fromMap(Map<String, dynamic> data)
    :
    id = data['id'],
    code = data['code'],
    name = Phrase.fromMap(Map<String, String?>.from(data['name'])),
    description = data['description'] == null
		? null
		: Phrase.fromMap(Map<String, String?>.from(data['description'])),
    userId = data['user_id'],
    currencyId = data['currency_id'],
    discountPercentMin = data['discount_percent_min'],
    discountValueMinUsd = data['discount_value_min_usd'],
    deletion = data['deletion'] == null
		? null
		: Action.fromMap(data['deletion']),
    creationDate = data['creation_date'];
}

class DiscountFeedsList {
  /// Ленты
  List<DiscountFeed> feeds;

  /// Валюты
  Map<int, Currency> currencies;

  /// Данные о выборке
  SelectionData selectionData;

  DiscountFeedsList.fromMap(Map<String, dynamic> data)
    :
    feeds = data['feeds'].map<DiscountFeed>((itemData) => DiscountFeed.fromMap(itemData)).toList(),
    currencies = (data['currencies'] as Map<String, dynamic>).map<int, Currency>((key, value) => MapEntry<int, Currency>(int.parse(key), Currency.fromMap(value))),
    selectionData = SelectionData.fromMap(data['selection_data']);
}

class DiscountFeedsNotification {
  /// ID
  int id;

  /// UUID исходного товара
  String? sourceProductUuid;

  /// Новая цена (NULL - нет в наличии)
  double? priceValue;

  /// Скидка
  Discount? discount;

  /// ID источника уведомления
  int sourceId;

  /// ID ленты уведомлений
  int? feedId;

  /// Флаг "Активно"
  bool active;

  /// Дата создания
  int creationDate;

  DiscountFeedsNotification.fromMap(Map<String, dynamic> data)
    :
    id = data['id'],
    sourceProductUuid = data['source_product_uuid'],
    priceValue = data['price_value'],
    discount = data['discount'] == null
		? null
		: Discount.fromMap(data['discount']),
    sourceId = data['source_id'],
    feedId = data['feed_id'],
    active = data['active'],
    creationDate = data['creation_date'];
}

class DiscountFeedsNotificationFull {
  /// Уведомление о скидке
  DiscountFeedsNotification notification;

  DiscountFeedsNotificationFull.fromMap(Map<String, dynamic> data)
    :
    notification = DiscountFeedsNotification.fromMap(data['notification']);
}

class DiscountFeedsNotificationsList {
  /// Уведомления
  List<DiscountFeedsNotification> notifications;

  DiscountFeedsNotificationsList.fromMap(Map<String, dynamic> data)
    :
    notifications = data['notifications'].map<DiscountFeedsNotification>((itemData) => DiscountFeedsNotification.fromMap(itemData)).toList();
}

class DiscountFeedsSource {
  /// ID
  int id;

  /// ID пользователя
  int? userId;

  /// ID ленты
  int? feed;

  /// Разрешить/запретить товары из фильтра
  bool allow;

  /// ID магазина
  int? storeId;

  /// ID товара из каталога
  int? catalogProductId;

  /// ID исходного товара
  int? sourceProductId;

  /// ID категории из каталога
  int? catalogCategoryId;

  /// ID исходной категории
  int? sourceCategoryId;

  /// Данные об удалении
  Action? deletion;

  /// Данные о создании
  Action? creation;

  /// Дата создания
  int creationDate;

  DiscountFeedsSource.fromMap(Map<String, dynamic> data)
    :
    id = data['id'],
    userId = data['user_id'],
    feed = data['feed'],
    allow = data['allow'],
    storeId = data['store_id'],
    catalogProductId = data['catalog_product_id'],
    sourceProductId = data['source_product_id'],
    catalogCategoryId = data['catalog_category_id'],
    sourceCategoryId = data['source_category_id'],
    deletion = data['deletion'] == null
		? null
		: Action.fromMap(data['deletion']),
    creation = data['creation'] == null
		? null
		: Action.fromMap(data['creation']),
    creationDate = data['creation_date'];
}

class DiscountFeedsSubscription {
  /// ID
  int id;

  DiscountFeedsSubscription.fromMap(Map<String, dynamic> data)
    :
    id = data['id'];
}

class DiscountsList {
  /// Скидки
  List<Discount> discounts;

  /// Товары
  Map<String, SourceProduct> sourceProducts;

  /// Магазины
  Map<int, Store> stores;

  /// Валюты
  Map<int, Currency> currencies;

  /// Данные о выборке
  SelectionData selectionData;

  DiscountsList.fromMap(Map<String, dynamic> data)
    :
    discounts = data['discounts'].map<Discount>((itemData) => Discount.fromMap(itemData)).toList(),
    sourceProducts = (data['source_products'] as Map<String, dynamic>).map<String, SourceProduct>((key, value) => MapEntry<String, SourceProduct>(key, SourceProduct.fromMap(value))),
    stores = (data['stores'] as Map<String, dynamic>).map<int, Store>((key, value) => MapEntry<int, Store>(int.parse(key), Store.fromMap(value))),
    currencies = (data['currencies'] as Map<String, dynamic>).map<int, Currency>((key, value) => MapEntry<int, Currency>(int.parse(key), Currency.fromMap(value))),
    selectionData = SelectionData.fromMap(data['selection_data']);
}

class Error {
  /// Field code
  int code;

  /// Field input_field
  String? inputField;

  /// Field description
  Phrase description;

  Error.fromMap(Map<String, dynamic> data)
    :
    code = data['code'],
    inputField = data['input_field'],
    description = Phrase.fromMap(Map<String, String?>.from(data['description']));
}

class InitializationData {
  /// Данные о посетителе
  SessionInfo sessionInfo;

  InitializationData.fromMap(Map<String, dynamic> data)
    :
    sessionInfo = SessionInfo.fromMap(data['session_info']);
}

class JwtToken {
  /// Значение токена
  String value;

  /// Дата истечения
  int expirationDate;

  JwtToken(this.value, this.expirationDate);

  bool isExpired() {
    return DateTime.now().millisecondsSinceEpoch > (expirationDate * 1000);
  }

  JwtToken.fromMap(Map<String, dynamic> data)
    :
    value = data['value'],
    expirationDate = data['expiration_date'];
}

class JwtTokensPair {
  /// Токен для доступа
  JwtToken accessToken;

  /// Токен для обновления
  JwtToken refreshToken;

  JwtTokensPair.fromMap(Map<String, dynamic> data)
    :
    accessToken = JwtToken.fromMap(data['access_token']),
    refreshToken = JwtToken.fromMap(data['refresh_token']);
}

class Location {
  /// Долгота
  double longitude;

  /// Широта
  double latitude;

  Location.fromMap(Map<String, dynamic> data)
    :
    longitude = data['longitude'],
    latitude = data['latitude'];
}

class ProductImages {
  /// Размер 256x256
  String size256;

  ProductImages.fromMap(Map<String, dynamic> data)
    :
    size256 = data['size_256'];
}

class ProductPrice {
  /// ID
  int sourceProductId;

  /// Значение цены числом
  double? value;

  /// Значение цены строкой
  String? valueFormatted;

  /// ID валюты
  int? currencyId;

  /// ID исходного региона
  int? sourceAreaId;

  /// ID филиала
  int? branchId;

  /// Дата создания
  int creationDate;

  ProductPrice.fromMap(Map<String, dynamic> data)
    :
    sourceProductId = data['source_product_id'],
    value = data['value'],
    valueFormatted = data['value_formatted'],
    currencyId = data['currency_id'],
    sourceAreaId = data['source_area_id'],
    branchId = data['branch_id'],
    creationDate = data['creation_date'];
}

class SelectionData {
  /// Лимит
  int limit;

  /// Общее количество элементов
  int totalAmount;

  /// Страница
  int page;

  /// Общее количество страниц
  int pagesTotal;

  /// Поле, по которому производится сортировка
  String sortBy;

  /// Направление сортировки
  String sortDirection;

  /// Варианты полей сортировки
  List<String> sortVariants;

  SelectionData.fromMap(Map<String, dynamic> data)
    :
    limit = data['limit'],
    totalAmount = data['total_amount'],
    page = data['page'],
    pagesTotal = data['pages_total'],
    sortBy = data['sort_by'],
    sortDirection = data['sort_direction'],
    sortVariants = List<String>.from(data['sort_variants'] as List);
}

class Session {
  /// Код языка
  String languageCode;

  Session.fromMap(Map<String, dynamic> data)
    :
    languageCode = data['language_code'];
}

class SessionInfo {
  /// Пользователь
  User? user;

  /// Сессия
  Session session;

  /// JWT-токены
  JwtTokensPair? tokens;

  /// Временная зона
  String timezone;

  SessionInfo.fromMap(Map<String, dynamic> data)
    :
    user = data['user'] == null
		? null
		: User.fromMap(data['user']),
    session = Session.fromMap(data['session']),
    tokens = data['tokens'] == null
		? null
		: JwtTokensPair.fromMap(data['tokens']),
    timezone = data['timezone'];
}

class SourceArea {
  /// ID
  int id;

  /// ID интернет-магазина
  int storeId;

  /// ID родительского исходного региона
  int? parentId;

  /// Географический регион
  int? areaId;

  /// Наименование
  Phrase name;

  /// Данные об удалении
  Action? deletion;

  /// Дата создания
  int creationDate;

  SourceArea.fromMap(Map<String, dynamic> data)
    :
    id = data['id'],
    storeId = data['store_id'],
    parentId = data['parent_id'],
    areaId = data['area_id'],
    name = Phrase.fromMap(Map<String, String?>.from(data['name'])),
    deletion = data['deletion'] == null
		? null
		: Action.fromMap(data['deletion']),
    creationDate = data['creation_date'];
}

class SourceAreasList {
  /// Исходные регионы
  List<SourceArea> sourceAreas;

  /// Данные о выборке
  SelectionData selectionData;

  SourceAreasList.fromMap(Map<String, dynamic> data)
    :
    sourceAreas = data['source_areas'].map<SourceArea>((itemData) => SourceArea.fromMap(itemData)).toList(),
    selectionData = SelectionData.fromMap(data['selection_data']);
}

class SourceCategoriesList {
  /// Исходные категории
  List<SourceCategory> sourceCategories;

  /// Данные о выборке
  SelectionData selectionData;

  SourceCategoriesList.fromMap(Map<String, dynamic> data)
    :
    sourceCategories = data['source_categories'].map<SourceCategory>((itemData) => SourceCategory.fromMap(itemData)).toList(),
    selectionData = SelectionData.fromMap(data['selection_data']);
}

class SourceCategory {
  /// ID
  int id;

  /// ID интернет-магазина
  int storeId;

  /// Наименование
  Phrase name;

  /// Количество товаров
  int? productsTotal;

  /// Количество подкатегорий
  int? childrenTotal;

  /// Информация об удалении
  Action? deletion;

  /// Дата создания
  int creationDate;

  SourceCategory.fromMap(Map<String, dynamic> data)
    :
    id = data['id'],
    storeId = data['store_id'],
    name = Phrase.fromMap(Map<String, String?>.from(data['name'])),
    productsTotal = data['products_total'],
    childrenTotal = data['children_total'],
    deletion = data['deletion'] == null
		? null
		: Action.fromMap(data['deletion']),
    creationDate = data['creation_date'];
}

class SourceProduct {
  /// UUID
  String uuid;

  /// Наименование
  Phrase name;

  /// ID магазина
  int storeId;

  /// Изображения
  ProductImages? images;

  /// Доступность хотябы в одном филиале или в одном регионе
  bool available;

  /// Внешний URI адрес
  String? externalUri;

  /// Дата последнего обновления данных
  int? lastUpdateDate;

  /// Дата создания
  int creationDate;

  SourceProduct.fromMap(Map<String, dynamic> data)
    :
    uuid = data['uuid'],
    name = Phrase.fromMap(Map<String, String?>.from(data['name'])),
    storeId = data['store_id'],
    images = data['images'] == null
		? null
		: ProductImages.fromMap(data['images']),
    available = data['available'],
    externalUri = data['external_uri'],
    lastUpdateDate = data['last_update_date'],
    creationDate = data['creation_date'];
}

class SourceProductFull {
  /// Основная информация [устарело]
  SourceProduct mainData;

  /// Основная информация
  SourceProduct sourceProduct;

  /// История цен
  List<ProductPrice> prices;

  SourceProductFull.fromMap(Map<String, dynamic> data)
    :
    mainData = SourceProduct.fromMap(data['main_data']),
    sourceProduct = SourceProduct.fromMap(data['source_product']),
    prices = data['prices'].map<ProductPrice>((itemData) => ProductPrice.fromMap(itemData)).toList();
}

class SourceProductsList {
  /// Исходные товары
  List<SourceProduct> sourceProducts;

  /// Интернет-магазины
  Map<int, Store> stores;

  /// Данные о выборке
  SelectionData selectionData;

  SourceProductsList.fromMap(Map<String, dynamic> data)
    :
    sourceProducts = data['source_products'].map<SourceProduct>((itemData) => SourceProduct.fromMap(itemData)).toList(),
    stores = (data['stores'] as Map<String, dynamic>).map<int, Store>((key, value) => MapEntry<int, Store>(int.parse(key), Store.fromMap(value))),
    selectionData = SelectionData.fromMap(data['selection_data']);
}

class Store {
  /// ID
  int id;

  /// Код
  String code;

  /// Наименование
  String name;

  /// Описание
  Phrase? description;

  /// Код основной валюты
  String currencyCode;

  /// URL адрес
  String url;

  Store.fromMap(Map<String, dynamic> data)
    :
    id = data['id'],
    code = data['code'],
    name = data['name'],
    description = data['description'] == null
		? null
		: Phrase.fromMap(Map<String, String?>.from(data['description'])),
    currencyCode = data['currency_code'],
    url = data['url'];
}

class StoresList {
  /// Интернет-магазины
  List<Store> stores;

  /// Данные о выборке
  SelectionData selectionData;

  StoresList.fromMap(Map<String, dynamic> data)
    :
    stores = data['stores'].map<Store>((itemData) => Store.fromMap(itemData)).toList(),
    selectionData = SelectionData.fromMap(data['selection_data']);
}

class User {
  /// ID
  int id;

  /// Номер телефона
  String? phoneNumber;

  /// Юзернейм
  String? username;

  /// Имя
  String firstName;

  /// Фамилия
  String? lastName;

  /// Отчество
  String? middleName;

  /// Дата последнего посещения
  int? lastRequestDate;

  /// Флаг "Онлайн"
  bool isOnline;

  User.fromMap(Map<String, dynamic> data)
    :
    id = data['id'],
    phoneNumber = data['phone_number'],
    username = data['username'],
    firstName = data['first_name'],
    lastName = data['last_name'],
    middleName = data['middle_name'],
    lastRequestDate = data['last_request_date'],
    isOnline = data['is_online'];
}


class Api {
  static const String host = 'new.skidki.me';

  final ApiSession _session;

  Api(this._session);

  Future<dynamic> request(String method, Map<String, dynamic> params, Function? then) async {
    Uri url = Uri.https(host, '/api/$method');
    log('Request to: $url');

    Map<String, String> headers = {};
    headers['Content-Type'] = 'application/json';
    if (_session.accessToken != null && !_session.accessToken!.isExpired()) {
      headers['Authorization'] = 'Bearer ${_session.accessToken!.value}';
    }

    var client = http.Client();
    http.Response response = await client.post(url, headers: headers, body: utf8.encode(jsonEncode(params)));

    if (response.statusCode == 200) {
      String responseBody = response.body;
      log(responseBody);

      Map<String, dynamic> responseDecoded = jsonDecode(responseBody);
      if (responseDecoded['ok'] == false) {
        throw ApiErrorsList(responseDecoded['errors']);
      }

      return then == null ? responseDecoded['result'] : then(responseDecoded['result']);
    } else {
      log('http error #${response.statusCode}');
      return null;
    }
  }

  /// Получить исходный регион
  Future<SourceArea> areas_getSourceArea(
  ) async {
    return await request(
        'v1/areas/getSourceArea', {
    },
    (Map<String, dynamic> data) => SourceArea.fromMap(data)
    );
  }

  /// Получить выборку исходных регионов
  Future<SourceAreasList> areas_getSourceAreas(
    {
    bool? deleted,
    List<int>? store,
    }
  ) async {
    return await request(
        'v1/areas/getSourceAreas', {
        'deleted': deleted,
        'store': store,
    },
    (Map<String, dynamic> data) => SourceAreasList.fromMap(data)
    );
  }

  /// Получение филиала
  Future<Branch> branches_getBranch(
  ) async {
    return await request(
        'v1/branches/getBranch', {
    },
    (Map<String, dynamic> data) => Branch.fromMap(data)
    );
  }

  /// Получить выборку исходных категорий
  Future<SourceCategoriesList> categories_getSourceCategories(
    {
    int? page,
    int? limit,
    String? sortBy,
    String? sortDirection,
    bool? deleted,
    List<int>? store,
    bool? hasChildren,
    bool? hasParent,
    }
  ) async {
    return await request(
        'v1/categories/getSourceCategories', {
        'page': page,
        'limit': limit,
        'sort_by': sortBy,
        'sort_direction': sortDirection,
        'deleted': deleted,
        'store': store,
        'has_children': hasChildren,
        'has_parent': hasParent,
    },
    (Map<String, dynamic> data) => SourceCategoriesList.fromMap(data)
    );
  }

  /// Получение исходной категории товаров
  Future<SourceCategory> categories_getSourceCategory(
    int id,
  ) async {
    return await request(
        'v1/categories/getSourceCategory', {
        'id': id,
    },
    (Map<String, dynamic> data) => SourceCategory.fromMap(data)
    );
  }

  /// Получить ленту изменений цен
  Future<DiscountFeed> discountFeeds_getFeed(
    int id,
  ) async {
    return await request(
        'v1/discountFeeds/getFeed', {
        'id': id,
    },
    (Map<String, dynamic> data) => DiscountFeed.fromMap(data)
    );
  }

  /// Получить выборку лент
  Future<DiscountFeedsList> discountFeeds_getFeedsList(
    {
    int? page,
    int? limit,
    String? sortBy,
    String? sortDirection,
    bool? deleted,
    }
  ) async {
    return await request(
        'v1/discountFeeds/getFeedsList', {
        'page': page,
        'limit': limit,
        'sort_by': sortBy,
        'sort_direction': sortDirection,
        'deleted': deleted,
    },
    (Map<String, dynamic> data) => DiscountFeedsList.fromMap(data)
    );
  }

  /// Получить уведомление
  Future<DiscountFeedsNotification> discountFeeds_getNotification(
  ) async {
    return await request(
        'v1/discountFeeds/getNotification', {
    },
    (Map<String, dynamic> data) => DiscountFeedsNotification.fromMap(data)
    );
  }

  /// Получение выборки уведомлений
  Future<DiscountFeedsNotificationsList> discountFeeds_getNotificationsList(
    {
    int? page,
    int? limit,
    String? sortBy,
    String? sortDirection,
    }
  ) async {
    return await request(
        'v1/discountFeeds/getNotificationsList', {
        'page': page,
        'limit': limit,
        'sort_by': sortBy,
        'sort_direction': sortDirection,
    },
    (Map<String, dynamic> data) => DiscountFeedsNotificationsList.fromMap(data)
    );
  }

  /// Получение скидок
  Future<DiscountsList> discounts_getDiscounts(
    {
    int? page,
    int? limit,
    String? sortBy,
    String? sortDirection,
    int? feed,
    double? minPercent90,
    double? minPercent180,
    double? minPercent270,
    double? minPercentTotal,
    }
  ) async {
    return await request(
        'v1/discounts/getDiscounts', {
        'page': page,
        'limit': limit,
        'sort_by': sortBy,
        'sort_direction': sortDirection,
        'feed': feed,
        'min_percent_90': minPercent90,
        'min_percent_180': minPercent180,
        'min_percent_270': minPercent270,
        'min_percent_total': minPercentTotal,
    },
    (Map<String, dynamic> data) => DiscountsList.fromMap(data)
    );
  }

  /// Получить список валют
  Future<List<Currency>> finance_getCurrencies(
  ) async {
    return await request(
        'v1/finance/getCurrencies', {
    },
    (value) => value
    );
  }

  /// Получение валюты
  Future<Currency> finance_getCurrency(
    int id,
  ) async {
    return await request(
        'v1/finance/getCurrency', {
        'id': id,
    },
    (Map<String, dynamic> data) => Currency.fromMap(data)
    );
  }

  /// Получение валюты
  Future<Currency> finance_getCurrencyByCode(
    String code,
  ) async {
    return await request(
        'v1/finance/getCurrencyByCode', {
        'code': code,
    },
    (Map<String, dynamic> data) => Currency.fromMap(data)
    );
  }

  /// Информация о посетителе
  Future<SessionInfo> getMe(
  ) async {
    return await request(
        'v1/getMe', {
    },
    (Map<String, dynamic> data) => SessionInfo.fromMap(data)
    );
  }

  /// Инициализация клиента
  Future<InitializationData> init(
  ) async {
    return await request(
        'v1/init', {
    },
    (Map<String, dynamic> data) => InitializationData.fromMap(data)
    );
  }

  /// Получить исходный товар
  Future<SourceProduct> products_getSourceProduct(
    String uuid,
  ) async {
    return await request(
        'v1/products/getSourceProduct', {
        'uuid': uuid,
    },
    (Map<String, dynamic> data) => SourceProduct.fromMap(data)
    );
  }

  /// Получение полной информации об исходном товаре
  Future<SourceProductFull> products_getSourceProductFull(
    String uuid,
    {
    bool? testMode,
    }
  ) async {
    return await request(
        'v1/products/getSourceProductFull', {
        'uuid': uuid,
        'test_mode': testMode,
    },
    (Map<String, dynamic> data) => SourceProductFull.fromMap(data)
    );
  }

  /// Получить список товаров
  Future<SourceProductsList> products_getSourceProducts(
    {
    int? page,
    int? limit,
    String? sortBy,
    String? sortDirection,
    }
  ) async {
    return await request(
        'v1/products/getSourceProducts', {
        'page': page,
        'limit': limit,
        'sort_by': sortBy,
        'sort_direction': sortDirection,
    },
    (Map<String, dynamic> data) => SourceProductsList.fromMap(data)
    );
  }

  /// Получить интернет-магазин
  Future<Store> stores_getStore(
    int id,
  ) async {
    return await request(
        'v1/stores/getStore', {
        'id': id,
    },
    (Map<String, dynamic> data) => Store.fromMap(data)
    );
  }

  /// Получить интернет-магазин
  Future<Store> stores_getStoreByCode(
    String code,
  ) async {
    return await request(
        'v1/stores/getStoreByCode', {
        'code': code,
    },
    (Map<String, dynamic> data) => Store.fromMap(data)
    );
  }

  /// Получить выборку интернет-магазинов
  Future<StoresList> stores_getStores(
    {
    int? page,
    int? limit,
    String? sortBy,
    String? sortDirection,
    }
  ) async {
    return await request(
        'v1/stores/getStores', {
        'page': page,
        'limit': limit,
        'sort_by': sortBy,
        'sort_direction': sortDirection,
    },
    (Map<String, dynamic> data) => StoresList.fromMap(data)
    );
  }

  /// Обновление JWT-токенов
  Future<JwtTokensPair> tokens_refresh(
    String refreshToken,
  ) async {
    return await request(
        'v1/tokens/refresh', {
        'refresh_token': refreshToken,
    },
    (Map<String, dynamic> data) => JwtTokensPair.fromMap(data)
    );
  }

}

/// Глобальный экземпляр сессии API
ApiSession session = ApiSession();


