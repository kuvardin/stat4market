<?php

// phpcs:ignoreFile

declare(strict_types=1);

use App\Api\v1\ApiModel;
use App\Api\v1\ApiMethod;
use App\Api\v1\Exceptions\ApiException;
use App\Api\v1\Input\ApiParameter;
use App\Api\v1\Output\ApiFieldType;
use App\TemplatesEngine\TemplatesEngine;
use App\Languages\Locale;
use App\Api\v1\Models;
use App\Utils\CaseSwitcher;

/**
 * @var string $title
 * @var Locale $language_code
 * @var ApiModel[]|string[] $models
 * @var string[] $models_errors
 * @var ApiMethod[]|string[] $methods
 * @var string[] $methods_errors
 * @var int[][] $methods_error_codes
 */

?>
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

<?php foreach ($models as $model_class): ?>

class <?= $model_class::getName() ?> {
<?php foreach ($model_class::getFields() as $model_field_name => $model_field): ?>
  /// <?= ($model_field->description ?? "Field {$model_field_name}") ?>

  <?= $model_field->getDartType() ?><?= $model_field->nullable ? '?' : '' ?> <?= CaseSwitcher::snakeToCamel($model_field_name) ?>;

<?php endforeach; ?>
<?php if ($model_class === Models\JwtTokenApiModel::class): ?>
  JwtToken(this.value, this.expirationDate);

  bool isExpired() {
    return DateTime.now().millisecondsSinceEpoch > (expirationDate * 1000);
  }

<?php endif; ?>
  <?= $model_class::getName() ?>.fromMap(Map<String, dynamic> data)
    :
<?php $model_field_index = 0; ?>
<?php foreach ($model_class::getFields() as $model_field_name => $model_field): ?>
<?php $line_break = ++$model_field_index === count($model_class::getFields()) ? ';' : ','; ?>
    <?= TemplatesEngine::render('dart/api/api_model_field_initialization', [
        'name' => $model_field_name,
            'field' => $model_field,
        ]) ?><?= $line_break ?>

<?php endforeach; ?>
}
<?php endforeach; ?>


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

<?php foreach ($methods as $method_name => $method_class): ?>
  /// <?= $method_class::getDescription() ?? "Method {$method_name}" ?>

  Future<<?= $method_class::getResultField()?->getDartType() ?? 'void' ?>> <?= str_replace('/', '_', $method_name) ?>(
<?php foreach ($method_class::getAllParameters($language_code, true) as $parameter_name => $parameter): ?>
    <?= $parameter->getDartType() ?> <?= CaseSwitcher::snakeToCamel($parameter_name) ?>,
<?php endforeach; ?>
<?php if ($method_class::getAllParameters($language_code, false) !== []): ?>
    {
<?php foreach ($method_class::getAllParameters($language_code, false) as $parameter_name => $parameter): ?>
    <?= $parameter->getDartType() ?>? <?= CaseSwitcher::snakeToCamel($parameter_name) ?>,
<?php endforeach; ?>
    }
<?php endif; ?>
  ) async {
    return await request(
        'v1/<?= $method_name ?>', {
<?php foreach ($method_class::getAllParameters($language_code) as $parameter_name => $parameter): ?>
        '<?= $parameter_name ?>': <?= CaseSwitcher::snakeToCamel($parameter_name) ?>,
<?php endforeach; ?>
    },
<?php if ($method_class::getResultField()->type === ApiFieldType::Object): ?>
    (Map<String, dynamic> data) => <?= $method_class::getResultField()->getDartType() ?>.fromMap(data)
<?php else: ?>
    (value) => value
<?php endif ?>
    );
  }

<?php endforeach; ?>
}

/// Глобальный экземпляр сессии API
ApiSession session = ApiSession();


