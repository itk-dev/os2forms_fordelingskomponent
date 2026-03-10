<?php

namespace Drupal\os2forms_fordelingskomponent\Model;

use Drupal\file\FileInterface;

/**
 * Files (from webform elements) attached to a distribution object.
 *
 * A Files instance provides array-like access to at structure like.
 *
 * @code
 * [
 *   'type name' => [
 *     [
 *       'sftp_filename' => …,
 *       'file' => instance of \Drupal\file\FileInterface
 *     ],
 *   ],
 * ]
 * @endcode
 */
final class DistributionObjectFiles implements \ArrayAccess, \Iterator, \Countable {
  /**
   * The files data.
   */
  private array $files = [];

  /**
   * Constructor.
   */
  public function __construct() {
  }

  /**
   * Add a file.
   */
  public function addFile(string $type, string $sftpFilename, FileInterface $file) {
    $this->files[$type][] = [
      'sftp_filename' => $sftpFilename,
      'file' => $file,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists(mixed $offset): bool {
    return isset($this->files[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet(mixed $offset): mixed {
    return $this->files[$offset] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet(mixed $offset, mixed $value): void {
    throw new \RuntimeException(__METHOD__ . ' is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset(mixed $offset): void {
    throw new \RuntimeException(__METHOD__ . ' is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function count(): int {
    return count($this->files);
  }

  /**
   * {@inheritdoc}
   */
  public function current(): mixed {
    return current($this->files);
  }

  /**
   * {@inheritdoc}
   */
  public function next(): void {
    next($this->files);
  }

  /**
   * {@inheritdoc}
   */
  public function key(): mixed {
    return key($this->files);
  }

  /**
   * {@inheritdoc}
   */
  public function valid(): bool {
    return NULL !== key($this->files);
  }

  /**
   * {@inheritdoc}
   */
  public function rewind(): void {
    reset($this->files);
  }

}
