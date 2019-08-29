<?php

namespace Drupal\search_365\Serializer;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Base normalizer class.
 */
abstract class BaseNormalizer extends AbstractNormalizer {

  use ClassNameSupportNormalizerTrait;

  /**
   * Checks whether the array is associative.
   *
   * @param array $array
   *   The array.
   *
   * @return bool
   *   TRUE if the array is associative.
   */
  protected function isAssoc(array $array) {
    return (bool) count(array_filter(array_keys($array), 'is_string'));
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    throw new \RuntimeException("Method not implemented.");
  }

}
