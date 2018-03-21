<?php

namespace Drupal\registryonsteroids;

/**
 * Creates ThemeHookStub objects.
 *
 * @codingStandardsIgnoreFile
 */
class ThemeHookStubFactory {

  /**
   * @var array[]
   */
  private $registry;

  /**
   * @var string[][][]
   *   Format: $[$hook][$phase_key][$weight] = $function
   */
  private $functionsByHookAndPhasekeyAndWeight;

  /**
   * @var string[][]
   *   Format: $[$phase_key][$weight] = $function
   */
  private $templateFunctionsByPhasekeyAndWeight;

  /**
   * @param array[] $registry
   * @param string[][][] $functions_by_hook_and_phasekey_and_weight
   */
  public function __construct(array $registry, array $functions_by_hook_and_phasekey_and_weight) {
    $this->registry = $registry;
    $this->functionsByHookAndPhasekeyAndWeight = $functions_by_hook_and_phasekey_and_weight;
    $this->templateFunctionsByPhasekeyAndWeight = $functions_by_hook_and_phasekey_and_weight['*'];
  }

  /**
   * @param string $hook
   * @param \Drupal\registryonsteroids\ThemeHookStub|null $parent
   *
   * @return \Drupal\registryonsteroids\ThemeHookStub|null
   */
  public function createStub($hook, ThemeHookStub $parent = NULL) {
    $info = isset($this->registry[$hook])
      ? $this->registry[$hook]
      : NULL;

    $functions_by_phasekey_and_weight = isset($this->functionsByHookAndPhasekeyAndWeight[$hook])
      ? $this->functionsByHookAndPhasekeyAndWeight[$hook]
      : array();

    if (NULL !== $parent) {
      return $parent->addVariant(
        $hook,
        $info,
        $functions_by_phasekey_and_weight);
    }

    if (NULL === $info) {
      return NULL;
    }

    return ThemeHookStub::createRoot(
      $hook,
      $info,
      $functions_by_phasekey_and_weight,
      isset($info['template'])
        ? $this->templateFunctionsByPhasekeyAndWeight
        : array());
  }

}
