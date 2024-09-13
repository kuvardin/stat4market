<?php

declare(strict_types=1);

// phpcs:ignoreFile

/**
 * @var string $name
 * @var ApiParameter $parameter
 */

use App\Api\v1\Input\ApiParameter;
use App\Api\v1\Input\ApiParameterType;
use App\Utils\CaseSwitcher;

$name_cc = CaseSwitcher::snakeToCamel($name);
?>
<?php if (
    $parameter->type === ApiParameterType::String
    || $parameter->type === ApiParameterType::Integer
    || $parameter->type === ApiParameterType::Float
    || $parameter->type === ApiParameterType::Boolean
    || $parameter->type === ApiParameterType::Uuid
    || $parameter->type === ApiParameterType::Array
) : ?>
    <?= $name ?>: <?= $name_cc ?>,
<?php elseif (
    $parameter->type === ApiParameterType::Date
    || $parameter->type === ApiParameterType::DateTime
) : ?>
<?php if ($parameter->isRequired()): ?>
    <?= $name ?>: <?= $name_cc ?>.getTime(),
<?php else: ?>
    <?= $name ?>: <?= $name_cc ?> === null ? null : <?= $name_cc ?>.getTime(),
<?php endif; ?>
<?php elseif ($parameter->type === ApiParameterType::Phrase) : ?>
<?php if ($parameter->isRequired()): ?>
    <?= $name ?>: Object.fromEntries(<?= $name_cc ?>.values.entries()),
<?php else: ?>
    <?= $name ?>: <?= $name_cc ?> === null ? null : Object.fromEntries(<?= $name_cc ?>.values.entries()),
<?php endif; ?>
<?php elseif ($parameter->type === ApiParameterType::Enum) : ?>
    <?= $name ?>: <?= $name_cc ?>,
<?php else : ?>
    <?php throw new RuntimeException("Unexpected ApiParameter type: {$parameter->type->value}"); ?>
<?php endif; ?>