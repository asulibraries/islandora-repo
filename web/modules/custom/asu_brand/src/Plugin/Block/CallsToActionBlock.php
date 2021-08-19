<?php

namespace Drupal\asu_brand\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'CallsToActionBlock' block.
 *
 * @Block(
 *  id = "calls_to_action_block",
 *  admin_label = @Translation("Calls to action block"),
 * )
 */
class CallsToActionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    // $build['#theme'] = 'calls_to_action_block';
    $share = '<div class="card-title">' .
    '  <h3 class="card-title">Contribute</h3>' .
    '</div>' .
    '<div class="card-body">' .
    '  <p>Deposit your scholarly work into KEEP for ' .
    'preservation and ongoing access.</p><br/><br/>' .
    '  <div class="card-button"><a role="button" class="btn btn-md btn-maroon" href="/self_deposit/asu_repository_item">Share your work!</a></div>' .
    '</div>';
    $contact = '<div class="card-title">' .
    '  <h3 class="card-title">Get in touch</h3>' .
    '</div>' .
    '<div class="card-body">' .
    '  <p>Reach out to us if you have questions or concerns about KEEP.</p><br/><br/>' .
    '  <div class="card-button"><a role="button" class="btn btn-md btn-maroon" href="/contact">Contact us</a></div>' .
    '</div>';
    $resources = '<div class="card-title">' .
    '  <h3 class="card-title">Learn more</h3>' .
    '</div>' .
    '<div class="card-body"><ul><li><a href="http://libguides.asu.edu/openaccess">Open Access at ASU</a></li><li><a href="/policies">Repository Policies</a></li></ul>' .
    '</div>';
    $build['calls_to_action_block']['#markup'] = 
    '<div class="calls-to-action">' .
    '  <div class="container">' .
    '    <div class="row">' .
    '      <div class="col-md-4 card">' . $share . '</div>' .
    '      <div class="col-md-4 card">' . $contact . '</div>' .
    '      <div class="col-md-4 card">' . $resources . '</div>' .
    '    </div>' .
    '  </div>' .
    '</div>';

    return $build;
  }

}
