<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent_debug\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\os2forms_fordelingskomponent\Helper\FordelingskomponentHelper;
use Drupal\os2forms_fordelingskomponent_debug\Hook\ThemeHooks;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Returns responses for os2forms_fordelingskomponent_debug routes.
 */
final class Os2formsFordelingskomponentDebugSftpController extends ControllerBase {

  public function __construct(
    private readonly FordelingskomponentHelper $helper,
    #[Autowire(service: 'file.mime_type.guesser')]
    private readonly MimeTypeGuesserInterface $mimeTypeGuesser,
  ) {
  }

  /**
   * Builds the response.
   */
  public function __invoke(string $dir = '/', ?string $filename = NULL): array|Response {
    $path = $this->normalizePath(implode('/', array_filter([$dir, $filename])));

    $sftp = $this->helper->sf2900()->sftp();

    if (preg_match('/\.[^.]+$/', $path)) {
      $content = $sftp->getContents($filename, $dir);

      $contentType = match(pathinfo($path, PATHINFO_EXTENSION)) {
        'sftpreceipt', 'trigger' => $this->mimeTypeGuesser->guessMimeType('name.xml'),
        default => $this->mimeTypeGuesser->guessMimeType($path)
      };

      return new Response($content, Response::HTTP_OK, [
        'Content-Type' => $contentType,
      ]);
    }
    else {
      $files = $sftp->getFiles($path);

      // Filter out . and ..
      $files = array_filter($files, static fn (string $name) => !preg_match('/^\.+$/', $name));

      $files = array_map(fn (string $name) => $this->normalizePath($path . '/' . $name), $files);

      return [
        '#theme' => ThemeHooks::SFTP_FILES,
        '#files' => $files,
        '#parent_dir' => dirname($path),
      ];
    }
  }

  /**
   * Normalize file path.
   */
  private function normalizePath(string $path): string {
    return '/' . trim(preg_replace('@/+@', '/', $path), '/');
  }

}
