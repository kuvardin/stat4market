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


class Book {
  /// ID
  int id;

  /// Наименование
  String title;

  /// ISBN
  String isbn;

  /// Автор
  String author;

  /// Год издания
  int? yearPublished;

  /// Таймштамп создания записи
  int createdAt;

  Book.fromMap(Map<String, dynamic> data)
    :
    id = data['id'],
    title = data['title'],
    isbn = data['isbn'],
    author = data['author'],
    yearPublished = data['year_published'],
    createdAt = data['created_at'];
}

class BooksList {
  /// Книги
  List<Book> books;

  /// Данные о выборке
  SelectionData selectionData;

  BooksList.fromMap(Map<String, dynamic> data)
    :
    books = data['books'].map<Book>((itemData) => Book.fromMap(itemData)).toList(),
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

  /// Удаление книги
  Future<void> deleteBooksWithId(
    int id,
  ) async {
    return await request(
        'v1/deleteBooksWithId', {
        'id': id,
    },
    (value) => value
    );
  }

  /// Получение выборки книг
  Future<BooksList> getBooks(
    {
    int? page,
    int? limit,
    String? sortBy,
    String? sortDirection,
    String? query,
    int? yearPublished,
    }
  ) async {
    return await request(
        'v1/getBooks', {
        'page': page,
        'limit': limit,
        'sort_by': sortBy,
        'sort_direction': sortDirection,
        'query': query,
        'year_published': yearPublished,
    },
    (Map<String, dynamic> data) => BooksList.fromMap(data)
    );
  }

  /// Получение книги
  Future<Book> getBooksWithId(
    int id,
  ) async {
    return await request(
        'v1/getBooksWithId', {
        'id': id,
    },
    (Map<String, dynamic> data) => Book.fromMap(data)
    );
  }

  /// Создание книги
  Future<Book> postBooks(
    String title,
    String author,
    String isbn,
    {
    int? yearPublished,
    }
  ) async {
    return await request(
        'v1/postBooks', {
        'title': title,
        'author': author,
        'year_published': yearPublished,
        'isbn': isbn,
    },
    (Map<String, dynamic> data) => Book.fromMap(data)
    );
  }

  /// Редактирование книги
  Future<Book> putBooksWithId(
    int id,
    {
    String? title,
    String? author,
    int? yearPublished,
    bool? clearYearPublished,
    String? isbn,
    }
  ) async {
    return await request(
        'v1/putBooksWithId', {
        'id': id,
        'title': title,
        'author': author,
        'year_published': yearPublished,
        'clear_year_published': clearYearPublished,
        'isbn': isbn,
    },
    (Map<String, dynamic> data) => Book.fromMap(data)
    );
  }

}

/// Глобальный экземпляр сессии API
ApiSession session = ApiSession();


