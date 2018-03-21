<?php

namespace Drupal\registryonsteroids;

/**
 * Contains data to build a modified theme registry entry.
 *
 * @codingStandardsIgnoreFile
 */
class ThemeHookStub {

  /**
   * @var string
   */
  private $baseHook;

  /**
   * @var self
   */
  private $baseHookStub;

  /**
   * @var string[]
   *   Format: [$base_hook, .., $hook]
   */
  private $cascade;

  /**
   * @var array
   */
  private $info;

  /**
   * @var string[][]
   *   Format: $[$phase_key][$weight][] = $placeholder_or_function
   */
  private $placeholderssByPhasekeyAndWeight = array(
    'process functions' => array(),
    'preprocess functions' => array(),
  );

  /**
   * @var string[][]
   *   Format: $[$phase_key]['@' . $function] = $function
   */
  private $replacementssByPhasekey = array(
    'process functions' => array(),
    'preprocess functions' => array(),
  );

  /**
   * @param string $hook
   * @param array $info
   * @param string[][] $functionsByPhasekeyAndWeight
   *   Format: $[$phase_key][$weight] = $function
   * @param string[][] $templateFunctionsByPhasekeyAndWeight
   *   Format: $[$phase_key][$weight] = $function
   *   Should be empty if this is not a template.
   *
   * @return self
   */
  public static function createRoot($hook, array $info, array $functionsByPhasekeyAndWeight, array $templateFunctionsByPhasekeyAndWeight) {
    $root = new self();
    $root->baseHook = $hook;
    $root->cascade = array($hook);
    $root->baseHookStub = $root;
    $root->info = $info;
    foreach ($templateFunctionsByPhasekeyAndWeight as $phase_key => $functionsByWeight) {
      foreach ($functionsByWeight as $weight => $function) {
        $root->placeholderssByPhasekeyAndWeight[$phase_key][$weight][] = $function;
      }
    }
    foreach ($functionsByPhasekeyAndWeight as $phase_key => $functionsByWeight) {
      foreach ($functionsByWeight as $weight => $function) {
        $root->placeholderssByPhasekeyAndWeight[$phase_key][$weight][] = $function;
      }
    }
    return $root;
  }

  /**
   * Private (incomplete) constructor.
   */
  private function __construct() {}

  /**
   * @param string $hook
   * @param array|null $info
   * @param array $functionsByPhasekeyAndWeight
   *
   * @return static
   */
  public function addVariant($hook, array $info = NULL, array $functionsByPhasekeyAndWeight) {
    $variant = clone $this;
    $variant->cascade[] = $hook;
    foreach ($functionsByPhasekeyAndWeight as $phase_key => $functionsByWeight) {
      foreach ($functionsByWeight as $weight => $function) {
        $this->baseHookStub->placeholderssByPhasekeyAndWeight[$phase_key][$weight][] = '@' . $function;
        $variant->replacementssByPhasekey[$phase_key]['@' . $function] = $function;
      }
    }
    if (NULL !== $info) {
      $variant->info = $info;
    }
    else {
      // Do not inherit processor functions yet. This happens later.
      unset($variant->info['process functions']);
      unset($variant->info['preprocess functions']);
    }
    $variant->info['base hook'] = $this->baseHook;
    $variant->placeholderssByPhasekeyAndWeight = array();
    return $variant;
  }

  /**
   * @return array
   */
  public function getRegistryEntry() {
    $info = $this->info;
    foreach ($this->getPlaceholdersByPhasekeySorted() as $phase_key => $placeholders_sorted) {
      $info[$phase_key] = $placeholders_sorted;
    }

    $info['registryonsteroids replace'] = $this->replacementssByPhasekey;

    return $info;
  }

  /**
   * @return string[][][]
   *   Format: $[$phase_key][] = $function_or_placeholder
   */
  private function getPlaceholdersByPhasekeySorted() {
    $placeholders_by_phasekey = array();
    foreach ($this->placeholderssByPhasekeyAndWeight as $phase_key => $placeholderss_by_weight) {
      ksort($placeholderss_by_weight);
      $placeholders_by_phasekey[$phase_key] = array() !== $placeholderss_by_weight
        ? array_merge(...$placeholderss_by_weight)
        : array();
    }

    return $placeholders_by_phasekey;
  }

}
