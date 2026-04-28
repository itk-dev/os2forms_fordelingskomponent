<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent_debug\Controller;

use Drupal\Core\Url;
use Drupal\os2forms_fordelingskomponent\Helper\FordelingskomponentHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Returns responses for os2forms_fordelingskomponent_debug routes.
 */
final class Os2formsFordelingskomponentDebugSftpController extends AbstractController {

  public function __construct(
    private readonly FordelingskomponentHelper $helper,
    #[Autowire(service: 'file.mime_type.guesser')]
    private readonly MimeTypeGuesserInterface $mimeTypeGuesser,
  ) {
  }

  /**
   * Builds the response.
   */
  public function __invoke(?string $dir, ?string $filename = NULL): array|Response {
    $sftp = $this->helper->sf2900()->sftp();

    if (NULL !== $filename && preg_match('/\.[^.]+$/', $filename)) {
      $content = $sftp->getContents($filename, $dir);

      $contentType = match(pathinfo($filename, PATHINFO_EXTENSION)) {
        'sftpreceipt', 'trigger' => $this->mimeTypeGuesser->guessMimeType('name.xml'),
        default => $this->mimeTypeGuesser->guessMimeType($filename)
      };

      return new Response($content, Response::HTTP_OK, [
        'Content-Type' => $contentType,
      ]);
    }
    else {
      $files = $sftp->getFiles($dir ?? '.', recursive: TRUE, raw: TRUE);

      // Filter out . and ..
      $files = array_filter($files, static fn (string $name) => !preg_match('/^\.+$/', $name), ARRAY_FILTER_USE_KEY);

      $header = [
        'filepath' => $this->t('Path'),
        'atime' => $this->t('Last accessed at'),
        'mtime' => $this->t('Last modified at'),
      ];
      $rows = [];
      foreach ($files as $stat) {
        $rows[] = [
          'filepath' => [
            'data' => [
              '#title' => '/' . ($dir ? $dir . '/' : '') . $stat->filename,
              '#type' => 'link',
              '#url' => $dir
                ? Url::fromRoute('os2forms_fordelingskomponent_debug.os2forms_fordelingskomponent_debug_sftp_filename', [
                  'dir' => $dir,
                  'filename' => $stat->filename,
                ])
                : Url::fromRoute('os2forms_fordelingskomponent_debug.os2forms_fordelingskomponent_debug_sftp', [
                  'dir' => $stat->filename,
                ]),
            ],
          ],
          'atime' => $this->formatDatetime($stat->atime ?? NULL),
          'mtime' => $this->formatDatetime($stat->mtime ?? NULL),
        ];
      }

      return [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No entries available.'),
      ];
    }
  }

}
