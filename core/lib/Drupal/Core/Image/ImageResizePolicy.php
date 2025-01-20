<?php

declare(strict_types=1);
namespace Drupal\Core\Image;

/**
 * Enum for image resizing policies.
 */
enum ImageResizePolicy: string {
  case ResizeLargerImages = 'resize_larger_images';
  case RejectLargerImagesWithError = 'reject_larger_images_with_error';
}
