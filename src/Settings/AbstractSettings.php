<?php

namespace Drupal\os2forms_fordelingskomponent\Settings;

/**
 * Abstract settings.
 */
abstract class AbstractSettings implements \JsonSerializable {
  protected const string UUID_PATTERN = '^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$';
  protected const string CVR_PATTERN = '^[0-9]{8}$';

  /**
   * Settings properties.
   *
   * Map from name to AbstractSettings type, e.g.
   *
   * <code>
   *   $settingsProperties = [
   *     'items' => SomeNestedSettings::class,
   *   ];
   * </code>
   *
   * @var array<string, string>
   */
  protected static array $settingsProperties = [];

  /**
   * List properties.
   *
   * Map from name to AbstractSettings type, e.g.
   *
   * <code>
   *   $listProperties = [
   *     'items' => SomeNestedSettings::class,
   *   ];
   * </code>
   *
   * @var array<string, string>
   */
  protected static array $listProperties = [];

  /**
   * Nullable properties.
   *
   * List of properties that must be set to null if the value is a blank string.
   *
   * @var array<string, bool>
   */
  protected static array $nullableProperties = [];

  /**
   * The values.
   *
   * @var array<string, mixed>
   */
  protected array $values;

  /**
   * Constructor.
   *
   * @param array<string, mixed> $values
   *   The values.
   * @param bool $throwExceptionOnMissingProperty
   *   If set, an exception is thrown when setting an undefined property.
   *   If not set, undefined properties are silently ignored.
   */
  public function __construct(array $values, bool $throwExceptionOnMissingProperty = FALSE) {
    $this->values = [];
    $this->apply($values, $throwExceptionOnMissingProperty);
  }

  /**
   * Apply values to settings.
   */
  public function apply(array $values, bool $throwExceptionOnMissingProperty = FALSE): static {
    foreach (static::$listProperties as $property => $class) {
      if (isset($values[$property]) && is_array($values[$property])) {
        $values[$property] = array_map(static fn(array $vals) => new $class($vals), $values[$property]);
      }
    }

    foreach (static::$settingsProperties as $property => $class) {
      if (isset($values[$property]) && is_array($values[$property])) {
        $values[$property] = new $class($values[$property]);
      }
    }

    foreach ($values as $key => $value) {
      $name = self::kebab2camel($key);
      if (!property_exists($this, $name)) {
        if ($throwExceptionOnMissingProperty) {
          throw new \RuntimeException(
            $name !== $key
              ? sprintf('Property "%s" ("%s") does not exist in class %s.',
              $name, $key, static::class)
              : sprintf('Property "%s" does not exist in class %s.', $name,
              static::class)
          );
        }
        else {
          continue;
        }
      }
      if (isset(static::$nullableProperties[$key]) && empty(trim((string) $value))) {
        $value = NULL;
      }
      $this->$name = $value;
      $this->values[self::camel2kebab($name)] = $value;
    }

    return $this;
  }

  /**
   * Convert settings to array.
   */
  public function toArray(bool $recursive = true): array {
    $values = $this->values;

    if ($recursive) {
      foreach ($values as &$value) {
        if ($value instanceof self) {
          $value = $value->toArray($recursive);
        }
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->toArray();
  }

  /**
   * Convert kebab_case to camelCase.
   */
  public static function kebab2camel(string $value): string {
    return lcfirst(str_replace('_', '', ucwords($value, '_')));
  }

  /**
   * Convert camelCase to kebab_case.
   *
   * @see https://stackoverflow.com/a/40514305/2502647
   */
  public static function camel2kebab(string $value): string {
    return strtolower((string) preg_replace('/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', '_', $value));
  }

}
