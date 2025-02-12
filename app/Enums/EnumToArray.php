<?php

namespace App\Enums;

trait EnumToArray
{

  public static function names(): array
  {
    return array_column(self::cases(), 'name');
  }

  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }

  public static function array(): array
  {
    return array_combine(self::values(), self::names());
  }

  public static function getEnumByName(string $name): self {
    foreach (self::cases() as $status) {
        if($name === $status->name) {
            return $status;
        }
    }

    throw new \ValueError("$status is not valid");
  }
  public static function getEnumByValue(string $name): self {
    foreach (self::cases() as $status) {
        if($name === $status->value) {
            return $status;
        }
    }
    throw new \ValueError("$status is not valid");
  }

  public static function is_in_name(string $name): bool
  {
    return in_array($name, self::names());
  }
  public static function is_in_value(string $name): bool
  {
    return in_array($name, self::values());
  }

}