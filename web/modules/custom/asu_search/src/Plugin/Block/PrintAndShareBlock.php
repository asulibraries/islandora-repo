<?php

/**
 * @file
 * PrintAndShareBlock
 */
namespace Drupal\asu_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Print and share' Block.
 *
 * @Block(
 *   id = "print_and_share_item_block",
 *   admin_label = @Translation("Print and share"),
 *   category = @Translation("Views"),
 * )
 */
class PrintAndShareBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * This should probably call a separate function that will output the
     * HTML --- and this same contents will be needed in the main part of the
     * page so that same function should be used there.
     */
    return [
      '#markup' => '
        <div class="list-group-item nope">
                    <button id="sidebar-share-print"
                            data-ga-action="Sidebar Print Button"
                        class="btn  btn-link print">
                    <i class="fa fa-print" aria-hidden="true"></i><span class="sr-only">Print</span></button>  

                    <button 
                        id="sidebar-share-email"
                        class="btn  btn-link sharer"
                        data-sharer="email"
                        data-to="" 
                        data-social-action="send"
                        data-ga-action="Shared via email (sidebar)"
                        data-title="[The $64 Tomato Edible Book]"
                        data-url="https://digital.library.unt.edu/ark:/67531/metadc991216/?utm_source=email&amp;utm_medium=client&amp;utm_content=ark_sidebar&amp;utm_campaign=ark_permanent"
                        data-subject="[The $64 Tomato Edible Book]: UNT Digital Library">
                        <i class="fa fa-envelope" aria-hidden="true"></i><span class="sr-only">Email</span>
                    </button>
                    <button
                        id="sidebar-share-twitter"
                        class="btn  btn-link sharer  no-shorten"
                        data-sharer="twitter"
                        data-social-action="tweet"
                        data-title="[The $64 Tomato Edible Book]"
                        data-url="https://digital.library.unt.edu/ark:/67531/metadc991216/?utm_source=twitter&amp;utm_medium=social&amp;utm_content=ark_sidebar&amp;utm_campaign=ark_permanent"
                        data-ga-action="Shared to Twitter (sidebar)">
                        <i class="fa fa-twitter" aria-hidden="true"></i><span class="sr-only">Twitter</span>
                    </button>
                    <button
                        id="sidebar-share-facebook"
                        class="btn  btn-link sharer"
                        data-sharer="facebook" 
                        data-social-action="share"
                        data-ga-action="Shared to Facebook (sidebar)"
                        data-url="https://digital.library.unt.edu/ark:/67531/metadc991216/?utm_source=facebook&amp;utm_medium=social&amp;utm_content=ark_sidebar&amp;utm_campaign=ark_permanent">
                        <i class="fa fa-facebook-official" aria-hidden="true"></i><span class="sr-only">Facebook</span>
                    </button>
                    
                    <button
                        id="sidebar-share-tumblr" 
                        class="btn  btn-link sharer"
                        data-sharer="tumblr"
                        data-social-action="share"
                        data-title=""
                        data-ga-action="Shared to Tumblr (sidebar)"
                        data-url="https://digital.library.unt.edu/ark:/67531/metadc991216/?utm_source=tumblr&amp;utm_medium=social&amp;utm_content=ark_sidebar&amp;utm_campaign=ark_permanent">
                        <i class="fa fa-tumblr" aria-hidden="true"></i><span class="sr-only">Tumblr</span>
                    </button>
                    <button
                        id="sidebar-share-reddit"
                        class="btn  btn-link sharer no-shorten"
                        data-sharer="reddit"
                        data-ga-action="Shared to reddit (sidebar)"
                        data-social-action="submit"
                        data-url="https://digital.library.unt.edu/ark:/67531/metadc991216/?utm_source=reddit&amp;utm_medium=social&amp;utm_content=ark_sidebar&amp;utm_campaign=ark_permanent">
                        <i class="fa fa-reddit" aria-hidden="true"></i><span class="sr-only">Reddit</span>
                    </button>
                </div>
            </div>',
    ];
  }

}